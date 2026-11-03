<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class AlumniController extends Controller
{
    public function index(): View
    {
        return view('admin.alumni', [
            'totalAlumni'      => 0,
            'angkatanTerakhir' => '2024',
            'angkatanList'     => ['2024', '2023', '2022'],
            'alumni'           => [],
        ]);
    }

    public function show(int $id): View
    {
        return view('admin.alumni', [
            'totalAlumni'      => 0,
            'angkatanTerakhir' => '2024',
            'angkatanList'     => [],
            'alumni'           => [],
        ]);
    }
}

