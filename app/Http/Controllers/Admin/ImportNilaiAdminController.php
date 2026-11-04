<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ImportNilaiAdminController extends Controller
{
    public function index(): View
    {
        return view('admin.import.nilai', [
            'kelasList'     => ['X-1', 'X-2', 'XI-1', 'XI-2', 'XII-1'],
            'riwayatImport' => [],
        ]);
    }

    public function store(Request $request)
    {
        return back()->with('info', 'File berhasil diunggah. Menunggu proses parsing Excel.');
    }

    public function confirm(Request $request)
    {
        return redirect()->route('admin.import.nilai')->with('success', 'Data nilai berhasil diimpor.');
    }

    public function template()
    {
        return back()->with('info', 'Template Excel nilai sedang diunduh.');
    }
}
