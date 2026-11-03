<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ImportEkskulAdminController extends Controller
{
    public function index(): View
    {
        return view('admin.import.ekskul', ['riwayat' => []]);
    }

    public function store(Request $request)
    {
        return back()->with('info', 'File ekskul berhasil diunggah.');
    }
}

