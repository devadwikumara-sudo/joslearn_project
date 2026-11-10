<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Admin\CatatanAktivitas;
use App\Models\Auth\Pengguna;
use App\Models\Guru\GuruBiodata;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(private OtpService $otpService) {}

    /**
     * Tampilkan form login (Login.png).
     */
    public function showLogin()
    {
        if (Auth::check()) {
            return Auth::user()->peran === 'admin'
                ? redirect()->route('admin.dashboard')
                : redirect()->route('guru.beranda');
        }

        return view('login-guru-dan-admin.login');
    }

    /**
     * Proses autentikasi login pengguna (Admin atau Guru) terhubung ke database.
     */
    public function login(Request $request)
    {
        $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'username.required' => 'NIS/NIP, Username atau Email wajib diisi.',
            'password.required' => 'Kata sandi wajib diisi.',
        ]);

        $input = trim($request->username);
        $password = $request->password;
        $remember = $request->boolean('remember');
        $rateLimitKey = 'login-web:'.Str::lower($input).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            return back()->withInput($request->only('username'))->withErrors([
                'username' => 'Username atau kata sandi salah',
            ]);
        }

        $pengguna = Pengguna::where('username', $input)
            ->orWhere('email', $input)
            ->first();

        $autentikasiBerhasil = $pengguna
            && Auth::attempt(['username' => $pengguna->username, 'password' => $password], $remember);

        if (! $autentikasiBerhasil) {
            RateLimiter::hit($rateLimitKey, 60);

            return back()->withInput($request->only('username'))->withErrors([
                'username' => 'Username atau kata sandi salah',
            ]);
        }

        RateLimiter::clear($rateLimitKey);
        $user = Auth::user();

        // Cek status aktif akun
        if (! $user->aktif) {
            Auth::logout();

            return back()->withInput($request->only('username'))->withErrors([
                'username' => 'Akun Anda dinonaktifkan. Silakan hubungi administrator sekolah.',
            ]);
        }

        // Hak akses web hanya untuk admin dan guru
        if (! in_array($user->peran, ['admin', 'guru'])) {
            Auth::logout();

            return back()->withInput($request->only('username'))->withErrors([
                'username' => 'Akses portal web khusus untuk Guru dan Administrator. Siswa dapat menggunakan aplikasi mobile JosBook.',
            ]);
        }

        // Perbarui waktu login terakhir di database
        $user->terakhir_login_at = now();
        $user->save();

        $request->session()->regenerate();

        // Jika ada kewajiban ganti password perdana
        if ($user->wajib_ganti_password) {
            return redirect()->route('buat-kata-sandi-baru');
        }

        // Arahkan ke halaman masing-masing sesuai peran akun yang login
        if ($user->peran === 'admin') {
            return redirect()->route('admin.dashboard');
        }

        return redirect()->route('guru.beranda');
    }

    /**
     * Logout pengguna.
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Tampilkan halaman pendaftaran akun (DAFTAR (1).png).
     */
    public function showDaftar()
    {
        if (Auth::check()) {
            return Auth::user()->peran === 'admin'
                ? redirect()->route('admin.dashboard')
                : redirect()->route('guru.beranda');
        }

        return view('login-guru-dan-admin.daftar');
    }

    /**
     * Proses pendaftaran akun langsung ke database dan diarahkan sesuai peran.
     */
    public function daftar(Request $request)
    {
        $identitasRules = ['required', 'string', 'max:50'];
        if ($request->input('peran') === 'admin') {
            $identitasRules[] = 'unique:akun_pengguna,username';
        }

        $request->validate([
            'nama' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:120', 'unique:akun_pengguna,email'],
            'identitas' => $identitasRules,
            'peran' => ['required', 'in:admin,guru'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'nama.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Email resmi kedinasan/sekolah wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email ini sudah terdaftar pada sistem.',
            'identitas.required' => 'Nomor identitas (NIP / Username) wajib diisi.',
            'identitas.unique' => 'NIP / Username ini sudah terdaftar.',
            'password.required' => 'Kata sandi wajib diisi.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
        ]);

        if ($request->peran === 'admin') {
            $kodeRahasia = config('auth.admin_registration_code');
            $kodeDiberikan = $request->input('admin_registration_code');
            $kodeCocok = is_string($kodeRahasia)
                && $kodeRahasia !== ''
                && is_string($kodeDiberikan)
                && hash_equals($kodeRahasia, $kodeDiberikan);

            if (! $kodeCocok) {
                $pesan = is_string($kodeRahasia) && $kodeRahasia !== ''
                    ? 'Kode pendaftaran administrator tidak valid.'
                    : 'Pendaftaran administrator belum tersedia karena kode rahasia belum dikonfigurasi.';

                return back()
                    ->withInput($request->except('admin_registration_code'))
                    ->withErrors(['admin_registration_code' => $pesan]);
            }
        }

        $email = Str::lower(trim($request->email));
        $pengguna = DB::transaction(function () use ($request, $email) {
            $guruId = null;

            if ($request->peran === 'guru') {
                $nip = trim($request->identitas);
                $guru = GuruBiodata::where('nip', $nip)->lockForUpdate()->first();
                if (! $guru) {
                    throw ValidationException::withMessages([
                        'identitas' => 'NIP belum terdaftar, hubungi admin',
                    ]);
                }

                $akunSudahAda = Pengguna::where(function ($query) use ($guru, $nip) {
                    $query->where('guru_id', $guru->id)->orWhere('username', $nip);
                })->exists();
                if ($akunSudahAda) {
                    throw ValidationException::withMessages([
                        'identitas' => 'NIP ini sudah memiliki akun.',
                    ]);
                }

                $guruId = $guru->id;
            }

            // Simpan akun pengguna ke tabel akun_pengguna
            return Pengguna::create([
                'peran' => $request->peran,
                'username' => trim($request->identitas),
                'email' => $email,
                'password' => Hash::make($request->password),
                'aktif' => false,
                'guru_id' => $guruId,
                'wajib_ganti_password' => false,
                'email_diverifikasi_at' => null,
            ]);
        });

        session([
            'auth_flow' => 'daftar',
            'auth_user_id' => $pengguna->id,
            'masked_email' => $this->maskEmail($pengguna->email),
            'identitas_nama' => $pengguna->nama,
            'identitas_id' => 'Akun: '.$pengguna->username,
        ]);

        try {
            $this->otpService->buatDanKirim($pengguna->email, 'daftar');
        } catch (\Throwable $e) {
            Log::error('Gagal mengirim OTP pendaftaran web: '.$e->getMessage());

            return redirect()->route('verifikasi-akun')->withErrors([
                'kode' => 'Gagal mengirim email verifikasi. Tunggu sebentar lalu kirim ulang kode.',
            ]);
        }

        return redirect()->route('verifikasi-akun');
    }

    /**
     * Tampilkan halaman verifikasi OTP (Verifikasi akun.png).
     */
    public function showVerifikasi()
    {
        $pengguna = $this->penggunaDalamAlur();
        if (! $pengguna) {
            return redirect()->route('daftar')->withErrors([
                'email' => 'Sesi verifikasi tidak valid. Silakan mulai kembali.',
            ]);
        }

        if ($this->resetOtpTervalidasi()) {
            return redirect()->route('buat-kata-sandi-baru');
        }

        return view('login-guru-dan-admin.verifikasi-akun', [
            'sisaKirimUlang' => $this->otpService->detikSampaiKirimUlang(
                $pengguna->email,
                $this->tujuanOtp(),
                45
            ),
        ]);
    }

    /**
     * Proses verifikasi OTP.
     */
    public function verifikasi(Request $request)
    {
        $request->validate([
            'kode' => ['required', 'string'],
        ], [
            'kode.required' => 'Masukkan 6 digit kode keamanan.',
        ]);

        $kode = trim($request->kode);
        if (strlen($kode) !== 6 || ! ctype_digit($kode)) {
            return back()->withErrors(['kode' => 'Kode verifikasi harus berupa 6 digit angka.']);
        }

        $pengguna = $this->penggunaDalamAlur();
        if (! $pengguna) {
            return redirect()->route('reset-sandi')->withErrors([
                'email' => 'Sesi verifikasi tidak valid. Silakan mulai kembali.',
            ]);
        }

        $hasil = $this->otpService->verifikasi($pengguna->email, $this->tujuanOtp(), $kode);
        if ($hasil === OtpService::TIDAK_VALID) {
            return back()->withErrors(['kode' => 'Kode verifikasi tidak valid atau sudah kedaluwarsa. Kirim ulang kode baru.']);
        }
        if ($hasil === OtpService::TERLALU_BANYAK) {
            return back()->withErrors(['kode' => 'Terlalu banyak percobaan. Silakan kirim ulang kode baru.']);
        }
        if ($hasil === OtpService::SALAH) {
            return back()->withErrors(['kode' => 'Kode verifikasi salah.']);
        }

        if (session('auth_flow') === 'reset') {
            session([
                'auth_reset_verified' => true,
                'auth_reset_user_id' => $pengguna->id,
                'auth_reset_verified_until' => now()->addMinutes(15)->timestamp,
            ]);

            return redirect()->route('buat-kata-sandi-baru');
        }

        DB::transaction(function () use ($pengguna, $request): void {
            $pengguna->email_diverifikasi_at = now();
            $pengguna->aktif = true;
            $pengguna->terakhir_login_at = now();
            $pengguna->save();

            CatatanAktivitas::create([
                'pengguna_id' => $pengguna->id,
                'peran' => $pengguna->peran,
                'aksi' => 'verifikasi_pendaftaran',
                'keterangan' => 'Memverifikasi pendaftaran akun melalui OTP email.',
                'objek_tipe' => 'akun',
                'objek_id' => $pengguna->id,
                'alamat_ip' => $request->ip(),
            ]);
        });

        Auth::login($pengguna);
        $request->session()->regenerate();
        $this->hapusKonteksOtp();

        return $pengguna->peran === 'admin'
            ? redirect()->route('admin.dashboard')->with('success', 'Akun Administrator berhasil diverifikasi. Selamat datang di JosBook.')
            : redirect()->route('guru.beranda')->with('success', 'Akun Guru berhasil diverifikasi. Selamat datang di JosBook.');
    }

    /**
     * Tampilkan halaman reset kata sandi (Reset sandi.png).
     */
    public function showResetSandi()
    {
        return view('login-guru-dan-admin.reset-sandi');
    }

    /**
     * Proses permintaan kirim kode verifikasi reset sandi.
     */
    public function kirimResetSandi(Request $request)
    {
        $request->validate([
            'email' => ['required', 'string'],
        ], [
            'email.required' => 'Email atau NIP akun wajib diisi.',
        ]);

        $input = trim($request->email);

        $pengguna = Pengguna::where(function ($query) use ($input) {
            $query->whereRaw('LOWER(email) = ?', [Str::lower($input)])
                ->orWhere('username', $input);
        })
            ->whereIn('peran', ['admin', 'guru'])
            ->where('aktif', true)
            ->first();

        if (! $pengguna) {
            return back()->withInput()->withErrors([
                'email' => 'Akun dengan email atau NIP tersebut tidak ditemukan di database.',
            ]);
        }

        $email = Str::lower($pengguna->email);
        session()->forget(['auth_reset_verified', 'auth_reset_user_id', 'auth_reset_verified_until']);
        session([
            'auth_flow' => 'reset',
            'auth_user_id' => $pengguna->id,
            'masked_email' => $this->maskEmail($email),
            'identitas_nama' => $pengguna->nama ?? $pengguna->username,
            'identitas_id' => 'NIP/Akun: '.$pengguna->username,
        ]);

        try {
            $this->otpService->buatDanKirim($email, 'lupa_password');
        } catch (\Throwable $e) {
            Log::error('Gagal mengirim OTP reset sandi web: '.$e->getMessage());

            return redirect()->route('verifikasi-akun')->withErrors([
                'kode' => 'Gagal mengirim email verifikasi. Tunggu sebentar lalu kirim ulang kode.',
            ]);
        }

        return redirect()->route('verifikasi-akun');
    }

    public function kirimUlangOtp(Request $request)
    {
        $pengguna = $this->penggunaDalamAlur();
        if (! $pengguna) {
            return redirect()->route('reset-sandi')->withErrors([
                'email' => 'Sesi verifikasi tidak valid. Silakan mulai kembali.',
            ]);
        }

        if ($this->resetOtpTervalidasi()) {
            return redirect()->route('buat-kata-sandi-baru');
        }

        $jeda = $this->otpService->detikSampaiKirimUlang($pengguna->email, $this->tujuanOtp(), 45);
        if ($jeda > 0) {
            return back()->with('otp_sisa_detik', $jeda)->withErrors([
                'kode' => "Tunggu {$jeda} detik sebelum mengirim ulang kode.",
            ]);
        }

        try {
            $this->otpService->buatDanKirim($pengguna->email, $this->tujuanOtp());
        } catch (\Throwable $e) {
            Log::error('Gagal mengirim ulang OTP web: '.$e->getMessage());

            return back()->withErrors(['kode' => 'Gagal mengirim email verifikasi. Coba lagi beberapa saat.']);
        }

        return redirect()->route('verifikasi-akun');
    }

    /**
     * Tampilkan halaman pembuatan kata sandi baru (Buat kata sandi baru tersembunyi.png).
     */
    public function showBuatKataSandiBaru()
    {
        if (! $this->bolehBuatKataSandiBaru()) {
            return redirect()->route('reset-sandi')->withErrors([
                'email' => 'Verifikasi OTP reset sandi terlebih dahulu.',
            ]);
        }

        return view('login-guru-dan-admin.buat-kata-sandi-baru');
    }

    /**
     * Proses penyimpanan kata sandi baru.
     */
    public function simpanKataSandiBaru(Request $request)
    {
        if (! $this->bolehBuatKataSandiBaru()) {
            return redirect()->route('reset-sandi')->withErrors([
                'email' => 'Verifikasi OTP reset sandi terlebih dahulu.',
            ]);
        }

        $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'password.required' => 'Kata sandi baru wajib diisi.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
        ]);

        $user = Auth::user();
        if (! $user) {
            $user = Pengguna::find(session('auth_reset_user_id'));
        }

        if (! $user) {
            return redirect()->route('login')->withErrors([
                'username' => 'Sesi reset kata sandi telah kedaluwarsa. Silakan ulangi langkah reset.',
            ]);
        }

        DB::transaction(function () use ($user, $request): void {
            $user->password = Hash::make($request->password);
            $user->wajib_ganti_password = false;
            $user->save();

            CatatanAktivitas::create([
                'pengguna_id' => $user->id,
                'peran' => $user->peran,
                'aksi' => 'reset_password',
                'keterangan' => 'Mengubah kata sandi setelah verifikasi OTP email.',
                'objek_tipe' => 'akun',
                'objek_id' => $user->id,
                'alamat_ip' => $request->ip(),
            ]);
        });

        if (Auth::check()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        $this->hapusKonteksOtp();
        session([
            'identitas_nama' => $user->nama ?? $user->username,
            'identitas_id' => 'Akun Terverifikasi · '.strtoupper($user->peran),
        ]);

        return redirect()->route('selesai-kata-sandi');
    }

    /**
     * Tampilkan halaman selesai ubah kata sandi (selesai kata sandi.png).
     */
    public function showSelesaiKataSandi()
    {
        return view('login-guru-dan-admin.selesai-kata-sandi');
    }

    /**
     * Helper untuk sensor tampilan email (contoh: gu****@domain.com).
     */
    private function maskEmail(?string $email): string
    {
        if (! $email || ! str_contains($email, '@')) {
            return 'guru@sman1rejoso.sch.id';
        }

        [$name, $domain] = explode('@', $email, 2);
        $len = strlen($name);
        if ($len <= 2) {
            $masked = $name.'****';
        } else {
            $masked = substr($name, 0, 2).str_repeat('*', min(4, $len - 2));
        }

        return $masked.'@'.$domain;
    }

    private function tujuanOtp(): string
    {
        return session('auth_flow') === 'reset' ? 'lupa_password' : 'daftar';
    }

    private function penggunaDalamAlur(): ?Pengguna
    {
        $pengguna = Pengguna::find(session('auth_user_id'));
        if (! $pengguna) {
            return null;
        }

        if (session('auth_flow') === 'reset') {
            return $pengguna->aktif && in_array($pengguna->peran, ['admin', 'guru'], true)
                ? $pengguna
                : null;
        }

        return session('auth_flow') === 'daftar' && ! $pengguna->aktif
            ? $pengguna
            : null;
    }

    private function resetOtpTervalidasi(): bool
    {
        return session('auth_reset_verified') === true
            && (int) session('auth_reset_user_id') === (int) session('auth_user_id')
            && (int) session('auth_reset_verified_until') >= now()->timestamp;
    }

    private function bolehBuatKataSandiBaru(): bool
    {
        if (Auth::check()) {
            return Auth::user()->wajib_ganti_password;
        }

        if (! $this->resetOtpTervalidasi()) {
            return false;
        }

        $pengguna = Pengguna::find(session('auth_reset_user_id'));

        return $pengguna !== null
            && $pengguna->aktif
            && in_array($pengguna->peran, ['admin', 'guru'], true);
    }

    private function hapusKonteksOtp(): void
    {
        session()->forget([
            'auth_flow',
            'auth_user_id',
            'auth_reset_verified',
            'auth_reset_user_id',
            'auth_reset_verified_until',
            'masked_email',
            'identitas_nama',
            'identitas_id',
        ]);
    }
}
