<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DataSiswaController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.data-siswa', [
            'siswa'       => collect([]),
            'kelasList'   => ['X-1', 'XI-1', 'XII-1'],
            'angkatanList'=> ['2023', '2024', '2025'],
        ]);
    }

    public function show(int $id): View
    {
        return redirect()->route('admin.data-siswa')->with('info', 'Detail siswa belum tersedia.');
    }

    public function store(Request $request)
    {
        return redirect()->route('admin.data-siswa')->with('success', 'Data siswa berhasil disimpan.');
    }

    public function update(Request $request, int $id)
    {
        return redirect()->route('admin.data-siswa')->with('success', 'Data siswa berhasil diperbarui.');
    }

    public function export()
    {
        return back()->with('info', 'Fitur export Excel sedang dipersiapkan.');
    }

    public function pdf(int $id)
    {
        return back()->with('info', 'Dokumen PDF siswa sedang dipersiapkan.');
    }
}
