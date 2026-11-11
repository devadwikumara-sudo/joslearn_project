<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SekolahKelas;
use App\Models\SekolahMapel;
use App\Models\SiswaRiwayatKelas;
use App\Models\SiswaNilai;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MasterKelasController extends Controller
{
    public function index(): View
    {
        $kelasList = SekolahKelas::orderBy('tingkat')->orderBy('nama')->get();
        $mapelList = SekolahMapel::orderBy('nama')->get();

        return view('admin.master-kelas', compact('kelasList', 'mapelList'));
    }

    public function store(Request $request)
    {
        $type = $request->input('type');

        if ($type === 'kelas') {
            $request->validate([
                'nama_kelas' => 'required|string|max:20|unique:sekolah_kelas,nama',
            ], ['nama_kelas.unique' => 'Nama kelas sudah ada.']);

            $nama = strtoupper(trim($request->nama_kelas));
            $tingkat = 'X';
            if (str_starts_with($nama, 'XII')) $tingkat = 'XII';
            elseif (str_starts_with($nama, 'XI')) $tingkat = 'XI';

            SekolahKelas::create(['nama' => $nama, 'tingkat' => $tingkat]);
            return redirect()->route('admin.master-kelas')->with('success', "Kelas {$nama} berhasil ditambahkan.");
        }

        if ($type === 'mapel') {
            $request->validate([
                'nama_mapel' => 'required|string|max:100',
                'kode_mapel' => 'nullable|string|max:20',
            ]);

            $nama = trim($request->nama_mapel);
            $kode = trim($request->kode_mapel ?? '');
            $tingkat = $request->input('tingkat_mapel', []);

            if (SekolahMapel::where('nama', $nama)->exists()) {
                return redirect()->route('admin.master-kelas')->with('error', "Mata pelajaran '{$nama}' sudah ada.");
            }

            SekolahMapel::create([
                'nama'    => $nama,
                'kode'    => $kode ?: null,
                'tingkat' => !empty($tingkat) ? implode(',', $tingkat) : 'X,XI,XII',
            ]);
            return redirect()->route('admin.master-kelas')->with('success', "Mata pelajaran {$nama} berhasil ditambahkan.");
        }

        return redirect()->route('admin.master-kelas')->with('error', 'Tipe tidak dikenal.');
    }

    public function destroy(Request $request, $id)
    {
        $type = $request->input('type', 'kelas');

        if ($type === 'mapel') {
            $mapel = SekolahMapel::findOrFail($id);
            $used = SiswaNilai::where('mapel_id', $id)->exists();
            if ($used) {
                return redirect()->route('admin.master-kelas')->with('error', "Mata pelajaran '{$mapel->nama}' tidak bisa dihapus karena masih dipakai di data nilai.");
            }
            $mapel->delete();
            return redirect()->route('admin.master-kelas')->with('success', "Mata pelajaran '{$mapel->nama}' berhasil dihapus.");
        }

        $kelas = SekolahKelas::findOrFail($id);
        $used = SiswaRiwayatKelas::where('kelas_id', $id)->exists();
        if ($used) {
            return redirect()->route('admin.master-kelas')->with('error', "Kelas '{$kelas->nama}' tidak bisa dihapus karena masih ada siswa terdaftar.");
        }
        $kelas->delete();
        return redirect()->route('admin.master-kelas')->with('success', "Kelas '{$kelas->nama}' berhasil dihapus.");
    }
}

