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
            'mutasiMasuk'  => 0,
            'mutasiKeluar' => 0,
            'mutasi'       => [],
        ]);
    }

    public function store(Request $request)
    {
        return back()->with('info', 'Pencatatan mutasi berhasil disimpan.');
    }
}

