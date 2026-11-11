<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Auth\Pengguna;
use App\Models\SiswaBiodata;
use App\Services\OtpService;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Autentikasi aplikasi mobile siswa:
 * login, daftar, verifikasi OTP, lupa password, reset password, logout.
 */
class AuthApiController extends Controller
{
    private const OTP_JEDA_KIRIM_ULANG_DETIK = 60;

    private const RESET_TOKEN_BERLAKU_MENIT = 15;

    public function __construct(private OtpService $otpService) {}

    /**
     * POST /api/v1/login  (layar Login)
     * Username boleh NISN, username, atau email.
     */
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
            'nama_perangkat' => ['nullable', 'string', 'max:120'],
        ], [
            'username.required' => 'Email / NISN / Username wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ]);

        $input = trim($data['username']);

        // Maksimal 5 kali salah per akun + IP dalam 1 menit
        $kunci = 'login-mobile:'.Str::lower($input).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($kunci, 5)) {
            $detik = RateLimiter::availableIn($kunci);

            return $this->gagal("Terlalu banyak percobaan login. Coba lagi dalam {$detik} detik.", 429, 'terlalu_banyak');
        }

        $pengguna = Pengguna::where('username', $input)
            ->orWhere('email', $input)
            ->first();

        if (! $pengguna || ! Hash::check($data['password'], $pengguna->password)) {
            RateLimiter::hit($kunci, 60);

            return $this->gagal('Username atau kata sandi yang dimasukkan tidak sesuai.', 401, 'login_gagal');
        }

        RateLimiter::clear($kunci);

        if ($pengguna->peran !== 'siswa') {
            return $this->gagal('Aplikasi mobile khusus untuk siswa. Admin dan guru silakan masuk lewat portal web.', 403, 'bukan_siswa');
        }

        if (! $pengguna->aktif) {
            return $pengguna->email_diverifikasi_at
                ? $this->gagal('Akun Anda dinonaktifkan. Silakan hubungi administrator sekolah.', 403, 'akun_nonaktif')
                : $this->gagal('Akun belum diverifikasi. Selesaikan verifikasi kode OTP yang dikirim ke email Anda.', 403, 'belum_verifikasi');
        }

        $token = $pengguna->createToken($data['nama_perangkat'] ?? 'Perangkat tidak dikenal', ['siswa']);

        // Catat perangkat untuk layar "Sesi Aktif"
        DB::table('akun_perangkat_login')->insert([
            'pengguna_id' => $pengguna->id,
            'token_id' => $token->accessToken->id,
            'nama_perangkat' => $data['nama_perangkat'] ?? null,
            'alamat_ip' => $request->ip(),
            'terakhir_aktif_at' => now(),
            'created_at' => now(),
        ]);

        $pengguna->terakhir_login_at = now();
        $pengguna->save();

        return response()->json([
            'pesan' => 'Login berhasil.',
            'data' => [
                'token' => $token->plainTextToken,
                'tipe_token' => 'Bearer',
                'wajib_ganti_password' => (bool) $pengguna->wajib_ganti_password,
                'pengguna' => [
                    'nama' => $pengguna->nama,
                    'nisn' => $pengguna->username,
                    'email' => $pengguna->email,
                ],
            ],
        ]);
    }

    /**
     * POST /api/v1/daftar  (layar Daftar)
     * Nama + NISN harus cocok dengan data siswa di sekolah. Akun dibuat
     * dalam keadaan belum aktif, lalu diaktifkan setelah OTP email benar.
     */
    public function daftar(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:150'],
            'nisn' => ['required', 'digits:10'],
            'email' => ['required', 'email', 'max:120'],
            'password' => ['required', 'string', 'min:8'],
        ], [
            'nama.required' => 'Nama lengkap wajib diisi.',
            'nisn.required' => 'NISN wajib diisi.',
            'nisn.digits' => 'NISN harus 10 digit angka.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 8 karakter.',
        ]);

        $nisn = trim($data['nisn']);
        $email = Str::lower(trim($data['email']));

        $siswa = SiswaBiodata::where('nisn', $nisn)->first();

        if (! $siswa || ! $this->samaNama($siswa->nama_lengkap, $data['nama'])) {
            return $this->gagal('Data tidak cocok dengan data siswa di sekolah. Periksa kembali nama dan NISN Anda.', 422, 'register_gagal');
        }

        $akun = Pengguna::where('siswa_id', $siswa->id)->orWhere('username', $nisn)->first();

        // Akun yang sudah aktif tidak boleh didaftarkan ulang
        if ($akun && ($akun->aktif || $akun->peran !== 'siswa')) {
            return $this->gagal('Akun untuk NISN ini sudah terdaftar. Silakan login.', 422, 'register_gagal');
        }

        $emailDipakai = Pengguna::where('email', $email)
            ->when($akun, fn ($q) => $q->where('id', '!=', $akun->id))
            ->exists();

        if ($emailDipakai) {
            return $this->gagal('Email ini sudah dipakai oleh akun lain.', 422, 'register_gagal');
        }

        DB::transaction(function () use ($akun, $siswa, $nisn, $email, $data) {
            if ($akun) {
                // Pendaftaran yang belum diverifikasi: perbarui isiannya
                $akun->email = $email;
                $akun->password = Hash::make($data['password']);
                $akun->email_diverifikasi_at = null;
                $akun->save();

                return;
            }

            Pengguna::create([
                'peran' => 'siswa',
                'username' => $nisn,
                'email' => $email,
                'password' => Hash::make($data['password']),
                'aktif' => false,
                'siswa_id' => $siswa->id,
                'wajib_ganti_password' => false,
            ]);
        });

        try {
            $kode = $this->buatDanKirimOtp($email, 'daftar');
        } catch (\Throwable $e) {
            Log::error('Gagal mengirim OTP daftar: '.$e->getMessage());

            return $this->gagal('Gagal mengirim email verifikasi. Coba lagi beberapa saat.', 503);
        }

        return response()->json([
            'pesan' => 'Kode verifikasi telah dikirim ke email Anda.',
            'data' => $this->dataOtp($email, $kode),
        ], 201);
    }

    /**
     * POST /api/v1/verifikasi-otp  (layar Verifikasi Email)
     * tujuan: daftar (default) atau lupa_password.
     */
    public function verifikasiOtp(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'kode' => ['required', 'digits:6'],
            'tujuan' => ['nullable', 'in:daftar,lupa_password'],
        ], [
            'kode.required' => 'Masukkan 6 digit kode verifikasi.',
            'kode.digits' => 'Kode verifikasi harus 6 digit angka.',
        ]);

        $email = Str::lower(trim($data['email']));
        $tujuan = $data['tujuan'] ?? 'daftar';

        $hasil = $this->otpService->verifikasi($email, $tujuan, $data['kode']);

        if ($hasil === OtpService::TIDAK_VALID) {
            return $this->gagal('Kode verifikasi tidak valid atau sudah kedaluwarsa. Silakan kirim ulang kode.', 422, 'otp_salah');
        }

        if ($hasil === OtpService::TERLALU_BANYAK) {
            return $this->gagal('Terlalu banyak percobaan. Silakan kirim ulang kode baru.', 429, 'otp_terlalu_banyak');
        }

        if ($hasil === OtpService::SALAH) {
            return $this->gagal('Kode verifikasi salah.', 422, 'otp_salah');
        }

        $pengguna = Pengguna::where('email', $email)->where('peran', 'siswa')->first();

        if (! $pengguna) {
            return $this->gagal('Akun tidak ditemukan.', 404);
        }

        if ($tujuan === 'daftar') {
            $pengguna->aktif = true;
            $pengguna->email_diverifikasi_at = now();
            $pengguna->save();

            NotifikasiApiController::buat($pengguna->id, 'Akun berhasil dibuat', 'Selamat datang di JosBook. Akun Anda sudah aktif.');

            return response()->json([
                'pesan' => 'Register berhasil. Silakan login.',
                'data' => [
                    'nama' => $pengguna->nama,
                    'nisn' => $pengguna->username,
                ],
            ]);
        }

        // lupa_password: beri token singkat untuk langkah "buat kata sandi baru"
        return response()->json([
            'pesan' => 'Kode benar. Silakan buat kata sandi baru.',
            'data' => ['reset_token' => $this->buatResetToken($pengguna)],
        ]);
    }

    /**
     * POST /api/v1/kirim-ulang-otp  (tombol "Kirim ulang")
     */
    public function kirimUlangOtp(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'tujuan' => ['nullable', 'in:daftar,lupa_password'],
        ]);

        $email = Str::lower(trim($data['email']));
        $tujuan = $data['tujuan'] ?? 'daftar';

        $sisa = $this->otpService->detikSampaiKirimUlang($email, $tujuan, self::OTP_JEDA_KIRIM_ULANG_DETIK);
        if ($sisa > 0) {
            return response()->json([
                'pesan' => 'Tunggu sebentar sebelum mengirim ulang kode.',
                'kode' => 'tunggu',
                'data' => ['sisa_detik' => $sisa],
            ], 429);
        }

        // Jawaban selalu sama agar email yang terdaftar tidak bisa ditebak
        $kode = null;
        if (Pengguna::where('email', $email)->where('peran', 'siswa')->exists()) {
            try {
                $kode = $this->buatDanKirimOtp($email, $tujuan);
            } catch (\Throwable $e) {
                Log::error('Gagal mengirim ulang OTP: '.$e->getMessage());

                return $this->gagal('Gagal mengirim email verifikasi. Coba lagi beberapa saat.', 503);
            }
        }

        return response()->json([
            'pesan' => 'Jika email terdaftar, kode verifikasi baru telah dikirim.',
            'data' => $this->dataOtp($email, $kode),
        ]);
    }

    /**
     * POST /api/v1/lupa-password  (layar Lupa Kata Sandi langkah 1)
     */
    public function lupaPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
        ]);

        $email = Str::lower(trim($data['email']));

        $pengguna = Pengguna::where('email', $email)
            ->where('peran', 'siswa')
            ->where('aktif', true)
            ->first();

        $kode = null;
        if ($pengguna) {
            try {
                $kode = $this->buatDanKirimOtp($email, 'lupa_password');
            } catch (\Throwable $e) {
                Log::error('Gagal mengirim OTP lupa password: '.$e->getMessage());

                return $this->gagal('Gagal mengirim email verifikasi. Coba lagi beberapa saat.', 503);
            }
        }

        // Jawaban selalu sama agar email yang terdaftar tidak bisa ditebak
        return response()->json([
            'pesan' => 'Jika email terdaftar, kode verifikasi telah dikirim.',
            'data' => $this->dataOtp($email, $kode),
        ]);
    }

    /**
     * POST /api/v1/reset-password  (layar Buat Kata Sandi Baru)
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'reset_token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'password.required' => 'Kata sandi baru wajib diisi.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
        ]);

        $pengguna = $this->bacaResetToken($data['reset_token']);

        if (! $pengguna) {
            return $this->gagal('Sesi reset kata sandi tidak valid atau sudah kedaluwarsa. Ulangi dari awal.', 422, 'reset_kedaluwarsa');
        }

        $pengguna->password = Hash::make($data['password']);
        $pengguna->wajib_ganti_password = false;
        $pengguna->save();

        // Keluarkan semua perangkat yang sedang login
        $pengguna->tokens()->delete();
        DB::table('akun_perangkat_login')->where('pengguna_id', $pengguna->id)->delete();

        NotifikasiApiController::buat($pengguna->id, 'Password berhasil diganti', 'Password akun Anda telah diperbarui.');

        return response()->json(['pesan' => 'Kata sandi berhasil diganti. Silakan login.']);
    }

    /**
     * POST /api/v1/logout  (logout perangkat ini)
     */
    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()->currentAccessToken();

        DB::table('akun_perangkat_login')->where('token_id', $token->id)->delete();
        $token->delete();

        return response()->json(['pesan' => 'Berhasil keluar.']);
    }

    // ─── Pembantu ────────────────────────────────────────────────────────────

    private function gagal(string $pesan, int $status, ?string $kode = null): JsonResponse
    {
        return response()->json(array_filter([
            'pesan' => $pesan,
            'kode' => $kode,
        ]), $status);
    }

    private function samaNama(?string $a, ?string $b): bool
    {
        $normal = fn (?string $x) => Str::lower(preg_replace('/\s+/', ' ', trim((string) $x)));

        return $normal($a) !== '' && $normal($a) === $normal($b);
    }

    /**
     * Buat kode 6 digit, simpan hash-nya, lalu kirim lewat email.
     * Kode lama untuk email + tujuan yang sama dinyatakan tidak berlaku.
     */
    private function buatDanKirimOtp(string $email, string $tujuan): string
    {
        return $this->otpService->buatDanKirim($email, $tujuan);
    }

    private function dataOtp(string $email, ?string $kode): array
    {
        $data = [
            'email' => $this->maskEmail($email),
            'berlaku_menit' => OtpService::BERLAKU_MENIT,
            'jeda_kirim_ulang_detik' => self::OTP_JEDA_KIRIM_ULANG_DETIK,
        ];

        // Hanya untuk pengembangan lokal (mailer "log"), TIDAK muncul di produksi
        if ($kode && app()->environment('local') && config('app.debug')) {
            $data['kode_debug'] = $kode;
        }

        return $data;
    }

    /**
     * Token reset tanpa tabel: terenkripsi dengan APP_KEY, berisi id, masa berlaku,
     * dan sidik password lama sehingga otomatis tidak berlaku setelah password diganti.
     */
    private function buatResetToken(Pengguna $pengguna): string
    {
        return Crypt::encryptString(json_encode([
            'id' => $pengguna->id,
            'exp' => now()->addMinutes(self::RESET_TOKEN_BERLAKU_MENIT)->timestamp,
            'sidik' => hash('sha256', $pengguna->password),
        ]));
    }

    private function bacaResetToken(string $token): ?Pengguna
    {
        try {
            $isi = json_decode(Crypt::decryptString($token), true);
        } catch (DecryptException) {
            return null;
        }

        if (! is_array($isi) || ($isi['exp'] ?? 0) < now()->timestamp) {
            return null;
        }

        $pengguna = Pengguna::where('id', $isi['id'] ?? 0)->where('peran', 'siswa')->where('aktif', true)->first();

        if (! $pengguna || ! hash_equals($isi['sidik'] ?? '', hash('sha256', $pengguna->password))) {
            return null;
        }

        return $pengguna;
    }

    private function maskEmail(string $email): string
    {
        [$nama, $domain] = array_pad(explode('@', $email, 2), 2, '');
        $panjang = strlen($nama);

        $tersamar = $panjang <= 2
            ? $nama.'****'
            : substr($nama, 0, 2).str_repeat('*', min(4, $panjang - 2));

        return $tersamar.'@'.$domain;
    }
}
