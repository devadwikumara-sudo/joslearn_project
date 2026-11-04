<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Models\SekolahKelas;
use App\Models\SekolahTahunAjaran;
use App\Models\SiswaEkskul;

class ImportEkskulAdminController extends Controller
{
    public function index(): View
    {
        $taAktifObj = SekolahTahunAjaran::where('sedang_berjalan', 1)->first()
            ?? SekolahTahunAjaran::latest('id')->first();
        $taAktif = $taAktifObj ? $taAktifObj->nama : '2024/2025';

        $kelasList = SekolahKelas::pluck('nama')->toArray();
        if (empty($kelasList)) {
            $kelasList = ['X-1', 'X-2', 'XI-1', 'XI-2', 'XII-1', 'XII-2'];
        }

        $riwayatImport = SiswaEkskul::where('tahun_ajaran', $taAktif)
            ->selectRaw('kelas, semester, count(*) as baris')
            ->groupBy('kelas', 'semester')
            ->orderBy('kelas')
            ->get();

        return view('admin.import.ekskul', compact(
            'taAktif',
            'kelasList',
            'riwayatImport'
        ));
    }

    public function store(Request $request)
    {
        return back()->with('info', 'File ekstrakurikuler berhasil diterima.');
    }

    public function template()
    {
        return back()->with('info', 'Gunakan tombol Download Template di halaman.');
    }
}
