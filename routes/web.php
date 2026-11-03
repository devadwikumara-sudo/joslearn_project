<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\GantiPasswordController;
use App\Http\Controllers\DashboardController;

// ─── ALUR LOGIN & AUTENTIKASI GURU & ADMIN (project/web/login guru dan admin) ───

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

// Kompatibilitas alur ganti password lama
Route::middleware(['auth'])->group(function () {
    Route::get('/ganti-password', [GantiPasswordController::class, 'show'])->name('ganti-password');
    Route::post('/ganti-password', [GantiPasswordController::class, 'update'])->name('ganti-password.update');
});

// ─── DASHBOARD & AREA TERPROTEKSI ──────────────────────────────────────────
Route::middleware(['auth', 'role.web'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
});
