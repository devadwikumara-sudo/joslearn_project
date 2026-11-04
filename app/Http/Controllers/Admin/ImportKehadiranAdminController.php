<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ImportKehadiranAdminController extends Controller
{
    public function index(): View
    {
        return view('admin.import.kehadiran', [
            'kelasList' => ['X-1', 'X-2', 'XI-1', 'XI-2', 'XII-1'],
            'riwayat'   => [],
        ]);
    }

    public function store(Request $request)
    {
        return back()->with('info', 'File kehadiran berhasil diunggah.');
    }

    public function template()
    {
        return back()->with('info', 'Template Excel kehadiran sedang diunduh.');
    }
}
