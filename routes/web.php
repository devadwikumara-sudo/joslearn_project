<?php

use App\Http\Controllers\Admin\AlumniController;
use App\Http\Controllers\Admin\CekKualitasDataController;
use App\Http\Controllers\Admin\DashboardAdminController;
use App\Http\Controllers\Admin\DataGuruController;
use App\Http\Controllers\Admin\DataSiswaController;
use App\Http\Controllers\Admin\FormPendataanController;
use App\Http\Controllers\Admin\ImportBatchController;
use App\Http\Controllers\Admin\ImportEkskulAdminController;
use App\Http\Controllers\Admin\ImportGuruController;
use App\Http\Controllers\Admin\ImportKehadiranAdminController;
use App\Http\Controllers\Admin\ImportNilaiAdminController;
use App\Http\Controllers\Admin\KenaikanKelasController;
use App\Http\Controllers\Admin\LaporanController;
use App\Http\Controllers\Admin\LogAktivitasController;
use App\Http\Controllers\Admin\MasterKelasController;
use App\Http\Controllers\Admin\MutasiSiswaController;
use App\Http\Controllers\Admin\PdfCetakController;
use App\Http\Controllers\Admin\PelacakanAlumniController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\GantiPasswordController;
use App\Http\Controllers\Guru\BerandaGuruController;
use App\Http\Controllers\Guru\DataSiswaGuruController;
use App\Http\Controllers\Guru\ImportEkskulGuruController;
use App\Http\Controllers\Guru\ImportKetidakhadiranController;
use App\Http\Controllers\Guru\ImportNilaiGuruController;
use App\Http\Controllers\Guru\InputNilaiMapelController;
use Illuminate\Support\Facades\Route;

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
Route::post('/verifikasi-akun/kirim-ulang', [AuthController::class, 'kirimUlangOtp'])->name('verifikasi-akun.kirim-ulang');

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
    Route::get('/data-siswa/pdf-semua', [DataSiswaController::class, 'pdfAll'])->name('data-siswa.pdf-all');
    Route::post('/data-siswa/bulk-update', [DataSiswaController::class, 'bulkUpdate'])->name('data-siswa.bulk-update');
    Route::delete('/data-siswa/bulk-delete', [DataSiswaController::class, 'bulkDestroy'])->name('data-siswa.bulk-delete');
    Route::get('/data-siswa/create', [FormPendataanController::class, 'index'])->name('data-siswa.create');
    Route::get('/data-siswa/{id}/berkas/{jenis}', [DataSiswaController::class, 'berkas'])->name('data-siswa.berkas');
    Route::get('/data-siswa/{id}/foto', [DataSiswaController::class, 'foto'])->name('data-siswa.foto');
    Route::get('/data-siswa/{id}', [DataSiswaController::class, 'show'])->name('data-siswa.show');
    Route::get('/data-siswa/{id}/edit', [FormPendataanController::class, 'edit'])->name('data-siswa.edit');
    Route::get('/data-siswa/{id}/pdf', [PdfCetakController::class, 'single'])->name('data-siswa.pdf');
    Route::match(['get', 'post'], '/data-siswa/pdf-batch', [PdfCetakController::class, 'batch'])->name('data-siswa.pdf-batch');
    Route::get('/pdf/siswa/{id}', [PdfCetakController::class, 'single'])->name('pdf.siswa');
    Route::match(['get', 'post'], '/pdf/batch', [PdfCetakController::class, 'batch'])->name('pdf.batch');
    Route::post('/data-siswa', [FormPendataanController::class, 'store'])->name('data-siswa.store');
    Route::put('/data-siswa/{id}', [FormPendataanController::class, 'update'])->name('data-siswa.update');
    Route::delete('/data-siswa/{id}', [DataSiswaController::class, 'destroy'])->name('data-siswa.destroy');

    // Data Guru (data guru.png)
    Route::get('/data-guru', [DataGuruController::class, 'index'])->name('data-guru');
    Route::post('/data-guru', [DataGuruController::class, 'store'])->name('data-guru.store');
    Route::post('/data-guru/{id}/buat-akun', [DataGuruController::class, 'buatAkun'])->name('data-guru.buat-akun');
    Route::post('/data-guru/{id}/reset-sandi', [DataGuruController::class, 'resetSandi'])->name('data-guru.reset-sandi');
    Route::get('/data-guru/{id}/edit', [DataGuruController::class, 'edit'])->name('data-guru.edit');
    Route::put('/data-guru/{id}', [DataGuruController::class, 'update'])->name('data-guru.update');
    Route::delete('/data-guru/{id}', [DataGuruController::class, 'destroy'])->name('data-guru.destroy');

    // Import Fleksibel API (Universal Batch Save)
    Route::post('/import/save-batch', [ImportBatchController::class, 'saveBatch'])->name('import.save-batch');

    // Import Siswa
    Route::get('/import/siswa', [DataSiswaController::class, 'importView'])->name('import.siswa');

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
    Route::put('/alumni/{id}', [AlumniController::class, 'update'])->name('alumni.update');

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
    Route::get('/cek-kualitas-data/export', [CekKualitasDataController::class, 'index'])->name('cek-kualitas-data.export');

    // Log Aktivitas (log aktivitas.png)
    Route::get('/log-aktivitas', [LogAktivitasController::class, 'index'])->name('log-aktivitas');
    Route::get('/log-aktivitas/export', [LogAktivitasController::class, 'export'])->name('log-aktivitas.export');

    // Master Kelas & Mapel (master class.png)
    Route::get('/master-kelas', [MasterKelasController::class, 'index'])->name('master-kelas');
    Route::post('/master-kelas', [MasterKelasController::class, 'store'])->name('master-kelas.store');
    Route::delete('/master-kelas/{id}', [MasterKelasController::class, 'destroy'])->name('master-kelas.destroy');
    Route::delete('/master-mapel/{id}', [MasterKelasController::class, 'destroy'])->name('master-mapel.destroy');

    // Kenaikan Kelas — batalkan
    Route::post('/kenaikan-kelas/{id}/batalkan', [KenaikanKelasController::class, 'batalkan'])->name('kenaikan-kelas.batalkan');

    // Import Guru (admin)
    Route::get('/import/guru', [ImportGuruController::class, 'index'])->name('import.guru');

    // Tempat Sampah
    Route::get('/sampah', [SettingController::class, 'tempahSampah'])->name('sampah');
    Route::post('/sampah/pulihkan', [SettingController::class, 'pulihkan'])->name('sampah.pulihkan');
    Route::delete('/sampah/hapus-permanen', [SettingController::class, 'hapusPermanenSampah'])->name('sampah.hapus-permanen');

    // Setting (setting.png)
    Route::get('/setting', [SettingController::class, 'index'])->name('setting');
    Route::put('/setting', [SettingController::class, 'update'])->name('setting.update');
    Route::post('/setting/tahun-ajaran', [SettingController::class, 'tambahTahunAjaran'])->name('setting.tahun-ajaran.tambah');
    Route::post('/setting/tahun-ajaran/aktif', [SettingController::class, 'setTahunAjaranAktif'])->name('setting.tahun-ajaran.aktif');
    Route::delete('/setting/akun/{id}', [SettingController::class, 'hapusAkun'])->name('setting.akun.hapus');
});

// ─── AREA GURU (hanya guru) ───────────────────────────────────────────────────
Route::prefix('guru')->name('guru.')->middleware(['auth', 'role.web'])->group(function () {

    // Beranda Guru (beranda guru.png)
    Route::get('/beranda', [BerandaGuruController::class, 'index'])->name('beranda');

    // Data Siswa (data siswa.png - guru)
    Route::get('/data-siswa', [DataSiswaGuruController::class, 'index'])->name('data-siswa');
    Route::get('/data-siswa/export', [DataSiswaGuruController::class, 'export'])->name('data-siswa.export');
    Route::get('/data-siswa/pdf-semua', [DataSiswaGuruController::class, 'pdfAll'])->name('data-siswa.pdf-all');
    Route::post('/data-siswa/pdf-terpilih', [DataSiswaGuruController::class, 'pdfSelected'])->name('data-siswa.pdf-selected');
    Route::get('/data-siswa/{id}/pdf', [PdfCetakController::class, 'single'])->name('data-siswa.pdf');
    Route::get('/data-siswa/{id}/edit', [FormPendataanController::class, 'edit'])->name('data-siswa.edit');
    Route::put('/data-siswa/{id}', [FormPendataanController::class, 'update'])->name('data-siswa.update');
    Route::get('/data-siswa/{id}/foto', [DataSiswaController::class, 'foto'])->name('data-siswa.foto');
    Route::get('/data-siswa/{id}/berkas/{jenis}', [DataSiswaController::class, 'berkas'])->name('data-siswa.berkas');
    Route::get('/data-siswa/{id}', [DataSiswaGuruController::class, 'show'])->name('data-siswa.show');

    // Import Fleksibel API (Universal Batch Save - Guru)
    Route::post('/import/save-batch', [ImportBatchController::class, 'saveBatch'])->name('import.save-batch');

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

    // Kenaikan kelas wali
    Route::get('/kenaikan-kelas', [KenaikanKelasController::class, 'index'])->name('kenaikan-kelas');
    Route::post('/kenaikan-kelas/proses', [KenaikanKelasController::class, 'proses'])->name('kenaikan-kelas.proses');
    Route::post('/kenaikan-kelas/{id}/batalkan', [KenaikanKelasController::class, 'batalkan'])->name('kenaikan-kelas.batalkan');
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
