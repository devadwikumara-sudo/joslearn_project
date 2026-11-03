<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class DashboardAdminController extends Controller
{
    public function index(): View
    {
        return view('admin.dashboard', [
            'totalSiswa'    => 0,
            'totalGuru'     => 0,
            'totalKelas'    => 0,
            'totalImport'   => 0,
            'distribusiKelas' => [],
            'aktivitas'     => [],
            'siswaKurang'   => 0,
            'importPending' => 0,
        ]);
    }
}

