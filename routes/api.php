<?php

use Illuminate\Support\Facades\Route;

// Endpoint pemeriksaan awal; tambahkan autentikasi sebelum API dipakai.
Route::prefix('v1')->group(function () {
    Route::get('/status', fn () => response()->json([
        'app' => 'JosBook', 'status' => 'starter',
    ]));
});
