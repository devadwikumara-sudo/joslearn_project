<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\SekolahKelas;
use App\Models\SekolahTahunAjaran;
use App\Models\SiswaBiodata;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DataSiswaGuruController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'tahun_ajaran' => ['nullable', 'integer', 'exists:sekolah_tahun_ajaran,id'],
            'kelas' => ['nullable', 'string', 'exists:sekolah_kelas,nama'],
            'q' => ['nullable', 'string', 'max:150'],
            'st' => ['nullable', 'in:Aktif,Pindah keluar,Keluar,Lulus'],
        ]);
        $tahunAjaranTerpilih = (string) ($filters['tahun_ajaran'] ?? '');
        $kelasTerpilih = $filters['kelas'] ?? '';
        $search = trim($filters['q'] ?? '');
        $statusFilter = $filters['st'] ?? '';

        $query = SiswaBiodata::query();
        if ($tahunAjaranTerpilih !== '' || $kelasTerpilih !== '' || $statusFilter !== '') {
            $query->whereHas('riwayatKelas', function ($riwayatQuery) use ($tahunAjaranTerpilih, $kelasTerpilih, $statusFilter): void {
                if ($tahunAjaranTerpilih !== '') {
                    $riwayatQuery->where('tahun_ajaran_id', $tahunAjaranTerpilih);
                }

                if ($kelasTerpilih !== '') {
                    $riwayatQuery->whereHas('kelas', fn ($kelasQuery) => $kelasQuery->where('nama', $kelasTerpilih));
                }

                if ($statusFilter !== '') {
                    $riwayatQuery->where('status', $statusFilter);
                }
            });
        }

        if ($search !== '') {
            $query->where(function ($searchQuery) use ($search): void {
                $searchQuery->where('nama_lengkap', 'like', "%{$search}%")
                    ->orWhere('nisn', 'like', "%{$search}%")
                    ->orWhere('nomor_induk', 'like', "%{$search}%");
            });
        }

        $siswa = $query->with(['riwayatKelas' => function ($riwayatQuery) use ($tahunAjaranTerpilih, $kelasTerpilih, $statusFilter): void {
            if ($tahunAjaranTerpilih !== '') {
                $riwayatQuery->where('tahun_ajaran_id', $tahunAjaranTerpilih);
            }

            if ($kelasTerpilih !== '') {
                $riwayatQuery->whereHas('kelas', fn ($kelasQuery) => $kelasQuery->where('nama', $kelasTerpilih));
            }

            if ($statusFilter !== '') {
                $riwayatQuery->where('status', $statusFilter);
            }

            $riwayatQuery->with(['kelas', 'tahunAjaran'])
                ->orderByDesc('tahun_ajaran_id')
                ->orderByDesc('id');
        }])
            ->orderBy('nama_lengkap')
            ->paginate(25)
            ->withQueryString();

        $kelasList = SekolahKelas::orderBy('nama')->pluck('nama')->all();
        sort($kelasList, SORT_NATURAL);
        $tahunAjaranList = SekolahTahunAjaran::orderBy('nama')->get(['id', 'nama']);

        return view('guru.data-siswa', compact(
            'siswa',
            'kelasList',
            'tahunAjaranList',
            'tahunAjaranTerpilih',
            'kelasTerpilih',
            'search',
            'statusFilter'
        ));
    }

    public function show(int $id): View
    {
        $siswa = SiswaBiodata::with(['riwayatKelas' => function ($query): void {
            $query->with(['kelas', 'tahunAjaran'])->orderByDesc('tahun_ajaran_id');
        }])->findOrFail($id);

        return view('guru.data-siswa-detail', compact('siswa'));
    }
}
