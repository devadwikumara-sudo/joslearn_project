<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\SekolahTahunAjaran;
use App\Models\SiswaRiwayatKelas;
use App\Services\AksesGuru;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BerandaGuruController extends Controller
{
    public function index(Request $request, AksesGuru $aksesGuru): View
    {
        $pengguna = $request->user();
        $guru = $aksesGuru->guruDariPengguna($pengguna);
        $tahunAjaran = SekolahTahunAjaran::query()
            ->where('sedang_berjalan', true)
            ->first();
        $semesterBerjalan = DB::table('sekolah_pengaturan')
            ->where('kunci', 'semester_berjalan')
            ->value('isi');
        $kelasDiajar = $aksesGuru->kelasDiajar($pengguna);
        $mapelDiajar = $aksesGuru->mapelDiajar($pengguna);
        $kelasWali = $aksesGuru->kelasWali($pengguna);

        if ($tahunAjaran && $kelasWali->isNotEmpty()) {
            $jumlahSiswaPerKelas = SiswaRiwayatKelas::query()
                ->where('tahun_ajaran_id', $tahunAjaran->id)
                ->where('status', 'Aktif')
                ->whereIn('kelas_id', $kelasWali->modelKeys())
                ->whereHas('siswa')
                ->select('kelas_id')
                ->selectRaw('COUNT(*) AS jumlah_siswa')
                ->groupBy('kelas_id')
                ->pluck('jumlah_siswa', 'kelas_id');

            foreach ($kelasWali as $kelas) {
                $kelas->setAttribute('jumlah_siswa', (int) ($jumlahSiswaPerKelas[$kelas->id] ?? 0));
            }
        } else {
            foreach ($kelasWali as $kelas) {
                $kelas->setAttribute('jumlah_siswa', 0);
            }
        }

        return view('guru.beranda', [
            'namaGuru' => $guru?->nama ?? $pengguna->username,
            'tahunAjaran' => $tahunAjaran,
            'semesterBerjalan' => $semesterBerjalan,
            'tanggalHariIni' => now()->locale('id')->translatedFormat('l, d F Y'),
            'kelasDiajar' => $kelasDiajar,
            'mapelDiajar' => $mapelDiajar,
            'kelasWali' => $kelasWali,
            'adaPenugasan' => $kelasDiajar->isNotEmpty() || $kelasWali->isNotEmpty(),
        ]);
    }
}
