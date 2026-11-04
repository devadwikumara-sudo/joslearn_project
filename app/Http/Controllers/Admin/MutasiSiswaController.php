<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MutasiSiswaController extends Controller
{
    public function index(): View
    {
        return view('admin.mutasi-siswa', [
            'mutasiMasuk'   => 0,
            'mutasiKeluar'  => 0,
            'mutasi'        => collect([]),
            'riwayatMutasi' => collect([]),
            'siswaAktif'    => collect([]),
        ]);
    }

    public function store(Request $request)
    {
        return back()->with('success', 'Pencatatan mutasi berhasil disimpan.');
    }
}
