<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LaporanController extends Controller
{
    public function index(): View
    {
        return view('admin.laporan', [
            'kelasList'      => ['X-1', 'X-2', 'XI-1', 'XI-2', 'XII-1'],
            'riwayatLaporan' => [],
        ]);
    }

    public function cetak(Request $request)
    {
        return back()->with('info', 'Laporan sedang di-generate.');
    }
}

