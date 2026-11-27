<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Guru\GuruWaliKelas;
use App\Models\SekolahIdentitas;
use App\Models\SekolahKelas;
use App\Models\SekolahTahunAjaran;
use App\Models\SiswaBiodata;
use App\Models\SiswaEkskul;
use App\Models\SiswaKehadiran;
use App\Models\SiswaNilai;
use App\Models\SiswaRiwayatKelas;
use App\Services\AksesGuru;
use App\Support\Semester;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PdfCetakController extends Controller
{
    private const SLOT_OPTIONS = [
        'X-1' => ['tingkat' => 'X', 'jenis' => 'Ganjil'],
        'X-2' => ['tingkat' => 'X', 'jenis' => 'Genap'],
        'XI-1' => ['tingkat' => 'XI', 'jenis' => 'Ganjil'],
        'XI-2' => ['tingkat' => 'XI', 'jenis' => 'Genap'],
        'XII-1' => ['tingkat' => 'XII', 'jenis' => 'Ganjil'],
        'XII-2' => ['tingkat' => 'XII', 'jenis' => 'Genap'],
    ];

    /**
     * Cetak dokumen PDF satu siswa (Register, Nilai, Hadir, Ekskul, Kenaikan).
     */
    public function single(Request $request, int|string $id, AksesGuru $aksesGuru): View
    {
        $siswa = SiswaBiodata::where('id', $id)
            ->orWhere('nisn', $id)
            ->firstOrFail();

        $this->authorizeTeacherStudents($request, [$siswa->id], $aksesGuru);

        return $this->renderDocumentView($request, [$siswa->id], false);
    }

    /**
     * Cetak dokumen PDF batch banyak siswa atau satu kelas penuh.
     */
    public function batch(Request $request, AksesGuru $aksesGuru): View
    {
        $idsInput = $request->input('ids', []);
        if (is_string($idsInput)) {
            $idsInput = array_filter(array_map('trim', explode(',', $idsInput)), static fn (string $id): bool => $id !== '');
        }

        $request->merge(['ids' => $idsInput]);
        $selection = $request->validate([
            'ids' => ['sometimes', 'array'],
            'ids.*' => ['required', 'integer', 'distinct', 'exists:siswa_biodata,id'],
            'target_kelas' => ['sometimes', 'nullable', 'string', 'max:100'],
            'target_ta' => ['sometimes', 'nullable', 'string', 'max:30'],
            'kelas' => ['sometimes', 'nullable', 'string', 'max:100'],
            'ta' => ['sometimes', 'nullable', 'string', 'max:30'],
            'q' => ['sometimes', 'nullable', 'string', 'max:150'],
            'st' => ['sometimes', 'nullable', 'in:Aktif'],
            'tab' => ['sometimes', 'in:mapel,wali'],
        ]);
        $ids = array_map('intval', $selection['ids'] ?? []);

        $kelas = (string) $request->input('target_kelas', $request->input('kelas', ''));
        $ta = $request->input('target_ta');
        if ($ta === null) {
            $ta = $request->input('ta', 'Semua');
        }

        if ($ids === [] && $kelas !== '') {
            $ids = $this->studentIdsForTarget($request, $kelas, $ta, $aksesGuru);
        }

        $this->authorizeTeacherStudents($request, $ids, $aksesGuru);

        return $this->renderDocumentView($request, $ids, true);
    }

    /**
     * Data dokumen cetak yang sama persis dengan halaman cetak web (register biodata, capaian
     * nilai, kokurikuler, ekskul, kehadiran, kenaikan kelas), dipakai ulang oleh PDF aplikasi
     * mobile agar isinya identik. Pemeriksaan hak akses menjadi tanggung jawab pemanggil:
     * hanya kirim id siswa yang memang boleh dibaca.
     *
     * @param  array<int, int>  $siswaIds
     * @return array<string, mixed>
     */
    public function dataDokumen(Request $request, array $siswaIds): array
    {
        return $this->renderDocumentView($request, $siswaIds, false)->getData();
    }

    /**
     * @return array<int, int>
     */
    private function studentIdsForTarget(Request $request, string $kelas, mixed $tahunAjaran, AksesGuru $aksesGuru): array
    {
        abort_unless(
            $kelas === '*' || SekolahKelas::query()->where('nama', $kelas)->exists(),
            422,
            'Kelas cetak tidak ditemukan.'
        );
        $this->authorizeTeacherClassFilter($request, $aksesGuru, $kelas);

        $allYears = in_array($tahunAjaran, ['*', 'Semua', null, ''], true);
        if (! $allYears) {
            $yearExists = is_numeric($tahunAjaran)
                ? SekolahTahunAjaran::query()->whereKey((int) $tahunAjaran)->exists()
                : SekolahTahunAjaran::query()->where('nama', (string) $tahunAjaran)->exists();
            abort_unless($yearExists, 422, 'Tahun ajaran cetak tidak ditemukan.');
        }

        $status = (string) $request->input('st', '');
        $hasHistoryFilter = $kelas !== '*' || ! $allYears || $status !== '';

        $yearId = is_numeric($tahunAjaran) && (int) $tahunAjaran > 0
            ? (int) $tahunAjaran
            : null;
        $query = $request->user()?->peran === 'guru'
            ? $this->teacherStudentsQuery($request, $aksesGuru)
            : SiswaBiodata::query();
        if ($request->user()?->peran === 'guru' && $yearId !== null) {
            $query->whereHas('riwayatKelas', fn (Builder $historyQuery) => $historyQuery
                ->where('tahun_ajaran_id', $yearId)
                ->where('status', 'Aktif'));
        }
        if ($hasHistoryFilter) {
            $query->whereHas('riwayatKelas', function (Builder $historyQuery) use ($kelas, $tahunAjaran, $allYears, $status): void {
                if ($kelas !== '*') {
                    $historyQuery->whereHas('kelas', fn (Builder $classQuery) => $classQuery->where('nama', $kelas));
                }

                if (! $allYears) {
                    $historyQuery->whereHas('tahunAjaran', function (Builder $yearQuery) use ($tahunAjaran): void {
                        if (is_numeric($tahunAjaran)) {
                            $yearQuery->whereKey((int) $tahunAjaran);
                        } else {
                            $yearQuery->where('nama', (string) $tahunAjaran);
                        }
                    });
                }

                if ($status !== '') {
                    $historyQuery->where('status', $status);
                }
            });
        }

        $search = trim((string) $request->input('q', ''));
        if ($search !== '') {
            $query->where(function (Builder $studentQuery) use ($search): void {
                $studentQuery->where('nama_lengkap', 'like', "%{$search}%")
                    ->orWhere('nisn', 'like', "%{$search}%")
                    ->orWhere('nomor_induk', 'like', "%{$search}%");
            });
        }

        return $query->pluck('id')->map(static fn ($id): int => (int) $id)->all();
    }

    /**
     * @param  array<int, int>  $studentIds
     */
    private function authorizeTeacherStudents(Request $request, array $studentIds, AksesGuru $aksesGuru): void
    {
        if ($request->user()?->peran !== 'guru' || $studentIds === []) {
            return;
        }

        $query = $this->teacherStudentsQuery($request, $aksesGuru);
        $requestedYear = $request->input('tahun_ajaran', $request->input('target_ta'));
        $yearId = is_numeric($requestedYear) && (int) $requestedYear > 0 ? (int) $requestedYear : null;

        $kelas = (string) $request->input('target_kelas', $request->input('kelas', ''));
        $this->authorizeTeacherClassFilter($request, $aksesGuru, $kelas);
        if (($kelas !== '' && $kelas !== '*') || $yearId !== null) {
            $query->whereHas('riwayatKelas', function (Builder $historyQuery) use ($kelas, $yearId): void {
                if ($kelas !== '' && $kelas !== '*') {
                    $historyQuery->whereHas('kelas', fn (Builder $classQuery) => $classQuery->where('nama', $kelas));
                }

                if ($yearId !== null) {
                    $historyQuery->where('tahun_ajaran_id', $yearId);
                }
            });
        }

        $visibleCount = $query->whereIn('id', $studentIds)->distinct()->count('siswa_biodata.id');
        abort_unless($visibleCount === count(array_unique($studentIds)), 403);
    }

    private function authorizeTeacherClassFilter(Request $request, AksesGuru $aksesGuru, string $kelas): void
    {
        if ($request->user()?->peran !== 'guru' || $kelas === '' || $kelas === '*') {
            return;
        }

        $lingkup = $request->input('tab', 'mapel');
        abort_unless(in_array($lingkup, ['mapel', 'wali'], true), 422);
        $kelasDiizinkan = $aksesGuru->kelasUntukDataSiswa($request->user(), $lingkup)
            ->pluck('nama')
            ->all();

        abort_unless(in_array($kelas, $kelasDiizinkan, true), 403);
    }

    private function teacherStudentsQuery(Request $request, AksesGuru $aksesGuru): Builder
    {
        $lingkup = $request->input('tab', 'mapel');
        abort_unless(in_array($lingkup, ['mapel', 'wali'], true), 422);

        $kelasIds = $aksesGuru->kelasUntukDataSiswa($request->user(), $lingkup)->modelKeys();
        $tahunAjaranId = SekolahTahunAjaran::query()->where('sedang_berjalan', true)->value('id');
        abort_if($kelasIds === [] || $tahunAjaranId === null, 403);

        return SiswaBiodata::aktif((int) $tahunAjaranId)
            ->whereHas('riwayatKelas', function (Builder $historyQuery) use ($kelasIds, $tahunAjaranId): void {
                $historyQuery->where('tahun_ajaran_id', $tahunAjaranId)
                    ->where('status', 'Aktif')
                    ->whereIn('kelas_id', $kelasIds);
            });
    }

    /**
     * Core builder data dokumen cetak sesuai format prototype Kurikulum Merdeka.
     */
    private function renderDocumentView(Request $request, array $siswaIds, bool $isBatch): View
    {
        $request->validate([
            'bio' => ['sometimes', 'boolean'],
            'nilai' => ['sometimes', 'boolean'],
            'hadir' => ['sometimes', 'boolean'],
            'ekskul' => ['sometimes', 'boolean'],
            'naik' => ['sometimes', 'boolean'],
            'filter_applied' => ['sometimes', 'boolean'],
            'sem' => ['sometimes', 'in:Semua,Ganjil,Genap,1,2,3,4,5,6'],
            'ta' => ['sometimes', 'nullable', 'string', 'max:30'],
            'kelas_filter' => ['sometimes', 'nullable', 'string', 'max:100'],
            'kelas' => ['sometimes', 'nullable', 'string', 'max:100'],
            'tahun_ajaran' => ['sometimes', 'nullable', 'integer', 'exists:sekolah_tahun_ajaran,id'],
            'target_kelas' => ['sometimes', 'nullable', 'string', 'max:100'],
            'target_ta' => ['sometimes', 'nullable', 'string', 'max:30'],
            'q' => ['sometimes', 'nullable', 'string', 'max:150'],
            'st' => ['sometimes', 'nullable', 'in:Aktif'],
            'tab' => ['sometimes', 'in:mapel,wali'],
            'slots_present' => ['sometimes', 'boolean'],
            'slots' => ['sometimes', 'array'],
            'slots.*' => ['string', 'in:X-1,X-2,XI-1,XI-2,XII-1,XII-2'],
            'hide_empty' => ['sometimes', 'boolean'],
            'paper' => ['sometimes', 'in:F4,A4'],
        ]);

        // 1. Data Identitas Sekolah
        $sekolah = SekolahIdentitas::first();
        if (! $sekolah) {
            $sekolah = (object) [
                'nama' => 'SMA Negeri 1 Rejoso Nganjuk',
                'npsn' => '20538396',
                'alamat' => 'Jalan Yos Sudarso',
                'desa' => 'Sidokare',
                'kelurahan' => 'Sidokare',
                'kecamatan' => 'Rejoso',
                'kabupaten' => 'Nganjuk',
                'provinsi' => 'Jawa Timur',
                'nama_kepala_sekolah' => null,
                'nip_kepala_sekolah' => null,
            ];
        } else {
            $namaSekolahNormal = trim((string) ($sekolah->nama ?? ''));
            if ($namaSekolahNormal === '' || str_contains(strtolower($namaSekolahNormal), 'contoh')) {
                $sekolah->nama = 'SMA Negeri 1 Rejoso Nganjuk';
            }
        }

        // 2. Daftar Tahun Ajaran
        $taList = SekolahTahunAjaran::pluck('nama')->toArray();
        if (empty($taList)) {
            $taList = ['2023/2024', '2024/2025', '2025/2026'];
        }

        // 3. Parsing Pilihan Konten (Default: Semua aktif jika pertama buka)
        $hasQueryParams = $request->has('filter_applied') || $request->has('bio') || $request->has('nilai') || $request->has('hadir') || $request->has('ekskul') || $request->has('naik');

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
        $reportFilterSem = $filterSemNumber === null ? $filterSem : Semester::jenisDariNomor($filterSemNumber);
        $filterTa = $request->input('ta', 'Semua');
        $filterKelas = (string) $request->input('kelas_filter', 'Semua');
        $filterKelasList = SekolahKelas::orderBy('nama')->pluck('nama')->toArray();
        $paper = $request->input('paper', 'F4');
        $selectedSlots = $this->selectedSlots($request, $filterSem, $filterKelas);
        $slotTermsByGrade = [];
        foreach ($selectedSlots as $slot) {
            $option = self::SLOT_OPTIONS[$slot];
            $slotTermsByGrade[$option['tingkat']][] = $option['jenis'];
        }
        $hideEmptySlots = $request->boolean('hide_empty');

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

        $tingkatMapel = [];
        $mapelKurikulumRows = DB::table('sekolah_mapel_per_tingkat as kurikulum')
            ->join('sekolah_mapel as mapel', 'mapel.id', '=', 'kurikulum.mapel_id')
            ->orderBy('kurikulum.tingkat')
            ->orderBy('kurikulum.urutan')
            ->orderBy('mapel.nama')
            ->get([
                'kurikulum.tingkat',
                'kurikulum.kelompok_mapel',
                'mapel.kode',
                'mapel.nama',
            ]);

        foreach ($mapelKurikulumRows as $mapelKurikulum) {
            $tingkatMapel[$mapelKurikulum->tingkat][$mapelKurikulum->kelompok_mapel][$mapelKurikulum->kode] = $mapelKurikulum->nama;
        }

        // 5. Query Siswa & Bangun Data Lengkap
        $siswaCollection = SiswaBiodata::whereIn('id', $siswaIds)
            ->whereNull('deleted_at')
            ->with(['riwayatKelas' => function ($query) {
                $query->whereNull('deleted_at')->with(['kelas', 'kelasTujuan', 'tahunAjaran']);
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
            $riwayatDataList = [];
            $coveredGrades = [];
            foreach ($riwayatSorted as $rk) {
                $kNama = $rk->kelas?->nama ?? 'X-1';
                $taNama = $rk->tahunAjaran?->nama ?? '2024/2025';
                $grade = strtoupper((string) ($rk->kelas?->tingkat ?? Semester::tingkatDariNamaKelas($kNama) ?? ''));
                $selectedTerms = $slotTermsByGrade[$grade] ?? [];
                if ($selectedTerms === []) {
                    continue;
                }

                $uniqueClasses[$kNama] = true;
                $coveredGrades[$grade] = true;

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
                    if (! isset($ekskulGrouped[$er->nama_kegiatan])) {
                        $ekskulGrouped[$er->nama_kegiatan] = [
                            'nama' => $er->nama_kegiatan,
                            'nilai' => ['Ganjil' => '-', 'Genap' => '-'],
                        ];
                    }
                    $ekskulGrouped[$er->nama_kegiatan]['nilai'][$semKey] = $er->predikat ?: 'B';
                }

                // Wali Kelas
                $waliKelasNama = $rk->nama_wali_kelas;
                $waliKelasNip = $rk->nip_wali_kelas;
                if (! filled($waliKelasNama) || ! filled($waliKelasNip)) {
                    $waliRel = GuruWaliKelas::where('kelas_id', $rk->kelas_id)
                        ->where('tahun_ajaran_id', $rk->tahun_ajaran_id)
                        ->with('guru')
                        ->first();
                    $waliKelasNama = filled($waliKelasNama) ? $waliKelasNama : $waliRel?->guru?->nama;
                    $waliKelasNip = filled($waliKelasNip) ? $waliKelasNip : $waliRel?->guru?->nip;
                }

                $emptySemesters = $this->emptySemesters(
                    $selectedTerms,
                    $nilaiMap,
                    $hadirRows,
                    $ekskulRows,
                    $optNaik && filled($rk->keputusan_kenaikan)
                );
                $reportTerms = $hideEmptySlots
                    ? array_values(array_diff($selectedTerms, $emptySemesters))
                    : $selectedTerms;
                if ($reportTerms === []) {
                    continue;
                }

                $riwayatDataList[] = [
                    'riwayatKelas' => $rk,
                    'kelasTujuanNama' => $rk->kelasTujuan?->nama,
                    'taNama' => $taNama,
                    'nilaiMap' => $nilaiMap,
                    'hadirMap' => $hadirMap,
                    'kokurMap' => $kokurMap,
                    'ekskulList' => array_values($ekskulGrouped),
                    'waliKelasNama' => $waliKelasNama,
                    'waliKelasNip' => $waliKelasNip,
                    'slotSemesters' => $reportTerms,
                    'emptySemesters' => $hideEmptySlots ? [] : $emptySemesters,
                ];
            }

            if ($filterTa === 'Semua' && $filterKelas === 'Semua') {
                foreach (['X', 'XI', 'XII'] as $grade) {
                    $selectedTerms = $slotTermsByGrade[$grade] ?? [];
                    if ($selectedTerms === [] || isset($coveredGrades[$grade])) {
                        continue;
                    }

                    $emptyHistory = new SiswaRiwayatKelas;
                    $emptyHistory->setRelation('kelas', new SekolahKelas([
                        'nama' => $grade,
                        'tingkat' => $grade,
                    ]));
                    $riwayatDataList[] = [
                        'riwayatKelas' => $emptyHistory,
                        'kelasTujuanNama' => null,
                        'taNama' => 'Belum ada data',
                        'nilaiMap' => ['Ganjil' => [], 'Genap' => []],
                        'hadirMap' => ['Ganjil' => ['sakit' => 0, 'izin' => 0, 'alpa' => 0], 'Genap' => ['sakit' => 0, 'izin' => 0, 'alpa' => 0]],
                        'kokurMap' => ['Ganjil' => null, 'Genap' => null],
                        'ekskulList' => [],
                        'waliKelasNama' => null,
                        'waliKelasNip' => null,
                        'slotSemesters' => $selectedTerms,
                        'emptySemesters' => $selectedTerms,
                    ];
                }
            }

            $riwayatDataList = array_values(array_filter($riwayatDataList, function (array $historyData) use ($hideEmptySlots): bool {
                if ($hideEmptySlots) {
                    return count($historyData['emptySemesters']) < count($historyData['slotSemesters']);
                }

                return true;
            }));

            $siswaItems[] = [
                'siswa' => $s,
                'riwayat' => $riwayatDataList,
            ];
        }

        // Sampul selalu menjadi halaman pertama. Kelas dan angkatan dihitung dari
        // riwayat/data siswa aktual, bukan nilai sampel yang sama untuk semua siswa.
        $coverKelas = null;
        $coverAngkatan = null;
        foreach ($siswaCollection as $s) {
            $riwayatAktif = $s->riwayatKelas
                ->filter(fn (SiswaRiwayatKelas $rk): bool => $rk->status === 'Aktif')
                ->sortBy(fn (SiswaRiwayatKelas $rk): string => $rk->tahunAjaran?->nama ?? '')
                ->last();
            $riwayatTerbaru = $riwayatAktif
                ?? $s->riwayatKelas
                    ->sortBy(fn (SiswaRiwayatKelas $rk): string => $rk->tahunAjaran?->nama ?? '')
                    ->last();

            if ($riwayatTerbaru?->kelas?->nama) {
                $coverKelas = $riwayatTerbaru->kelas->nama;
            }

            $angkatanCandidates = [];
            if ($s->tanggal_diterima && preg_match('/^(\d{4})/', (string) $s->tanggal_diterima, $match)) {
                $angkatanCandidates[] = (int) $match[1];
            }
            if ($riwayatTerbaru?->tahunAjaran?->nama
                && preg_match('/^(\d{4})/', (string) $riwayatTerbaru->tahunAjaran->nama, $match)) {
                $angkatanCandidates[] = (int) $match[1];
            }
            if ($angkatanCandidates !== []) {
                $angkatan = min($angkatanCandidates);
                $coverAngkatan = $coverAngkatan === null ? $angkatan : min($coverAngkatan, $angkatan);
            }
        }
        $coverKelasList = [[
            'kelas' => $coverKelas ?: 'X-1',
            'angkatan' => $coverAngkatan ?: 2023,
        ]];
        $showCover = true;

        $kktp = DB::table('sekolah_pengaturan')
            ->where('kunci', 'kktp')
            ->value('isi') ?? 75;

        $subTitle = count($siswaItems) === 1
            ? ($siswaItems[0]['siswa']->nama_lengkap.' · NISN '.$siswaItems[0]['siswa']->nisn)
            : (count($siswaItems).' Siswa · Kurikulum Merdeka');

        return view('pdf.dokumen', compact(
            'sekolah',
            'kktp',
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
            'selectedSlots',
            'hideEmptySlots',
            'paper',
            'tingkatMapel',
            'subTitle',
            'showCover',
            'coverKelasList',
            'siswaItems',
            'nextKelasMap'
        ));
    }

    /**
     * @return array<int, string>
     */
    private function selectedSlots(Request $request, string $filterSem, string $filterKelas): array
    {
        if ($request->exists('slots_present')) {
            $slots = $request->input('slots', []);
            if (! is_array($slots)) {
                return [];
            }

            return array_values(array_intersect(array_keys(self::SLOT_OPTIONS), $slots));
        }

        if (ctype_digit($filterSem) && $filterSem !== '0') {
            $semester = Semester::dari((int) $filterSem);
            $selected = $semester['tingkat'].'-'.($semester['jenis'] === 'Ganjil' ? '1' : '2');
        } elseif (in_array($filterSem, ['Ganjil', 'Genap'], true)) {
            $selected = array_keys(array_filter(self::SLOT_OPTIONS, static fn (array $slot): bool => $slot['jenis'] === $filterSem));
        } else {
            $selected = array_keys(self::SLOT_OPTIONS);
        }

        if (is_string($selected)) {
            $selected = [$selected];
        }

        if ($filterKelas !== 'Semua') {
            $grade = Semester::tingkatDariNamaKelas($filterKelas);
            if ($grade !== null) {
                $selected = array_values(array_filter($selected, static fn (string $slot): bool => self::SLOT_OPTIONS[$slot]['tingkat'] === $grade));
            }
        }

        return $selected;
    }

    /** @param array<string, array<string, mixed>> $nilaiMap
     * @param  array<int, string>  $selectedTerms
     * @return array<int, string>
     */
    private function emptySemesters(
        array $selectedTerms,
        array $nilaiMap,
        EloquentCollection $hadirRows,
        EloquentCollection $ekskulRows,
        bool $hasPromotionData
    ): array {
        $emptySemesters = [];
        foreach ($selectedTerms as $term) {
            $hasRecords = ! empty($nilaiMap[$term])
                || $hadirRows->contains(fn (SiswaKehadiran $row): bool => $this->semesterTerm($row->semester) === $term)
                || $ekskulRows->contains(fn (SiswaEkskul $row): bool => $this->semesterTerm($row->semester) === $term)
                || $hasPromotionData;
            if (! $hasRecords) {
                $emptySemesters[] = $term;
            }
        }

        return $emptySemesters;
    }

    private function semesterTerm(string|int $semester): string
    {
        return $semester === 2 || strtolower((string) $semester) === 'genap' ? 'Genap' : 'Ganjil';
    }
}
