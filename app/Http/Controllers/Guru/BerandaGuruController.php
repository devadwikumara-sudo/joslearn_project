<?php
namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Services\AksesGuru;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BerandaGuruController extends Controller
{
    public function index(Request $request, AksesGuru $aksesGuru): View
    {
        return view('guru.beranda', [
            'kelasDiampu'    => 0,
            'totalSiswa'     => 0,
            'nilaiPending'   => 0,
            'importBulanIni' => 0,
            'kelasList'      => [],
            'kelasWali'      => $aksesGuru->kelasWali($request->user()),
            'pengingat'      => [],
        ]);
    }
}
