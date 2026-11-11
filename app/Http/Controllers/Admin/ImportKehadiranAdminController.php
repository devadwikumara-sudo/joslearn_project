<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SekolahKelas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ImportKehadiranAdminController extends Controller
{
    public function index(): View
    {
        $kelasList = SekolahKelas::pluck('nama')->toArray();
        if (empty($kelasList)) {
            $kelasList = ['X-1', 'X-2', 'XI-1', 'XI-2', 'XII-1', 'XII-2'];
        }

        $riwayatImport = DB::table('siswa_kehadiran as data')
            ->join('siswa_riwayat_kelas as riwayat', 'riwayat.id', '=', 'data.riwayat_kelas_id')
            ->join('sekolah_tahun_ajaran as tahun', 'tahun.id', '=', 'riwayat.tahun_ajaran_id')
            ->join('sekolah_kelas as kelas', 'kelas.id', '=', 'riwayat.kelas_id')
            ->whereNull('riwayat.deleted_at')
            ->select('kelas.nama as kelas', 'tahun.nama as tahun_ajaran', 'data.semester')
            ->selectRaw("CASE kelas.tingkat WHEN 'X' THEN CASE data.semester WHEN 'Ganjil' THEN 1 ELSE 2 END WHEN 'XI' THEN CASE data.semester WHEN 'Ganjil' THEN 3 ELSE 4 END WHEN 'XII' THEN CASE data.semester WHEN 'Ganjil' THEN 5 ELSE 6 END END as semester_number")
            ->selectRaw('COUNT(*) as baris')
            ->groupBy('kelas.id', 'kelas.nama', 'tahun.id', 'tahun.nama', 'data.semester', 'kelas.tingkat')
            ->orderBy('tahun.nama')
            ->orderBy('kelas.nama')
            ->get();

        return view('admin.import.kehadiran', compact(
            'kelasList',
            'riwayatImport'
        ));
    }

    public function store(Request $request)
    {
        return back()->with('info', 'File kehadiran berhasil diterima.');
    }

    public function template()
    {
        return back()->with('info', 'Gunakan tombol Download Template di halaman.');
    }
}
