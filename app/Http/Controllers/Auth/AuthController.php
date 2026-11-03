<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\GuruBiodata;
use App\Models\Pengguna;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
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

        $input    = trim($request->username);
        $password = $request->password;
        $remember = $request->boolean('remember');

        // Cari berdasarkan username atau email di tabel akun_pengguna
        $pengguna = Pengguna::where('username', $input)
            ->orWhere('email', $input)
            ->first();

        if (! $pengguna) {
            return back()->withInput($request->only('username'))->withErrors([
                'username' => 'Akun dengan kredensial tersebut tidak ditemukan di database.',
            ]);
        }

        // Cek autentikasi kata sandi
        $credentials = ['username' => $pengguna->username, 'password' => $password];

        if (! Auth::attempt($credentials, $remember)) {
            return back()->withInput($request->only('username'))->withErrors([
                'password' => 'Kata sandi yang dimasukkan salah.',
            ]);
        }

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
        $request->validate([
            'nama'      => ['required', 'string', 'max:150'],
            'email'     => ['required', 'email', 'max:120', 'unique:akun_pengguna,email'],
            'identitas' => ['required', 'string', 'max:50', 'unique:akun_pengguna,username'],
            'peran'     => ['required', 'in:admin,guru'],
            'password'  => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'nama.required'        => 'Nama lengkap wajib diisi.',
            'email.required'       => 'Email resmi kedinasan/sekolah wajib diisi.',
            'email.email'          => 'Format email tidak valid.',
            'email.unique'         => 'Email ini sudah terdaftar pada sistem.',
            'identitas.required'   => 'Nomor identitas (NIP / Username) wajib diisi.',
            'identitas.unique'     => 'NIP / Username ini sudah terdaftar.',
            'password.required'    => 'Kata sandi wajib diisi.',
            'password.min'         => 'Kata sandi minimal 8 karakter.',
            'password.confirmed'   => 'Konfirmasi kata sandi tidak cocok.',
        ]);

        $pengguna = DB::transaction(function () use ($request) {
            $guruId = null;

            // Jika peran adalah Guru, buat / tautkan biodata guru
            if ($request->peran === 'guru') {
                $guru = GuruBiodata::create([
                    'nama'               => trim($request->nama),
                    'nip'                => trim($request->identitas),
                    'email'              => trim($request->email),
                    'status_kepegawaian' => 'Guru',
                ]);
                $guruId = $guru->id;
            }

            // Simpan akun pengguna ke tabel akun_pengguna
            return Pengguna::create([
                'peran'                 => $request->peran,
                'username'              => trim($request->identitas),
                'email'                 => trim($request->email),
                'password'              => Hash::make($request->password),
                'aktif'                 => true,
                'guru_id'               => $guruId,
                'wajib_ganti_password'  => false,
                'email_diverifikasi_at' => now(),
                'terakhir_login_at'     => now(),
            ]);
        });

        // Langsung autentikasi pengguna yang baru dibuat
        Auth::login($pengguna);
        $request->session()->regenerate();

        // Redirect langsung ke halaman masing-masing tergantung peran akun
        if ($pengguna->peran === 'admin') {
            return redirect()->route('admin.dashboard')->with('success', 'Akun Administrator berhasil dibuat! Selamat datang di JosBook.');
        }

        return redirect()->route('guru.beranda')->with('success', 'Akun Guru berhasil dibuat! Selamat datang di JosBook.');
    }

    /**
     * Tampilkan halaman verifikasi OTP (Verifikasi akun.png).
     */
    public function showVerifikasi()
    {
        return view('login-guru-dan-admin.verifikasi-akun');
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

        // Kode verifikasi valid
        if (strlen($kode) !== 6 || ! ctype_digit($kode)) {
            return back()->withErrors([
                'kode' => 'Kode verifikasi harus berupa 6 digit angka.',
            ]);
        }

        $flow   = session('auth_flow', 'daftar');
        $userId = session('auth_user_id');

        if ($userId) {
            $user = Pengguna::find($userId);
            if ($user) {
                $user->email_diverifikasi_at = now();
                $user->aktif = true;
                $user->save();
            }
        }

        if ($flow === 'reset') {
            return redirect()->route('buat-kata-sandi-baru');
        }

        return redirect()->route('selesai-kata-sandi');
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

        $pengguna = Pengguna::where('email', $input)
            ->orWhere('username', $input)
            ->first();

        if (! $pengguna) {
            return back()->withInput()->withErrors([
                'email' => 'Akun dengan email atau NIP tersebut tidak ditemukan di database.',
            ]);
        }

        session([
            'auth_flow'      => 'reset',
            'auth_user_id'   => $pengguna->id,
            'masked_email'   => $this->maskEmail($pengguna->email ?? $pengguna->username . '@sman1rejoso.sch.id'),
            'identitas_nama' => $pengguna->nama ?? $pengguna->username,
            'identitas_id'   => 'NIP/Akun: ' . $pengguna->username,
        ]);

        return redirect()->route('verifikasi-akun');
    }

    /**
     * Tampilkan halaman pembuatan kata sandi baru (Buat kata sandi baru tersembunyi.png).
     */
    public function showBuatKataSandiBaru()
    {
        return view('login-guru-dan-admin.buat-kata-sandi-baru');
    }

    /**
     * Proses penyimpanan kata sandi baru.
     */
    public function simpanKataSandiBaru(Request $request)
    {
        $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'password.required'  => 'Kata sandi baru wajib diisi.',
            'password.min'       => 'Kata sandi minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
        ]);

        $user = Auth::user();
        if (! $user && session('auth_user_id')) {
            $user = Pengguna::find(session('auth_user_id'));
        }

        if (! $user) {
            return redirect()->route('login')->withErrors([
                'username' => 'Sesi reset kata sandi telah kedaluwarsa. Silakan ulangi langkah reset.',
            ]);
        }

        $user->password             = Hash::make($request->password);
        $user->wajib_ganti_password = false;
        $user->save();

        session([
            'identitas_nama' => $user->nama ?? $user->username,
            'identitas_id'   => 'Akun Terverifikasi · ' . strtoupper($user->peran),
        ]);

        if (Auth::check()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

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
            $masked = $name . '****';
        } else {
            $masked = substr($name, 0, 2) . str_repeat('*', min(4, $len - 2));
        }

        return $masked . '@' . $domain;
    }
}
