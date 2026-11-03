<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class CekKualitasDataController extends Controller
{
    public function index(): View
    {
        return view('admin.cek-kualitas-data', [
            'dataLengkap'  => 0,
            'tidakLengkap' => 0,
            'belumFoto'    => 0,
            'nisnDuplikat' => 0,
            'masalah'      => [],
        ]);
    }
}

