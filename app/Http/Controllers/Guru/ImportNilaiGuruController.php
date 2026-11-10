<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\SekolahKelas;
use App\Models\SekolahMapel;
use App\Services\AksesGuru;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ImportNilaiGuruController extends Controller
{
    public function index(Request $request, AksesGuru $aksesGuru): View
    {
        return view('guru.import-nilai', [
            'kelasList' => $aksesGuru->kelasDiajar($request->user())->pluck('nama')->all(),
            'riwayat' => [],
        ]);
    }

    public function store(Request $request, AksesGuru $aksesGuru)
    {
        $data = $request->validate([
            'kelas' => ['required', 'string', 'exists:sekolah_kelas,nama'],
            'mata_pelajaran' => ['required', 'string', 'max:150'],
        ]);
        $kelas = SekolahKelas::query()->where('nama', $data['kelas'])->firstOrFail();
        $mapel = SekolahMapel::query()
            ->where('nama', $data['mata_pelajaran'])
            ->orWhere('kode', $data['mata_pelajaran'])
            ->first();

        abort_unless(
            $mapel && $aksesGuru->bolehIsiNilai($request->user(), (int) $kelas->id, (int) $mapel->id),
            403,
            "Anda tidak memiliki penugasan untuk mapel {$data['mata_pelajaran']} di kelas {$kelas->nama}."
        );

        return back()->with('info', 'File nilai guru berhasil diunggah.');
    }
}
