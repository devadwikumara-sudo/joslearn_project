<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PelacakanAlumniController extends Controller
{
    public function index()
    {
        return view('admin.pelacakan-alumni');
    }

    public function store(Request $request)
    {
        // TODO: simpan data pelacakan alumni
        return redirect()->route('admin.pelacakan-alumni')->with('success', 'Data pelacakan berhasil disimpan.');
    }

    public function destroy($id)
    {
        // TODO: hapus data pelacakan
        return redirect()->route('admin.pelacakan-alumni')->with('success', 'Data pelacakan dihapus.');
    }
}

