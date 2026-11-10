<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\SekolahKelas;
use App\Services\AksesGuru;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ImportKetidakhadiranController extends Controller
{
    public function index(Request $request, AksesGuru $aksesGuru): View
    {
        return view('guru.import-ketidakhadiran', [
            'kelasList' => $aksesGuru->kelasWali($request->user())->pluck('nama')->all(),
            'riwayat' => [],
        ]);
    }

    public function store(Request $request, AksesGuru $aksesGuru)
    {
        $data = $request->validate([
            'kelas' => ['required', 'string', 'exists:sekolah_kelas,nama'],
        ]);
        $kelas = SekolahKelas::query()->where('nama', $data['kelas'])->firstOrFail();

        abort_unless(
            $aksesGuru->bolehIsiKehadiranEkskul($request->user(), (int) $kelas->id),
            403,
            "Anda bukan wali kelas {$kelas->nama}."
        );

        return back()->with('info', 'File ketidakhadiran berhasil diunggah.');
    }
}
