<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Auth\AkunSiswa;
use App\Models\SiswaBiodata;
use App\Services\ResetSandiSiswaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Autentikasi aplikasi mobile siswa (tanpa OTP):
 * login, daftar (langsung aktif), lupa password (tautan email), logout.
 */
class AuthApiController extends Controller
{
    public function __construct(private ResetSandiSiswaService $resetSandi) {}

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

        $pengguna = AkunSiswa::where('username', $input)
            ->orWhere('email', $input)
            ->first();

        if (! $pengguna || ! Hash::check($data['password'], $pengguna->password)) {
            RateLimiter::hit($kunci, 60);

            return $this->gagal('Username atau kata sandi yang dimasukkan tidak sesuai.', 401, 'login_gagal');
        }

        RateLimiter::clear($kunci);

        if (! $pengguna->aktif) {
            return $this->gagal('Akun Anda dinonaktifkan. Silakan hubungi administrator sekolah.', 403, 'akun_nonaktif');
        }

        $token = $pengguna->createToken($data['nama_perangkat'] ?? 'Perangkat tidak dikenal', ['siswa']);

        // Catat perangkat untuk layar "Sesi Aktif"
        DB::table('akun_perangkat_login')->insert([
            'akun_siswa_id' => $pengguna->id,
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
     * Nama + NISN harus cocok dengan data siswa di sekolah. Akun langsung aktif
     * (tanpa OTP). NISN yang sudah memiliki akun, aktif maupun dinonaktifkan
     * administrator, tidak dapat didaftarkan ulang.
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

        $akun = AkunSiswa::where('siswa_id', $siswa->id)->orWhere('username', $nisn)->first();

        if ($akun) {
            return $akun->aktif
                ? $this->gagal('Akun untuk NISN ini sudah terdaftar. Silakan login.', 422, 'register_gagal')
                : $this->gagal('Akun untuk NISN ini dinonaktifkan. Silakan hubungi administrator sekolah.', 422, 'register_gagal');
        }

        if (AkunSiswa::where('email', $email)->exists()) {
            return $this->gagal('Email ini sudah dipakai oleh akun lain.', 422, 'register_gagal');
        }

        $akun = AkunSiswa::create([
            'username' => $nisn,
            'email' => $email,
            'password' => Hash::make($data['password']),
            'aktif' => true,
            'siswa_id' => $siswa->id,
            'wajib_ganti_password' => false,
        ]);

        NotifikasiApiController::buat($akun->id, 'Akun berhasil dibuat', 'Selamat datang di JosBook. Akun Anda sudah aktif.');

        return response()->json([
            'pesan' => 'Pendaftaran berhasil. Silakan login.',
            'data' => [
                'nama' => $akun->nama,
                'nisn' => $akun->username,
            ],
        ], 201);
    }

    /**
     * POST /api/v1/lupa-password  (layar Lupa Kata Sandi)
     * Mengirim tautan email; kata sandi baru dibuat di halaman web tautan tersebut
     * (GET /siswa/reset-sandi). Tanpa OTP.
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

        $pengguna = AkunSiswa::where('email', $email)
            ->where('aktif', true)
            ->first();

        $tautan = null;
        if ($pengguna) {
            try {
                $tautan = $this->resetSandi->kirimTautan($pengguna);
            } catch (\Throwable $e) {
                Log::error('Gagal mengirim tautan reset kata sandi siswa: '.$e->getMessage());

                return $this->gagal('Gagal mengirim email. Coba lagi beberapa saat.', 503);
            }
        }

        $hasil = ['email' => $this->maskEmail($email)];

        // Hanya untuk pengembangan lokal (mailer "log"), TIDAK muncul di produksi
        if ($tautan && app()->environment('local') && config('app.debug')) {
            $hasil['tautan_debug'] = $tautan;
        }

        // Jawaban selalu sama agar email yang terdaftar tidak bisa ditebak
        return response()->json([
            'pesan' => 'Jika email terdaftar, tautan untuk membuat kata sandi baru telah dikirim.',
            'data' => $hasil,
        ]);
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
