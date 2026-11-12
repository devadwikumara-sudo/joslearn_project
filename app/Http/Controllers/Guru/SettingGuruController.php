<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Admin\CatatanAktivitas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class SettingGuruController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->peran === 'guru', 403, 'Halaman ini hanya untuk guru.');

        $guru = $request->user()->guru;
        abort_unless($guru, 403, 'Profil guru belum terhubung dengan akun ini.');

        return view('guru.setting', compact('guru'));
    }

    public function updateProfil(Request $request): RedirectResponse
    {
        abort_unless($request->user()->peran === 'guru', 403, 'Halaman ini hanya untuk guru.');

        $data = $request->validate([
            'nama' => ['required', 'string', 'max:150'],
        ], [
            'nama.required' => 'Nama tampilan wajib diisi.',
            'nama.max' => 'Nama tampilan maksimal 150 karakter.',
        ]);
        $pengguna = $request->user();
        $guru = $pengguna->guru;
        abort_unless($guru, 403, 'Profil guru belum terhubung dengan akun ini.');

        DB::transaction(function () use ($data, $guru, $pengguna, $request): void {
            $guru->update(['nama' => $data['nama']]);

            CatatanAktivitas::query()->create([
                'pengguna_id' => $pengguna->id,
                'peran' => $pengguna->peran,
                'aksi' => 'ubah_nama_tampilan',
                'keterangan' => 'Mengubah nama tampilan akun guru.',
                'objek_tipe' => 'guru',
                'objek_id' => $guru->id,
                'alamat_ip' => $request->ip(),
            ]);
        });

        return back()->with('success', 'Nama tampilan berhasil diperbarui.');
    }

    public function updateKataSandi(Request $request): RedirectResponse
    {
        abort_unless($request->user()->peran === 'guru', 403, 'Halaman ini hanya untuk guru.');

        $data = $request->validate([
            'password_lama' => ['required', 'current_password'],
            'password_baru' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'password_lama.required' => 'Kata sandi saat ini wajib diisi.',
            'password_lama.current_password' => 'Kata sandi saat ini tidak sesuai.',
            'password_baru.required' => 'Kata sandi baru wajib diisi.',
            'password_baru.min' => 'Kata sandi baru minimal 8 karakter.',
            'password_baru.confirmed' => 'Konfirmasi kata sandi baru tidak cocok.',
        ]);
        $pengguna = $request->user();

        DB::transaction(function () use ($data, $pengguna, $request): void {
            $pengguna->password = Hash::make($data['password_baru']);
            $pengguna->save();

            CatatanAktivitas::query()->create([
                'pengguna_id' => $pengguna->id,
                'peran' => $pengguna->peran,
                'aksi' => 'ubah_kata_sandi',
                'keterangan' => 'Mengubah kata sandi akun guru.',
                'objek_tipe' => 'akun',
                'objek_id' => $pengguna->id,
                'alamat_ip' => $request->ip(),
            ]);
        });

        return back()->with('success', 'Kata sandi berhasil diperbarui.');
    }
}
