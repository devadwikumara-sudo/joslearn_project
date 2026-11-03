<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pengguna;
use Illuminate\View\View;

class LogAktivitasController extends Controller
{
    public function index(): View
    {
        return view('admin.log-aktivitas', [
            'penggunaList' => Pengguna::all(),
            'logs'         => collect([]),
        ]);
    }
}

