<?php
namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class BerandaGuruController extends Controller
{
    public function index(): View
    {
        return view('guru.beranda', [
            'kelasDiampu'    => 0,
            'totalSiswa'     => 0,
            'nilaiPending'   => 0,
            'importBulanIni' => 0,
            'kelasList'      => [],
            'pengingat'      => [],
        ]);
    }
}

