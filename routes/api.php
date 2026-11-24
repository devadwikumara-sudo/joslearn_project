<?php

use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\BiodataApiController;
use App\Http\Controllers\Api\HomeApiController;
use App\Http\Controllers\Api\NilaiApiController;
use App\Http\Controllers\Api\NotifikasiApiController;
use App\Http\Controllers\Api\ProfilApiController;
use App\Http\Controllers\Api\SesiApiController;
use App\Http\Middleware\EnsureSiswaOnly;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // Endpoint pemeriksaan awal
    Route::get('/status', fn () => response()->json([
        'app' => 'JosBook', 'status' => 'starter',
    ]));

    // ─── PUBLIK (tanpa token) — batas umum per IP untuk jaringan sekolah bersama.
    Route::middleware('throttle:60,1')->group(function () {
        Route::post('/login', [AuthApiController::class, 'login']);
        Route::middleware('throttle:mobile-auth')->group(function () {
            Route::post('/daftar', [AuthApiController::class, 'daftar']);
            Route::post('/lupa-password', [AuthApiController::class, 'lupaPassword']);
        });
    });

    // ─── TERPROTEKSI (token Sanctum + khusus siswa) ──────────────────────────
    Route::middleware(['auth:sanctum', EnsureSiswaOnly::class])->group(function () {
        Route::post('/logout', [AuthApiController::class, 'logout']);

        // Sesi aktif
        Route::get('/sesi', [SesiApiController::class, 'index']);
        Route::delete('/sesi', [SesiApiController::class, 'destroyAll']);
        Route::delete('/sesi/{id}', [SesiApiController::class, 'destroy'])->whereNumber('id');

        // Home, biodata, nilai, unduh
        Route::get('/home', [HomeApiController::class, 'index']);
        Route::get('/biodata', [BiodataApiController::class, 'show']);
        Route::get('/nilai', [NilaiApiController::class, 'index']);
        Route::get('/unduh/{jenis}', [BiodataApiController::class, 'unduh']);

        // Profil
        Route::get('/profil', [ProfilApiController::class, 'show']);
        Route::put('/profil', [ProfilApiController::class, 'update']);
        Route::put('/ganti-password', [ProfilApiController::class, 'gantiPassword']);
        Route::get('/profil/foto', [ProfilApiController::class, 'foto']);
        Route::post('/profil/foto', [ProfilApiController::class, 'simpanFoto'])->middleware('throttle:10,1');

        // Notifikasi
        Route::get('/notifikasi', [NotifikasiApiController::class, 'index']);
        Route::post('/notifikasi/baca', [NotifikasiApiController::class, 'baca']);
        Route::post('/notifikasi/{id}/baca', [NotifikasiApiController::class, 'bacaSatu'])->whereNumber('id');
    });
});
