<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ImportKehadiranAdminController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->route('admin.import-data', ['tab' => 'kehadiran']);
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
