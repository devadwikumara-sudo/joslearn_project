<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\ResetSandiSiswaService;
use Illuminate\Http\Request;

/**
 * Halaman web untuk membuat kata sandi baru siswa dari tautan email
 * yang diminta lewat aplikasi mobile (POST /api/v1/lupa-password).
 */
class ResetSandiSiswaController extends Controller
{
    public function __construct(private ResetSandiSiswaService $layanan) {}

    public function show(Request $request)
    {
        $token = (string) $request->query('token', '');
        $akun = $token !== '' ? $this->layanan->bacaToken($token) : null;

        if (! $akun) {
            return response()->view('siswa-mobile.reset-sandi', ['status' => 'tidak_valid'], 410);
        }

        return view('siswa-mobile.reset-sandi', ['status' => 'form', 'token' => $token]);
    }

    public function simpan(Request $request)
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'password.required' => 'Kata sandi baru wajib diisi.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
        ]);

        $akun = $this->layanan->bacaToken($data['token']);

        if (! $akun) {
            return response()->view('siswa-mobile.reset-sandi', ['status' => 'tidak_valid'], 410);
        }

        $this->layanan->ubahPassword($akun, $data['password']);

        return view('siswa-mobile.reset-sandi', ['status' => 'selesai']);
    }
}
