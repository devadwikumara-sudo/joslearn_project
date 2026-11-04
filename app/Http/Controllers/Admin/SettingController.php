<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pengguna;
use App\Models\SekolahTahunAjaran;
use App\Models\CatatanAktivitas;
use App\Models\SiswaBiodata;
use App\Models\GuruBiodata;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function index(): View
    {
        $setting = DB::table('sekolah_identitas')->where('id', 1)->first();
        $pengguna = Pengguna::with('guru')->orderBy('peran')->orderBy('username')->get();
        $tahunAjaranList = SekolahTahunAjaran::orderByDesc('id')->get();

        return view('admin.setting', compact('setting', 'pengguna', 'tahunAjaranList'));
    }

    public function tempahSampah(): View
    {
        $siswaTerhapus = SiswaBiodata::onlyTrashed()->orderByDesc('deleted_at')->get();
        $guruTerhapus  = GuruBiodata::onlyTrashed()->orderByDesc('deleted_at')->get();

        return view('admin.tempat-sampah', compact('siswaTerhapus', 'guruTerhapus'));
    }

    public function pulihkan(Request $request)
    {
        $tipe = $request->input('tipe');
        $id   = $request->input('id');

        if ($tipe === 'siswa') {
            $item = SiswaBiodata::withTrashed()->findOrFail($id);
            $item->restore();
            return back()->with('success', "Siswa {$item->nama_lengkap} berhasil dipulihkan.");
        }

        if ($tipe === 'guru') {
            $item = GuruBiodata::withTrashed()->findOrFail($id);
            $item->restore();
            return back()->with('success', "Guru {$item->nama} berhasil dipulihkan.");
        }

        return back()->with('error', 'Tipe tidak dikenal.');
    }

    public function hapusPermanenSampah(Request $request)
    {
        $tipe = $request->input('tipe');
        $id   = $request->input('id');

        if ($tipe === 'siswa') {
            $item = SiswaBiodata::withTrashed()->findOrFail($id);
            $nama = $item->nama_lengkap;
            $item->forceDelete();
            return back()->with('success', "Siswa {$nama} dihapus permanen.");
        }

        if ($tipe === 'guru') {
            $item = GuruBiodata::withTrashed()->findOrFail($id);
            $nama = $item->nama;
            $item->forceDelete();
            return back()->with('success', "Guru {$nama} dihapus permanen.");
        }

        return back()->with('error', 'Tipe tidak dikenal.');
    }

    public function update(Request $request)
    {
        $request->validate([
            'nama'                => 'required|string|max:200',
            'npsn'                => 'nullable|string|max:20',
            'alamat'              => 'nullable|string|max:500',
            'nama_kepala_sekolah' => 'nullable|string|max:200',
            'nip_kepala_sekolah'  => 'nullable|string|max:30',
        ]);

        $exists = DB::table('sekolah_identitas')->where('id', 1)->exists();
        if ($exists) {
            DB::table('sekolah_identitas')->where('id', 1)->update([
                'nama'                => $request->nama,
                'npsn'                => $request->npsn,
                'alamat'              => $request->alamat,
                'nama_kepala_sekolah' => $request->nama_kepala_sekolah,
                'nip_kepala_sekolah'  => $request->nip_kepala_sekolah,
                'updated_at'          => now(),
            ]);
        } else {
            DB::table('sekolah_identitas')->insert([
                'nama'                => $request->nama,
                'npsn'                => $request->npsn,
                'alamat'              => $request->alamat,
                'nama_kepala_sekolah' => $request->nama_kepala_sekolah,
                'nip_kepala_sekolah'  => $request->nip_kepala_sekolah,
                'created_at'          => now(),
                'updated_at'          => now(),
            ]);
        }

        if ($request->filled('tahun_ajaran_aktif_id')) {
            SekolahTahunAjaran::query()->update(['sedang_berjalan' => 0]);
            SekolahTahunAjaran::where('id', $request->tahun_ajaran_aktif_id)
                ->update(['sedang_berjalan' => 1]);
        }

        CatatanAktivitas::create([
            'pengguna_id' => auth()->id(),
            'peran'       => auth()->user()->peran ?? 'admin',
            'aksi'        => 'update_setting',
            'keterangan'  => 'Memperbarui pengaturan sekolah.',
            'objek_tipe'  => 'setting',
            'alamat_ip'   => $request->ip(),
        ]);

        return back()->with('success', 'Pengaturan sekolah berhasil diperbarui.');
    }

    public function tambahTahunAjaran(Request $request)
    {
        $request->validate(['nama_ta' => 'required|string|max:20|unique:sekolah_tahun_ajaran,nama'], [
            'nama_ta.unique' => 'Tahun ajaran ini sudah ada.',
        ]);
        $aktif = $request->boolean('aktif');
        if ($aktif) {
            SekolahTahunAjaran::query()->update(['sedang_berjalan' => 0]);
        }
        SekolahTahunAjaran::create([
            'nama'            => $request->nama_ta,
            'sedang_berjalan' => $aktif ? 1 : 0,
        ]);
        return back()->with('success', "Tahun Ajaran {$request->nama_ta} berhasil ditambahkan.");
    }

    public function setTahunAjaranAktif(Request $request)
    {
        $id = $request->input('ta_id');
        SekolahTahunAjaran::query()->update(['sedang_berjalan' => 0]);
        SekolahTahunAjaran::where('id', $id)->update(['sedang_berjalan' => 1]);
        return back()->with('success', 'Tahun ajaran aktif berhasil diubah.');
    }

    public function hapusAkun(int $id)
    {
        if ($id === auth()->id()) {
            return back()->with('error', 'Anda tidak bisa menghapus akun sendiri.');
        }
        $pengguna = Pengguna::findOrFail($id);
        $pengguna->delete();
        return back()->with('success', "Akun {$pengguna->username} berhasil dihapus.");
    }
}

