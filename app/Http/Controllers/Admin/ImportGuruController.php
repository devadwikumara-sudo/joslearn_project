<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Guru\GuruBiodata;
use App\Models\SekolahTahunAjaran;
use Illuminate\View\View;

class ImportGuruController extends Controller
{
    public function index(): View
    {
        $totalGuru = GuruBiodata::whereNull('deleted_at')->count();
        $taAktif   = SekolahTahunAjaran::where('sedang_berjalan', 1)->first()
            ?? SekolahTahunAjaran::latest('id')->first();

        return view('admin.import.guru', compact('totalGuru', 'taAktif'));
    }
}

