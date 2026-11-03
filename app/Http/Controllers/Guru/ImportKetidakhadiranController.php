<?php
namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ImportKetidakhadiranController extends Controller
{
    public function index(): View
    {
        return view('guru.import-ketidakhadiran', [
            'kelasList' => ['X-1', 'X-2', 'XI-1', 'XI-2', 'XII-1'],
            'riwayat'   => [],
        ]);
    }

    public function store(Request $request)
    {
        return back()->with('info', 'File ketidakhadiran berhasil diunggah.');
    }
}

