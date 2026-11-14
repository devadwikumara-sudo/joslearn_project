<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ImportNilaiAdminController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->route('admin.import-data', ['tab' => 'nilai']);
    }

    public function store(Request $request)
    {
        return back()->with('info', 'File berhasil diterima.');
    }

    public function confirm(Request $request)
    {
        return redirect()->route('admin.import-data', ['tab' => 'nilai'])->with('success', 'Data nilai berhasil diimpor.');
    }

    public function template()
    {
        return back()->with('info', 'Gunakan tombol Download Template di halaman untuk mengunduh template spreadsheet.');
    }
}
