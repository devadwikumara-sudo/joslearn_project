<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\SekolahKelas;
use App\Services\AksesGuru;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ImportEkskulGuruController extends Controller
{
    public function index(Request $request, AksesGuru $aksesGuru): RedirectResponse
    {
        abort_unless($aksesGuru->adalahWali($request->user()), 403, 'Anda tidak memiliki penugasan wali kelas pada tahun ajaran berjalan.');

        return redirect()->route('guru.import-data', ['tab' => 'ekskul']);
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

        return back()->with('info', 'File ekskul berhasil diunggah.');
    }
}
