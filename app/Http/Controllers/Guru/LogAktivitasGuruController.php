<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Admin\CatatanAktivitas;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LogAktivitasGuruController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->peran === 'guru', 403, 'Halaman ini hanya untuk guru.');

        $logs = CatatanAktivitas::query()
            ->where('pengguna_id', $request->user()->id)
            ->latest('id')
            ->paginate(25);

        return view('guru.log-aktivitas', compact('logs'));
    }
}
