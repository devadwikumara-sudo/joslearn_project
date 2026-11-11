<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class GantiPasswordController extends Controller
{
    public function show()
    {
        $user = Auth::user();

        // Jika tidak diwajibkan ganti password, langsung ke dashboard
        if (! $user || ! $user->wajib_ganti_password) {
            return redirect()->route('dashboard');
        }

        return view('login-guru-dan-admin.buat-kata-sandi-baru');
    }

    public function update(Request $request)
    {
        $request->validate([
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],
        ], [
            'password.required'  => 'Kata sandi baru wajib diisi.',
            'password.min'       => 'Kata sandi minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
        ]);

        $user = Auth::user();
        $user->password             = Hash::make($request->password);
        $user->wajib_ganti_password = false;
        $user->save();

        session([
            'identitas_nama' => $user->username,
            'identitas_id'   => 'Akun Terverifikasi · ' . strtoupper($user->peran),
        ]);

        return redirect()->route('selesai-kata-sandi');
    }
}
