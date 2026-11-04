<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\GantiPasswordController;
use App\Http\Controllers\Admin\DashboardAdminController;
use App\Http\Controllers\Admin\DataSiswaController;
use App\Http\Controllers\Admin\DataGuruController;
use App\Http\Controllers\Admin\ImportNilaiAdminController;
use App\Http\Controllers\Admin\ImportEkskulAdminController;
use App\Http\Controllers\Admin\ImportKehadiranAdminController;
use App\Http\Controllers\Admin\KenaikanKelasController;
use App\Http\Controllers\Admin\MutasiSiswaController;
use App\Http\Controllers\Admin\AlumniController;
use App\Http\Controllers\Admin\LaporanController;
use App\Http\Controllers\Admin\CekKualitasDataController;
use App\Http\Controllers\Admin\LogAktivitasController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\FormPendataanController;
use App\Http\Controllers\Admin\PelacakanAlumniController;
use App\Http\Controllers\Admin\MasterKelasController;
use App\Http\Controllers\Guru\BerandaGuruController;
use App\Http\Controllers\Guru\DataSiswaGuruController;
use App\Http\Controllers\Guru\ImportNilaiGuruController;
use App\Http\Controllers\Guru\ImportEkskulGuruController;
use App\Http\Controllers\Guru\ImportKetidakhadiranController;
use App\Http\Controllers\Guru\InputNilaiMapelController;

// ─── ALUR LOGIN & AUTENTIKASI GURU & ADMIN ───────────────────────────────────

// 1. Halaman Login (Login.png)
Route::get('/login', [AuthController::class, 'showLogin'])->name('login')->middleware('guest');
Route::post('/login', [AuthController::class, 'login'])->name('login.post')->middleware('guest');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// 2. Halaman Pendaftaran Akun (DAFTAR (1).png)
Route::get('/daftar', [AuthController::class, 'showDaftar'])->name('daftar')->middleware('guest');
Route::post('/daftar', [AuthController::class, 'daftar'])->name('daftar.post')->middleware('guest');

// 3. Halaman Verifikasi Akun OTP (Verifikasi akun.png)
Route::get('/verifikasi-akun', [AuthController::class, 'showVerifikasi'])->name('verifikasi-akun');
Route::post('/verifikasi-akun', [AuthController::class, 'verifikasi'])->name('verifikasi-akun.post');

// 4. Halaman Reset Sandi (Reset sandi.png)
Route::get('/reset-sandi', [AuthController::class, 'showResetSandi'])->name('reset-sandi')->middleware('guest');
Route::post('/reset-sandi', [AuthController::class, 'kirimResetSandi'])->name('reset-sandi.post')->middleware('guest');

// 5. Halaman Buat Kata Sandi Baru (Buat kata sandi baru tersembunyi.png)
Route::get('/buat-kata-sandi-baru', [AuthController::class, 'showBuatKataSandiBaru'])->name('buat-kata-sandi-baru');
Route::post('/buat-kata-sandi-baru', [AuthController::class, 'simpanKataSandiBaru'])->name('buat-kata-sandi-baru.post');

// 6. Halaman Selesai Kata Sandi (selesai kata sandi.png)
Route::get('/selesai-kata-sandi', [AuthController::class, 'showSelesaiKataSandi'])->name('selesai-kata-sandi');

// Ganti password (wajib ganti pertama login)
Route::middleware(['auth'])->group(function () {
    Route::get('/ganti-password', [GantiPasswordController::class, 'show'])->name('ganti-password');
    Route::post('/ganti-password', [GantiPasswordController::class, 'update'])->name('ganti-password.update');
});

// ─── ROOT REDIRECT ────────────────────────────────────────────────────────────
Route::get('/', function () {
    if (auth()->check()) {
        $peran = auth()->user()->peran;
        return $peran === 'admin'
            ? redirect()->route('admin.dashboard')
            : redirect()->route('guru.beranda');
    }
    return redirect()->route('login');
});

// ─── AREA ADMIN (hanya admin) ─────────────────────────────────────────────────
Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin.only'])->group(function () {

    // Dashboard Admin (dashboard admin.png)
    Route::get('/dashboard', [DashboardAdminController::class, 'index'])->name('dashboard');

    // Form Pendataan (form pendataan.png)
    Route::get('/form-pendataan', [FormPendataanController::class, 'index'])->name('form-pendataan');
    Route::post('/form-pendataan', [FormPendataanController::class, 'store'])->name('form-pendataan.store');
    Route::get('/form-pendataan/{id}/edit', [FormPendataanController::class, 'edit'])->name('form-pendataan.edit');
    Route::put('/form-pendataan/{id}', [FormPendataanController::class, 'update'])->name('form-pendataan.update');

    // Data Siswa (data siswa.png)
    Route::get('/data-siswa', [DataSiswaController::class, 'index'])->name('data-siswa');
    Route::get('/data-siswa/export', [DataSiswaController::class, 'export'])->name('data-siswa.export');
    Route::get('/data-siswa/create', [FormPendataanController::class, 'index'])->name('data-siswa.create');
    Route::get('/data-siswa/{id}', [DataSiswaController::class, 'show'])->name('data-siswa.show');
    Route::get('/data-siswa/{id}/edit', [FormPendataanController::class, 'edit'])->name('data-siswa.edit');
    Route::get('/data-siswa/{id}/pdf', [DataSiswaController::class, 'pdf'])->name('data-siswa.pdf');
    Route::post('/data-siswa', [DataSiswaController::class, 'store'])->name('data-siswa.store');
    Route::put('/data-siswa/{id}', [DataSiswaController::class, 'update'])->name('data-siswa.update');

    // Data Guru (data guru.png)
    Route::get('/data-guru', [DataGuruController::class, 'index'])->name('data-guru');
    Route::post('/data-guru', [DataGuruController::class, 'store'])->name('data-guru.store');
    Route::get('/data-guru/{id}/edit', [DataGuruController::class, 'edit'])->name('data-guru.edit');
    Route::put('/data-guru/{id}', [DataGuruController::class, 'update'])->name('data-guru.update');
    Route::delete('/data-guru/{id}', [DataGuruController::class, 'destroy'])->name('data-guru.destroy');

    // Import Nilai (import nilai.png)
    Route::get('/import/nilai', [ImportNilaiAdminController::class, 'index'])->name('import.nilai');
    Route::post('/import/nilai', [ImportNilaiAdminController::class, 'store'])->name('import.nilai.store');
    Route::post('/import/nilai/upload', [ImportNilaiAdminController::class, 'store'])->name('import.nilai.upload');
    Route::get('/import/nilai/template', [ImportNilaiAdminController::class, 'template'])->name('import.nilai.template');
    Route::post('/import/nilai/confirm', [ImportNilaiAdminController::class, 'confirm'])->name('import.nilai.confirm');

    // Import Ekskul (import ekskul.png)
    Route::get('/import/ekskul', [ImportEkskulAdminController::class, 'index'])->name('import.ekskul');
    Route::post('/import/ekskul', [ImportEkskulAdminController::class, 'store'])->name('import.ekskul.store');
    Route::post('/import/ekskul/upload', [ImportEkskulAdminController::class, 'store'])->name('import.ekskul.upload');
    Route::get('/import/ekskul/template', [ImportEkskulAdminController::class, 'template'])->name('import.ekskul.template');

    // Import Kehadiran (import kehadiran.png)
    Route::get('/import/kehadiran', [ImportKehadiranAdminController::class, 'index'])->name('import.kehadiran');
    Route::post('/import/kehadiran', [ImportKehadiranAdminController::class, 'store'])->name('import.kehadiran.store');
    Route::post('/import/kehadiran/upload', [ImportKehadiranAdminController::class, 'store'])->name('import.kehadiran.upload');
    Route::get('/import/kehadiran/template', [ImportKehadiranAdminController::class, 'template'])->name('import.kehadiran.template');

    // Kenaikan Kelas (kenaikan kelas.png)
    Route::get('/kenaikan-kelas', [KenaikanKelasController::class, 'index'])->name('kenaikan-kelas');
    Route::post('/kenaikan-kelas/proses', [KenaikanKelasController::class, 'proses'])->name('kenaikan-kelas.proses');

    // Mutasi Siswa (mutasi siswa.png)
    Route::get('/mutasi-siswa', [MutasiSiswaController::class, 'index'])->name('mutasi-siswa');
    Route::post('/mutasi-siswa', [MutasiSiswaController::class, 'store'])->name('mutasi-siswa.store');

    // Alumni & Kontrol (alumni dan kontrol.png)
    Route::get('/alumni', [AlumniController::class, 'index'])->name('alumni');
    Route::get('/alumni/export', [AlumniController::class, 'export'])->name('alumni.export');
    Route::get('/alumni/{id}', [AlumniController::class, 'show'])->name('alumni.show');

    // Pelacakan Alumni (pelacakan alumni.png)
    Route::get('/pelacakan-alumni', [PelacakanAlumniController::class, 'index'])->name('pelacakan-alumni');
    Route::post('/pelacakan-alumni', [PelacakanAlumniController::class, 'store'])->name('pelacakan-alumni.store');
    Route::delete('/pelacakan-alumni/{id}', [PelacakanAlumniController::class, 'destroy'])->name('pelacakan-alumni.destroy');

    // Laporan (laporan.png)
    Route::get('/laporan', [LaporanController::class, 'index'])->name('laporan');
    Route::match(['get', 'post'], '/laporan/cetak', [LaporanController::class, 'cetak'])->name('laporan.cetak');
    Route::get('/laporan/pdf', [LaporanController::class, 'cetak'])->name('laporan.pdf');

    // Cek Kualitas Data (cek kualitas data.png)
    Route::get('/cek-kualitas-data', [CekKualitasDataController::class, 'index'])->name('cek-kualitas-data');

    // Log Aktivitas (log aktivitas.png)
    Route::get('/log-aktivitas', [LogAktivitasController::class, 'index'])->name('log-aktivitas');

    // Master Kelas & Mapel (master class.png)
    Route::get('/master-kelas', [MasterKelasController::class, 'index'])->name('master-kelas');
    Route::post('/master-kelas', [MasterKelasController::class, 'store'])->name('master-kelas.store');
    Route::delete('/master-kelas/{id}', [MasterKelasController::class, 'destroy'])->name('master-kelas.destroy');

    // Tempat Sampah (direct link)
    Route::get('/sampah', [SettingController::class, 'index'])->name('sampah');

    // Setting (setting.png)
    Route::get('/setting', [SettingController::class, 'index'])->name('setting');
    Route::put('/setting', [SettingController::class, 'update'])->name('setting.update');
    Route::delete('/setting/akun/{id}', [SettingController::class, 'hapusAkun'])->name('setting.akun.hapus');
});

// ─── AREA GURU (hanya guru) ───────────────────────────────────────────────────
Route::prefix('guru')->name('guru.')->middleware(['auth', 'role.web'])->group(function () {

    // Beranda Guru (beranda guru.png)
    Route::get('/beranda', [BerandaGuruController::class, 'index'])->name('beranda');

    // Data Siswa (data siswa.png - guru)
    Route::get('/data-siswa', [DataSiswaGuruController::class, 'index'])->name('data-siswa');
    Route::get('/data-siswa/{id}', [DataSiswaGuruController::class, 'show'])->name('data-siswa.show');

    // Import Nilai (import nilai.png - guru)
    Route::get('/import-nilai', [ImportNilaiGuruController::class, 'index'])->name('import-nilai');
    Route::post('/import-nilai', [ImportNilaiGuruController::class, 'store'])->name('import-nilai.store');

    // Import Ekskul (import ekskul.png - guru)
    Route::get('/import-ekskul', [ImportEkskulGuruController::class, 'index'])->name('import-ekskul');
    Route::post('/import-ekskul', [ImportEkskulGuruController::class, 'store'])->name('import-ekskul.store');

    // Import Ketidakhadiran (import ketidakhadiraan.png - guru)
    Route::get('/import-ketidakhadiran', [ImportKetidakhadiranController::class, 'index'])->name('import-ketidakhadiran');
    Route::post('/import-ketidakhadiran', [ImportKetidakhadiranController::class, 'store'])->name('import-ketidakhadiran.store');

    // Input Nilai Mapel (input nilai mapel.png - guru)
    Route::get('/input-nilai-mapel', [InputNilaiMapelController::class, 'index'])->name('input-nilai-mapel');
    Route::post('/input-nilai-mapel', [InputNilaiMapelController::class, 'store'])->name('input-nilai-mapel.store');
});

// ─── DASHBOARD LAMA (backward compat redirect) ───────────────────────────────
Route::middleware(['auth', 'role.web'])->group(function () {
    Route::get('/dashboard', function () {
        $peran = auth()->user()->peran;
        return $peran === 'admin'
            ? redirect()->route('admin.dashboard')
            : redirect()->route('guru.beranda');
    })->name('dashboard');
});
