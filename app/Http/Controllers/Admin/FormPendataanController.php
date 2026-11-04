<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class FormPendataanController extends Controller
{
    public function index()
    {
        return view('admin.form-pendataan');
    }

    public function store(Request $request)
    {
        // TODO: simpan data siswa baru ke database
        return redirect()->route('admin.data-siswa')->with('success', 'Data siswa berhasil disimpan.');
    }

    public function edit($id)
    {
        // TODO: ambil data siswa dari DB
        return view('admin.form-pendataan', ['siswaId' => $id]);
    }

    public function update(Request $request, $id)
    {
        // TODO: update data siswa
        return redirect()->route('admin.data-siswa')->with('success', 'Data siswa berhasil diperbarui.');
    }
}

