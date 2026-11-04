<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SekolahKelas;
use App\Models\SekolahTahunAjaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

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

        $riwayatImport = DB::table('siswa_ekskul as data')
            ->join('siswa_riwayat_kelas as riwayat', 'riwayat.id', '=', 'data.riwayat_kelas_id')
            ->join('sekolah_tahun_ajaran as tahun', 'tahun.id', '=', 'riwayat.tahun_ajaran_id')
            ->join('sekolah_kelas as kelas', 'kelas.id', '=', 'riwayat.kelas_id')
            ->where('tahun.id', $taAktifObj?->id ?? 0)
            ->whereNull('riwayat.deleted_at')
            ->select('kelas.nama as kelas', 'data.semester')
            ->selectRaw('COUNT(*) as baris')
            ->groupBy('kelas.id', 'kelas.nama', 'data.semester')
            ->orderBy('kelas.nama')
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
