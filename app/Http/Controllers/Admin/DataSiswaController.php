<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\CatatanAktivitas;
use App\Models\SekolahKelas;
use App\Models\SekolahMapel;
use App\Models\SekolahTahunAjaran;
use App\Models\SiswaBiodata;
use App\Models\SiswaRiwayatKelas;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DataSiswaController extends Controller
{
    public function index(Request $request): View
    {
        $tahunAjaranList = SekolahTahunAjaran::orderBy('nama')->get(['id', 'nama']);
        $taAktifObj = SekolahTahunAjaran::where('sedang_berjalan', 1)->first();
        $taAktifDefaultId = $taAktifObj?->id ?? $tahunAjaranList->first()?->id;
        $allYearsSelected = $request->input('tahun_ajaran') === '*';
        $tahunAjaranTerpilih = $request->filled('tahun_ajaran') && ! $allYearsSelected
            ? $tahunAjaranList->firstWhere('id', (int) $request->input('tahun_ajaran'))
            : null;
        $taAktifObj = $tahunAjaranTerpilih
            ?? $taAktifObj
            ?? $tahunAjaranList->first();
        $taAktifId = $allYearsSelected ? null : ($taAktifObj?->id ?? 0);
        $tahunAjaranTerpilihId = $allYearsSelected ? '*' : $taAktifObj?->id;
        $taAktif = $allYearsSelected ? 'Semua Tahun Ajaran' : ($taAktifObj?->nama ?? '2024/2025');

        $kelasSelected = (string) $request->input('kelas', '');
        $search = trim($request->input('q', ''));
        $statusFilter = trim($request->input('st', ''));

        $kelasList = SekolahKelas::orderBy('nama')->pluck('nama')->toArray();
        sort($kelasList, SORT_NATURAL);

        $query = $this->studentScopeQuery($taAktifId, $kelasSelected, $statusFilter, $search);
        $siswaList = $query->with(['riwayatKelas' => function (Relation $historyQuery) use ($taAktifId, $kelasSelected, $statusFilter): void {
            $this->applyHistoryFilters($historyQuery, $taAktifId, $kelasSelected, $statusFilter);
            $historyQuery->with('kelas')->orderByDesc('tahun_ajaran_id');
        }])
            ->with(['riwayatKelasAkademik' => function (Relation $historyQuery): void {
                $historyQuery->whereNull('deleted_at')
                    ->with(['kelas', 'tahunAjaran', 'nilai.mapel', 'kehadiran', 'ekskul'])
                    ->orderBy('tahun_ajaran_id');
            }])
            ->with('berkas')
            ->latest('id')
            ->paginate(25)
            ->withQueryString();
        $academicMapelOptions = SekolahMapel::query()->orderBy('nama')->get(['id', 'nama', 'kode']);

        return view('admin.data-siswa', compact(
            'taAktif',
            'tahunAjaranList',
            'tahunAjaranTerpilihId',
            'kelasList',
            'kelasSelected',
            'search',
            'statusFilter',
            'siswaList',
            'taAktifDefaultId',
            'academicMapelOptions'
        ));
    }

    public function importView(): View
    {
        $taAktifObj = SekolahTahunAjaran::where('sedang_berjalan', 1)->first()
            ?? SekolahTahunAjaran::latest('id')->first();
        $taAktif = $taAktifObj?->nama ?? '2024/2025';

        $taAktifId = $taAktifObj?->id ?? 0;
        $kelasList = SekolahKelas::orderBy('nama')->pluck('nama')->toArray();

        // Ambil riwayat batch import siswa pada tahun ini
        $riwayatImport = DB::table('siswa_riwayat_kelas as riwayat')
            ->join('sekolah_kelas as kelas', 'kelas.id', '=', 'riwayat.kelas_id')
            ->where('riwayat.tahun_ajaran_id', $taAktifId)
            ->whereNull('riwayat.deleted_at')
            ->select('kelas.nama as kelas')
            ->selectRaw('COUNT(*) as baris')
            ->groupBy('kelas.id', 'kelas.nama')
            ->orderBy('kelas.nama')
            ->get();

        return view('admin.import.siswa', compact(
            'taAktif',
            'kelasList',
            'riwayatImport'
        ));
    }

    public function export(Request $request): StreamedResponse
    {
        $taAktifId = $this->selectedAcademicYearId($request);
        $siswaList = SiswaBiodata::with(['riwayatKelas' => function ($query) use ($taAktifId) {
            if ($taAktifId !== null) {
                $query->where('tahun_ajaran_id', $taAktifId);
            }
            $query->with('kelas')->orderByDesc('tahun_ajaran_id');
        }])->orderBy('nama_lengkap')->get();

        return response()->streamDownload(function () use ($siswaList): void {
            $output = fopen('php://output', 'w');
            if ($output === false) {
                throw new RuntimeException('Tidak dapat membuka output ekspor data siswa.');
            }

            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($output, ['NISN', 'NIS', 'Nama', 'Kelas', 'Jenis Kelamin', 'Tempat Lahir', 'Tanggal Lahir', 'Agama', 'Status']);

            foreach ($siswaList as $siswa) {
                $riwayat = $siswa->riwayatKelas->first();
                fputcsv($output, [
                    $siswa->nisn,
                    $siswa->nomor_induk,
                    $siswa->nama_lengkap,
                    $riwayat?->kelas?->nama ?? '-',
                    $siswa->jenis_kelamin,
                    $siswa->tempat_lahir,
                    $siswa->tanggal_lahir,
                    $siswa->agama,
                    $riwayat?->status ?? '-',
                ]);
            }

            fclose($output);
        }, 'data-siswa-'.now()->format('Ymd-His').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function pdf(Request $request, int $id): Response
    {
        $taAktifId = $this->selectedAcademicYearId($request);
        $siswa = SiswaBiodata::with(['riwayatKelas' => function ($query) use ($taAktifId) {
            if ($taAktifId !== null) {
                $query->where('tahun_ajaran_id', $taAktifId);
            }
            $query->with(['kelas', 'tahunAjaran'])->orderByDesc('tahun_ajaran_id');
        }])->findOrFail($id);

        return Pdf::loadView('admin.siswa-pdf', compact('siswa'))
            ->setPaper('a4')
            ->stream('biodata-siswa-'.$siswa->nisn.'.pdf');
    }

    public function pdfAll(Request $request): Response
    {
        $taId = $this->selectedAcademicYearId($request);
        $kelasSelected = (string) $request->input('kelas', '');
        $statusFilter = (string) $request->input('st', '');
        $search = trim((string) $request->input('q', ''));

        $siswaList = $this->studentScopeQuery($taId, $kelasSelected, $statusFilter, $search)
            ->with(['riwayatKelas' => function (Relation $historyQuery) use ($taId, $kelasSelected, $statusFilter): void {
                $this->applyHistoryFilters($historyQuery, $taId, $kelasSelected, $statusFilter);
                $historyQuery->with(['kelas', 'tahunAjaran'])->orderByDesc('tahun_ajaran_id');
            }])
            ->orderBy('nama_lengkap')
            ->get();

        $tahunAjaranLabel = $taId === null
            ? 'Semua Tahun Ajaran'
            : (SekolahTahunAjaran::find($taId)?->nama ?? '-');
        $kelasLabel = $kelasSelected !== '' && $kelasSelected !== '*'
            ? $kelasSelected
            : 'Semua Kelas';

        return Pdf::loadView('admin.siswa-daftar-pdf', compact('siswaList', 'tahunAjaranLabel', 'kelasLabel'))
            ->setPaper('a4', 'landscape')
            ->stream('daftar-siswa-'.now()->format('Ymd-His').'.pdf');
    }

    public function foto(int $id): BinaryFileResponse
    {
        $siswa = SiswaBiodata::findOrFail($id);
        $path = $siswa->foto;
        abort_if(! $path, 404);

        $headers = [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ];

        if (str_starts_with($path, 'siswa/foto/')) {
            $filename = substr($path, strlen('siswa/foto/'));
            abort_if(
                $filename === ''
                    || basename($filename) !== $filename
                    || ! in_array(strtolower(pathinfo($filename, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp'], true),
                404
            );

            $disk = Storage::disk('local');
            abort_unless($disk->exists($path), 404);

            return response()->file($disk->path($path), $headers);
        }

        if (str_starts_with($path, 'uploads/foto/')) {
            $filename = substr($path, strlen('uploads/foto/'));
            abort_if(
                $filename === ''
                    || basename($filename) !== $filename
                    || ! in_array(strtolower(pathinfo($filename, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp'], true),
                404
            );

            $legacyPath = public_path('uploads/foto/'.$filename);
            abort_unless(is_file($legacyPath), 404);

            return response()->file($legacyPath, $headers);
        }

        abort(404);
    }

    public function berkas(int $id, string $jenis): StreamedResponse
    {
        abort_unless(in_array($jenis, ['kk', 'akta', 'ijazah_smp', 'surat_pindah'], true), 404);

        $berkas = SiswaBiodata::findOrFail($id)
            ->berkas()
            ->where('jenis', $jenis)
            ->firstOrFail();
        $disk = Storage::disk('local');
        abort_unless($disk->exists($berkas->lokasi), 404);

        return $disk->download($berkas->lokasi, $berkas->nama_file, [
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function show(int $id): View
    {
        $siswa = $this->studentWithDetails($id);
        $academicMapelOptions = SekolahMapel::query()->orderBy('nama')->get(['id', 'nama', 'kode']);

        return view('admin.siswa-detail', compact('siswa', 'academicMapelOptions'));
    }

    private function studentWithDetails(int $id): SiswaBiodata
    {
        return SiswaBiodata::with([
            'riwayatKelas' => function ($query): void {
                $query->with(['kelas', 'tahunAjaran', 'nilai.mapel', 'kehadiran', 'ekskul'])->orderBy('tahun_ajaran_id');
            },
            'berkas',
        ])->findOrFail($id);
    }

    private function studentScopeQuery(?int $tahunAjaranId, string $kelas, string $status, string $search): Builder
    {
        $query = SiswaBiodata::query();
        if ($tahunAjaranId !== null || ($kelas !== '' && $kelas !== '*') || $status !== '') {
            $query->whereHas('riwayatKelas', function (Builder $historyQuery) use ($tahunAjaranId, $kelas, $status): void {
                $this->applyHistoryFilters($historyQuery, $tahunAjaranId, $kelas, $status);
            });
        }

        if ($search !== '') {
            $query->where(function (Builder $searchQuery) use ($search): void {
                $searchQuery->where('nama_lengkap', 'like', "%{$search}%")
                    ->orWhere('nisn', 'like', "%{$search}%")
                    ->orWhere('nomor_induk', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    private function applyHistoryFilters(Builder|Relation $query, ?int $tahunAjaranId, string $kelas, string $status): void
    {
        if ($tahunAjaranId !== null) {
            $query->where('tahun_ajaran_id', $tahunAjaranId);
        }

        if ($kelas !== '' && $kelas !== '*') {
            $query->whereHas('kelas', fn (Builder $kelasQuery) => $kelasQuery->where('nama', $kelas));
        }

        if ($status !== '') {
            $query->where('status', $status);
        }
    }

    public function bulkUpdate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tahun_ajaran' => ['required', 'integer', 'exists:sekolah_tahun_ajaran,id'],
            'kelas' => ['nullable', 'string', 'max:64'],
            'st' => ['nullable', 'in:Aktif,Pindah keluar,Keluar,Lulus'],
            'q' => ['nullable', 'string', 'max:150'],
            'siswa_ids' => ['sometimes', 'array', 'min:1'],
            'siswa_ids.*' => ['required', 'integer', 'distinct', 'exists:siswa_biodata,id'],
            'target_tahun_ajaran' => ['required', 'integer', 'exists:sekolah_tahun_ajaran,id'],
            'target_kelas' => ['required', 'string', 'exists:sekolah_kelas,nama'],
            'target_status' => ['nullable', 'in:Aktif,Pindah keluar,Keluar,Lulus'],
        ]);

        $sourceYearId = (int) $validated['tahun_ajaran'];
        $sourceClass = $validated['kelas'] ?? '';
        if ($sourceClass !== '' && $sourceClass !== '*' && ! SekolahKelas::where('nama', $sourceClass)->exists()) {
            return back()->with('error', 'Filter kelas tidak valid.');
        }
        $sourceStatus = $validated['st'] ?? '';
        $search = trim($validated['q'] ?? '');
        $targetYearId = (int) $validated['target_tahun_ajaran'];
        $targetClassId = (int) SekolahKelas::where('nama', $validated['target_kelas'])->value('id');
        $targetStatus = $validated['target_status'] ?? null;

        $studentsQuery = $this->studentScopeQuery($sourceYearId, $sourceClass, $sourceStatus, $search);
        if (isset($validated['siswa_ids'])) {
            $studentsQuery->whereIn('id', $validated['siswa_ids']);
        }

        $students = $studentsQuery
            ->with(['riwayatKelas' => function (Relation $historyQuery) use ($sourceYearId, $sourceClass, $sourceStatus): void {
                $this->applyHistoryFilters($historyQuery, $sourceYearId, $sourceClass, $sourceStatus);
            }])
            ->get();

        if ($students->isEmpty()) {
            return back()->with('error', 'Tidak ada siswa yang cocok dengan filter untuk diedit.');
        }

        $targetYear = SekolahTahunAjaran::findOrFail($targetYearId);

        DB::transaction(function () use ($students, $targetYearId, $targetClassId, $targetStatus, $targetYear, $validated, $request): void {
            foreach ($students as $student) {
                $sourceHistory = $student->riwayatKelas->first();
                if (! $sourceHistory) {
                    continue;
                }

                SiswaRiwayatKelas::updateOrCreate(
                    [
                        'siswa_id' => $student->id,
                        'tahun_ajaran_id' => $targetYearId,
                    ],
                    [
                        'kelas_id' => $targetClassId,
                        'status' => $targetStatus ?? $sourceHistory->status,
                    ]
                );
            }

            CatatanAktivitas::create([
                'pengguna_id' => auth()->id(),
                'peran' => auth()->user()->peran,
                'aksi' => 'pindah_kelas_massal',
                'keterangan' => "Memindahkan {$students->count()} siswa ke kelas {$validated['target_kelas']} tahun ajaran {$targetYear->nama}.",
                'objek_tipe' => 'siswa',
                'alamat_ip' => $request->ip(),
            ]);
        });

        return back()->with(
            'success',
            "{$students->count()} siswa berhasil diperbarui ke kelas {$validated['target_kelas']} tahun ajaran {$targetYear->nama}."
        );
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tahun_ajaran' => ['required', 'string'],
            'kelas' => ['nullable', 'string', 'max:64'],
            'st' => ['nullable', 'in:Aktif,Pindah keluar,Keluar,Lulus'],
            'q' => ['nullable', 'string', 'max:150'],
            'konfirmasi' => ['required', 'in:hapus'],
            'siswa_ids' => ['sometimes', 'array', 'min:1'],
            'siswa_ids.*' => ['required', 'integer', 'distinct', 'exists:siswa_biodata,id'],
        ]);

        $selectedYear = $validated['tahun_ajaran'];
        $yearId = null;
        if ($selectedYear !== '*') {
            if (! ctype_digit($selectedYear) || ! SekolahTahunAjaran::whereKey((int) $selectedYear)->exists()) {
                return back()->with('error', 'Filter tahun ajaran tidak valid.');
            }
            $yearId = (int) $selectedYear;
        }

        $selectedClass = $validated['kelas'] ?? '';
        if ($selectedClass !== '' && $selectedClass !== '*' && ! SekolahKelas::where('nama', $selectedClass)->exists()) {
            return back()->with('error', 'Filter kelas tidak valid.');
        }
        if ($yearId === null && ($selectedClass === '' || $selectedClass === '*') && ! isset($validated['siswa_ids'])) {
            return back()->with('error', 'Pilih tahun ajaran tertentu atau kelas tertentu agar penghapusan massal memiliki batas yang jelas.');
        }

        $query = $this->studentScopeQuery(
            $yearId,
            $selectedClass,
            $validated['st'] ?? '',
            trim($validated['q'] ?? '')
        );
        if (isset($validated['siswa_ids'])) {
            $query->whereIn('id', $validated['siswa_ids']);
        }
        $deletedCount = 0;
        DB::transaction(function () use ($query, &$deletedCount, $request): void {
            $query->orderBy('id')->chunkById(200, function ($students) use (&$deletedCount): void {
                foreach ($students as $student) {
                    $student->delete();
                    $deletedCount++;
                }
            });

            if ($deletedCount > 0) {
                CatatanAktivitas::create([
                    'pengguna_id' => auth()->id(),
                    'peran' => auth()->user()->peran,
                    'aksi' => 'hapus_siswa_massal',
                    'keterangan' => "Memindahkan {$deletedCount} data siswa ke Tempat Sampah melalui aksi massal.",
                    'objek_tipe' => 'siswa',
                    'alamat_ip' => $request->ip(),
                ]);
            }
        });

        if ($deletedCount === 0) {
            return back()->with('error', 'Tidak ada siswa yang cocok dengan filter untuk dihapus.');
        }

        return back()->with('success', "{$deletedCount} data siswa berhasil dipindahkan ke Tempat Sampah.");
    }

    private function selectedAcademicYearId(Request $request): ?int
    {
        $selectedId = $request->input('tahun_ajaran');
        if ($selectedId === '*') {
            return null;
        }

        if (is_numeric($selectedId) && SekolahTahunAjaran::whereKey($selectedId)->exists()) {
            return (int) $selectedId;
        }

        return (int) (
            SekolahTahunAjaran::where('sedang_berjalan', 1)->value('id')
            ?? SekolahTahunAjaran::orderBy('nama')->value('id')
            ?? 0
        );
    }

    public function destroy(int $id)
    {
        $siswa = SiswaBiodata::findOrFail($id);
        $nama = $siswa->nama_lengkap ?? 'Siswa';
        $siswa->delete(); // Soft delete ke tempat sampah

        return back()->with('success', "Data {$nama} berhasil dipindahkan ke Tempat Sampah.");
    }
}
