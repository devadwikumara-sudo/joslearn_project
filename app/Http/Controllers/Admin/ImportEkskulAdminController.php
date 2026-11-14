<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ImportEkskulAdminController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->route('admin.import-data', ['tab' => 'ekskul']);
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
