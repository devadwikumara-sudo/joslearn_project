<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AlumniController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.alumni', [
            'totalAlumni'      => 0,
            'angkatanTerakhir' => '2024',
            'angkatanList'     => ['2024', '2023', '2022', '2021'],
            'alumni'           => collect([]),
        ]);
    }

    public function show(int $id): View
    {
        return redirect()->route('admin.alumni')->with('info', 'Detail alumni belum tersedia.');
    }

    public function export()
    {
        return back()->with('info', 'Fitur export alumni Excel sedang dipersiapkan.');
    }
}
