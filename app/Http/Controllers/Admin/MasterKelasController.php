<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MasterKelasController extends Controller
{
    public function index()
    {
        return view('admin.master-kelas');
    }

    public function store(Request $request)
    {
        // TODO: simpan kelas atau mapel baru
        return redirect()->route('admin.master-kelas')->with('success', 'Data berhasil disimpan.');
    }

    public function destroy($id)
    {
        // TODO: hapus kelas atau mapel
        return redirect()->route('admin.master-kelas')->with('success', 'Data berhasil dihapus.');
    }
}

