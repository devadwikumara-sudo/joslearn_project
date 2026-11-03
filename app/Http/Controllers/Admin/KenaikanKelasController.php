<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KenaikanKelasController extends Controller
{
    public function index(): View
    {
        return view('admin.kenaikan-kelas', [
            'kelasList'   => ['X-1', 'X-2', 'XI-1', 'XI-2'],
            'statusKelas' => [],
            'siswaTidakNaik' => [],
        ]);
    }

    public function proses(Request $request)
    {
        return back()->with('info', 'Proses kenaikan kelas sedang dipersiapkan.');
    }
}

