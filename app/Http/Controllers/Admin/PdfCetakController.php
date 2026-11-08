<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Guru\GuruBiodata;
use App\Models\Guru\GuruWaliKelas;
use App\Models\SekolahIdentitas;
use App\Models\SekolahKelas;
use App\Models\SekolahMapel;
use App\Models\SekolahTahunAjaran;
use App\Models\SiswaBiodata;
use App\Models\SiswaEkskul;
use App\Models\SiswaKehadiran;
use App\Models\SiswaNilai;
use App\Models\SiswaRiwayatKelas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PdfCetakController extends Controller
{
    /**
     * Cetak dokumen PDF satu siswa (Register, Nilai, Hadir, Ekskul, Kenaikan).
     */
    public function single(Request $request, $id): View
    {
        $siswa = SiswaBiodata::where('id', $id)
            ->orWhere('nisn', $id)
            ->firstOrFail();

        return $this->renderDocumentView($request, [$siswa->id], false);
    }

    /**
     * Cetak dokumen PDF batch banyak siswa atau satu kelas penuh.
     */
    public function batch(Request $request): View
    {
        $ids = $request->input('ids', []);
        if (is_string($ids)) {
            $ids = array_filter(explode(',', $ids));
        }

        $kelas = $request->input('kelas');
        $ta = $request->input('ta', 'Semua');

        // Jika tidak ada list ID tetapi ada parameter kelas, ambil semua siswa di kelas tersebut
        if (empty($ids) && !empty($kelas)) {
            $query = SiswaRiwayatKelas::whereNull('deleted_at');

            if ($kelas !== '*') {
                $kelasId = SekolahKelas::where('nama', $kelas)->value('id');
                if ($kelasId) {
                    $query->where('kelas_id', $kelasId);
                }
            }

            if ($ta !== 'Semua') {
                $taId = SekolahTahunAjaran::where('nama', $ta)->value('id');
                if ($taId) {
                    $query->where('tahun_ajaran_id', $taId);
                }
            }

            $ids = $query->pluck('siswa_id')->unique()->toArray();
        }

        return $this->renderDocumentView($request, $ids, true);
    }

    /**
     * Core builder data dokumen cetak sesuai format prototype Kurikulum Merdeka.
     */
    private function renderDocumentView(Request $request, array $siswaIds, bool $isBatch): View
    {
        // 1. Data Identitas Sekolah
        $sekolah = SekolahIdentitas::first();
        if (!$sekolah) {
            $sekolah = (object) [
                'nama' => 'SMA NEGERI 1 NGANJUK',
                'npsn' => '20538338',
                'alamat' => 'Jl. Panglima Sudirman No. 77',
                'desa' => 'Mangundikaran',
                'kecamatan' => 'Nganjuk',
                'kabupaten' => 'Kab. Nganjuk',
                'provinsi' => 'Jawa Timur',
                'nama_kepala_sekolah' => 'Drs. SUGIARTO, M.Pd.',
                'nip_kepala_sekolah' => '19670815 199412 1 003',
            ];
        }

        // 2. Daftar Tahun Ajaran
        $taList = SekolahTahunAjaran::pluck('nama')->toArray();
        if (empty($taList)) {
            $taList = ['2023/2024', '2024/2025', '2025/2026'];
        }

        // 3. Parsing Pilihan Konten (Default: Semua aktif jika pertama buka)
        $hasQueryParams = $request->has('bio') || $request->has('nilai') || $request->has('hadir') || $request->has('ekskul') || $request->has('naik');

        $optBio = $hasQueryParams ? $request->boolean('bio') : true;
        $optNilai = $hasQueryParams ? $request->boolean('nilai') : true;
        $optHadir = $hasQueryParams ? $request->boolean('hadir') : true;
        $optEkskul = $hasQueryParams ? $request->boolean('ekskul') : true;
        $optNaik = $hasQueryParams ? $request->boolean('naik') : true;

        $filterSem = (string) $request->input('sem', 'Semua');
        if (! in_array($filterSem, ['Semua', 'Ganjil', 'Genap', '1', '2', '3', '4', '5', '6'], true)) {
            $filterSem = 'Semua';
        }
        $filterSemNumber = ctype_digit($filterSem) ? (int) $filterSem : null;
        $filterSemGrade = match (true) {
            $filterSemNumber !== null && $filterSemNumber <= 2 => 'X',
            $filterSemNumber !== null && $filterSemNumber <= 4 => 'XI',
            $filterSemNumber !== null => 'XII',
            default => null,
        };
        $reportFilterSem = match (true) {
            $filterSemNumber !== null && $filterSemNumber % 2 === 1 => 'Ganjil',
            $filterSemNumber !== null => 'Genap',
            default => $filterSem,
        };
        $filterTa = $request->input('ta', 'Semua');
        $filterKelas = (string) $request->input('kelas_filter', 'Semua');
        $filterKelasList = SekolahKelas::orderBy('nama')->pluck('nama')->toArray();
        $paper = $request->input('paper', 'F4');

        // 4. Mapping Next Kelas (Untuk Kenaikan Kelas)
        $allKelas = SekolahKelas::pluck('nama')->toArray();
        $nextKelasMap = [];
        foreach ($allKelas as $k) {
            if (str_starts_with($k, 'X-')) {
                $nextKelasMap[$k] = str_replace('X-', 'XI-', $k);
            } elseif (str_starts_with($k, 'XI-')) {
                $nextKelasMap[$k] = str_replace('XI-', 'XII-', $k);
            } else {
                $nextKelasMap[$k] = 'LULUS';
            }
        }

        // 5. Query Siswa & Bangun Data Lengkap
        $siswaCollection = SiswaBiodata::whereIn('id', $siswaIds)
            ->whereNull('deleted_at')
            ->with(['riwayatKelas' => function ($q) {
                $q->whereNull('deleted_at')->with(['kelas', 'tahunAjaran']);
            }])
            ->orderBy('nomor_induk')
            ->orderBy('nama_lengkap')
            ->get();

        $siswaItems = [];
        $uniqueClasses = [];

        foreach ($siswaCollection as $s) {
            $riwayatSorted = $s->riwayatKelas->sortBy(function ($r) {
                return $r->tahunAjaran?->nama ?? '';
            });

            // Filter TA jika bukan 'Semua'
            if ($filterTa !== 'Semua') {
                $riwayatSorted = $riwayatSorted->filter(function ($r) use ($filterTa) {
                    return ($r->tahunAjaran?->nama ?? '') === $filterTa;
                });
            }
            if ($filterKelas !== 'Semua') {
                $riwayatSorted = $riwayatSorted->filter(function ($r) use ($filterKelas) {
                    return ($r->kelas?->nama ?? '') === $filterKelas;
                });
            }
            if ($filterSemGrade !== null) {
                $riwayatSorted = $riwayatSorted->filter(function ($r) use ($filterSemGrade) {
                    $className = $r->kelas?->nama ?? '';
                    $grade = strtoupper((string) ($r->kelas?->tingkat ?? (preg_match('/^(XII|XI|X)(?:-|$)/i', $className, $matches) ? $matches[1] : '')));

                    return $grade === $filterSemGrade;
                });
            }

            $riwayatDataList = [];
            foreach ($riwayatSorted as $rk) {
                $kNama = $rk->kelas?->nama ?? 'X-1';
                $taNama = $rk->tahunAjaran?->nama ?? '2024/2025';

                $uniqueClasses[$kNama] = true;

                // Nilai Intrakurikuler
                $nilaiRows = SiswaNilai::where('riwayat_kelas_id', $rk->id)
                    ->join('sekolah_mapel', 'siswa_nilai.mapel_id', '=', 'sekolah_mapel.id')
                    ->select('sekolah_mapel.kode', 'siswa_nilai.semester', 'siswa_nilai.nilai_angka')
                    ->get();

                $nilaiMap = ['Ganjil' => [], 'Genap' => []];
                foreach ($nilaiRows as $nr) {
                    $semKey = ($nr->semester === 2 || strtolower($nr->semester) === 'genap') ? 'Genap' : 'Ganjil';
                    $nilaiMap[$semKey][$nr->kode] = $nr->nilai_angka;
                }

                // Kehadiran & Kokurikuler
                $hadirRows = SiswaKehadiran::where('riwayat_kelas_id', $rk->id)->get();
                $hadirMap = ['Ganjil' => ['sakit' => 0, 'izin' => 0, 'alpa' => 0], 'Genap' => ['sakit' => 0, 'izin' => 0, 'alpa' => 0]];
                $kokurMap = ['Ganjil' => null, 'Genap' => null];

                foreach ($hadirRows as $hr) {
                    $semKey = ($hr->semester === 2 || strtolower($hr->semester) === 'genap') ? 'Genap' : 'Ganjil';
                    $hadirMap[$semKey]['sakit'] = $hr->sakit;
                    $hadirMap[$semKey]['izin'] = $hr->izin;
                    $hadirMap[$semKey]['alpa'] = $hr->alpa;
                    if ($hr->kokurikuler) {
                        $kokurMap[$semKey] = $hr->kokurikuler;
                    }
                }

                // Ekskul
                $ekskulRows = SiswaEkskul::where('riwayat_kelas_id', $rk->id)->get();
                $ekskulGrouped = [];
                foreach ($ekskulRows as $er) {
                    $semKey = ($er->semester === 2 || strtolower($er->semester) === 'genap') ? 'Genap' : 'Ganjil';
                    if (!isset($ekskulGrouped[$er->nama_kegiatan])) {
                        $ekskulGrouped[$er->nama_kegiatan] = [
                            'nama' => $er->nama_kegiatan,
                            'nilai' => ['Ganjil' => '-', 'Genap' => '-'],
                        ];
                    }
                    $ekskulGrouped[$er->nama_kegiatan]['nilai'][$semKey] = $er->predikat ?: 'B';
                }

                // Wali Kelas
                $waliKelasNama = null;
                $waliKelasNip = null;
                $waliRel = GuruWaliKelas::where('kelas_id', $rk->kelas_id)
                    ->where('tahun_ajaran_id', $rk->tahun_ajaran_id)
                    ->with('guru')
                    ->first();
                if ($waliRel && $waliRel->guru) {
                    $waliKelasNama = $waliRel->guru->nama;
                    $waliKelasNip = $waliRel->guru->nip;
                }

                $riwayatDataList[] = [
                    'riwayatKelas'  => $rk,
                    'taNama'        => $taNama,
                    'nilaiMap'      => $nilaiMap,
                    'hadirMap'      => $hadirMap,
                    'kokurMap'      => $kokurMap,
                    'ekskulList'    => array_values($ekskulGrouped),
                    'waliKelasNama' => $waliKelasNama,
                    'waliKelasNip'  => $waliKelasNip,
                ];
            }

            // Jika siswa belum punya riwayat kelas (hanya biodata baru)
            if (empty($riwayatDataList)) {
                $riwayatDataList[] = [
                    'riwayatKelas'  => null,
                    'taNama'        => $filterTa !== 'Semua' ? $filterTa : ($taList[0] ?? '2024/2025'),
                    'nilaiMap'      => ['Ganjil' => [], 'Genap' => []],
                    'hadirMap'      => ['Ganjil' => ['sakit' => 0, 'izin' => 0, 'alpa' => 0], 'Genap' => ['sakit' => 0, 'izin' => 0, 'alpa' => 0]],
                    'kokurMap'      => ['Ganjil' => null, 'Genap' => null],
                    'ekskulList'    => [],
                    'waliKelasNama' => null,
                    'waliKelasNip'  => null,
                ];
            }

            $siswaItems[] = [
                'siswa'   => $s,
                'riwayat' => $riwayatDataList,
            ];
        }

        // 6. Cover Kelas (Sesuai prototype covers(ss, p))
        $coverKelasList = [];
        $showCover = ($isBatch || count($siswaItems) > 1 || $request->has('cover')) && ($optBio || $optNilai);
        if ($showCover) {
            foreach (array_keys($uniqueClasses) as $kNama) {
                // Cari tahun angkatan tertua di kelas ini
                $minTahun = null;
                foreach ($siswaCollection as $s) {
                    if ($s->tanggal_diterima && preg_match('/^(\d{4})/', $s->tanggal_diterima, $ym)) {
                        $y = (int)$ym[1];
                        if ($minTahun === null || $y < $minTahun) {
                            $minTahun = $y;
                        }
                    }
                }
                $coverKelasList[] = [
                    'kelas'    => $kNama,
                    'angkatan' => $minTahun ?: (date('Y') - 1),
                ];
            }
        }

        $subTitle = count($siswaItems) === 1
            ? ($siswaItems[0]['siswa']->nama_lengkap . ' · NISN ' . $siswaItems[0]['siswa']->nisn)
            : (count($siswaItems) . ' Siswa · Kurikulum Merdeka');

        return view('pdf.dokumen', compact(
            'sekolah',
            'taList',
            'optBio',
            'optNilai',
            'optHadir',
            'optEkskul',
            'optNaik',
            'filterSem',
            'reportFilterSem',
            'filterSemNumber',
            'filterTa',
            'filterKelas',
            'filterKelasList',
            'paper',
            'subTitle',
            'showCover',
            'coverKelasList',
            'siswaItems',
            'nextKelasMap'
        ));
    }
}
