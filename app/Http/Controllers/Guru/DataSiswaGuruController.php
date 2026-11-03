<?php
namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class DataSiswaGuruController extends Controller
{
    public function index(Request $request): View
    {
        $siswa = new LengthAwarePaginator([], 0, 15);
        $kelasList = ['X-1', 'X-2', 'XI-1', 'XI-2', 'XII-1'];

        return view('guru.data-siswa', compact('siswa', 'kelasList'));
    }

    public function show(int $id): View
    {
        abort(404, 'Data siswa belum tersedia.');
    }
}

