<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pengguna;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function index(): View
    {
        $setting = DB::table('sekolah_identitas')->where('id', 1)->first();
        $pengguna = Pengguna::all();

        return view('admin.setting', compact('setting', 'pengguna'));
    }

    public function update(Request $request)
    {
        DB::table('sekolah_identitas')->where('id', 1)->update([
            'nama'                => $request->nama_sekolah,
            'npsn'                => $request->npsn,
            'alamat'              => $request->alamat,
            'nama_kepala_sekolah' => $request->kepala_sekolah,
            'nip_kepala_sekolah'  => $request->nip_kepala,
            'updated_at'          => now(),
        ]);

        return back()->with('success', 'Pengaturan sekolah berhasil diperbarui.');
    }

    public function hapusAkun(int $id)
    {
        $pengguna = Pengguna::findOrFail($id);
        $pengguna->delete();

        return back()->with('success', 'Akun pengguna berhasil dihapus.');
    }
}

