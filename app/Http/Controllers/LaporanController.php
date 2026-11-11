<?php
namespace App\Http\Controllers;

use Illuminate\View\View;

class LaporanController extends Controller
{
    public function index(): View { return view('laporan.index'); }
}
