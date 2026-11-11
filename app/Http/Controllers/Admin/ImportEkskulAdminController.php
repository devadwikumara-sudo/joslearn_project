<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SekolahKelas;
use App\Models\SekolahTahunAjaran;
use App\Services\ImportBatchHistory;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ImportEkskulAdminController extends Controller
{
    public function index(Request $request, ImportBatchHistory $batchHistory): View
    {
        $kelasList = SekolahKelas::pluck('nama')->toArray();
        if (empty($kelasList)) {
            $kelasList = ['X-1', 'X-2', 'XI-1', 'XI-2', 'XII-1', 'XII-2'];
        }

        $tahunAjaranList = SekolahTahunAjaran::query()->orderBy('nama')->get(['id', 'nama', 'sedang_berjalan']);
        $tahunBerjalan = $tahunAjaranList->firstWhere('sedang_berjalan', true);
        $tahunTerpilih = $tahunAjaranList->firstWhere('id', $request->integer('tahun_ajaran_id'))
            ?? $tahunBerjalan
            ?? $tahunAjaranList->first();
        $tahunAjaranTerpilihId = $tahunTerpilih?->id;
        $batches = $batchHistory->forYear('ekskul', $tahunAjaranTerpilihId);

        return view('admin.import.ekskul', compact(
            'kelasList',
            'tahunAjaranList',
            'tahunAjaranTerpilihId',
            'batches'
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
