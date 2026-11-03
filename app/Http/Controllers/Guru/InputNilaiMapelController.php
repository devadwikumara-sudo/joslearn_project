<?php
namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InputNilaiMapelController extends Controller
{
    public function index(Request $request): View
    {
        $kelasList = ['X-1', 'X-2', 'XI-1', 'XI-2', 'XII-1'];
        $siswa = [];

        return view('guru.input-nilai-mapel', compact('kelasList', 'siswa'));
    }

    public function store(Request $request)
    {
        return back()->with('success', 'Nilai mapel berhasil disimpan.');
    }
}

