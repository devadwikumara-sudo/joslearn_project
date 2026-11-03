<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class ImportController extends Controller
{
    public function index(): View { return view('import.index'); }
    public function store(Request $request)
    {
        // TODO: validasi, simpan file privat, baca sheet, petakan kolom, dan pratinjau.
        return back()->with('info', 'Mesin import Excel belum diaktifkan.');
    }
}
