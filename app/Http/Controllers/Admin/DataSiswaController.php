<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class DataSiswaController extends Controller
{
    public function index(): View
    {
        return view('admin.data-siswa', [
            'siswa'       => collect([]),
            'kelasList'   => [],
            'angkatanList'=> [],
        ]);
    }

    public function show(int $id): View
    {
        abort(404, 'Data siswa belum tersedia.');
    }

    public function store(\Illuminate\Http\Request $request)
    {
        return back()->with('info', 'Fitur simpan data siswa belum diaktifkan.');
    }

    public function update(\Illuminate\Http\Request $request, int $id)
    {
        return back()->with('info', 'Fitur update data siswa belum diaktifkan.');
    }
}

