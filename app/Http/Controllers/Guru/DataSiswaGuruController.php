<?php

namespace App\Http\Controllers\Guru;

use App\Exports\FormattedTableExport;
use App\Http\Controllers\Controller;
use App\Models\SekolahKelas;
use App\Models\SekolahTahunAjaran;
use App\Models\SiswaBiodata;
use App\Services\AcademicRecordAccess;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DataSiswaGuruController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $this->validatedFilters($request);
        $siswa = $this->filteredStudents($filters)
            ->paginate(25)
            ->withQueryString();

        $kelasList = SekolahKelas::orderBy('nama')->pluck('nama')->all();
        sort($kelasList, SORT_NATURAL);

        return view('guru.data-siswa', compact(
            'siswa',
            'kelasList',
            'filters'
        ));
    }

    public function export(Request $request): BinaryFileResponse
    {
        $filters = $this->validatedFilters($request);
        $siswa = $this->filteredStudents($filters)->get();

        $rows = $siswa->map(function (SiswaBiodata $murid): array {
            $riwayat = $murid->riwayatKelas->first();

            return [
                $murid->nisn,
                $murid->nomor_induk,
                $murid->nama_lengkap,
                $riwayat?->kelas?->nama ?? '-',
                $murid->jenis_kelamin,
                $murid->tempat_lahir,
                $murid->tanggal_lahir,
                $murid->agama,
                $murid->nama_ayah ?? $murid->nama_ibu,
                $riwayat?->status ?? '-',
            ];
        })->all();

        return Excel::download(
            new FormattedTableExport(
                'Data Siswa',
                ['NISN', 'NIS', 'Nama', 'Kelas', 'Jenis Kelamin', 'Tempat Lahir', 'Tanggal Lahir', 'Agama', 'Orang Tua', 'Status'],
                $rows,
                ['A' => 16, 'B' => 16, 'C' => 32, 'D' => 18, 'E' => 18, 'F' => 22, 'G' => 18, 'H' => 18, 'I' => 28, 'J' => 20]
            ),
            'data-siswa-guru-'.now()->format('Ymd-His').'.xlsx',
            ExcelWriter::XLSX
        );
    }

    public function pdfAll(Request $request): Response
    {
        $filters = $this->validatedFilters($request);
        $siswaList = $this->filteredStudents($filters)->get();

        return $this->renderListPdf($siswaList, $filters);
    }

    public function pdfSelected(Request $request): Response
    {
        $filters = $this->validatedFilters($request);
        $selection = $request->validate([
            'siswa_ids' => ['required', 'array', 'min:1'],
            'siswa_ids.*' => ['required', 'integer', 'distinct', 'exists:siswa_biodata,id'],
        ]);
        $siswaList = $this->filteredStudents($filters)
            ->whereIn('id', $selection['siswa_ids'])
            ->get();

        abort_if($siswaList->isEmpty(), 404);

        return $this->renderListPdf($siswaList, $filters);
    }

    public function show(int $id, Request $request, AcademicRecordAccess $access): View
    {
        $siswa = SiswaBiodata::aktif()->with(['riwayatKelas' => function ($query): void {
            $query->with(['kelas', 'tahunAjaran', 'nilai.mapel', 'kehadiran', 'ekskul'])->orderByDesc('tahun_ajaran_id');
        }])->findOrFail($id);
        $academicManagePermissions = [];
        foreach ($siswa->riwayatKelas as $history) {
            $academicManagePermissions[$history->id] = $access->permissionsFor($request->user(), $history);
        }

        return view('guru.data-siswa-detail', compact('siswa', 'academicManagePermissions'));
    }

    /** @param array{tahun_ajaran?: int|null, kelas?: string, q?: string, st?: string} $filters */
    private function filteredStudents(array $filters): Builder
    {
        $query = SiswaBiodata::aktif();
        $historyFilters = $filters;

        if ($historyFilters['tahun_ajaran'] !== null || $historyFilters['kelas'] !== '' || $historyFilters['st'] !== '') {
            $query->whereHas('riwayatKelas', function (Builder $historyQuery) use ($historyFilters): void {
                $this->applyHistoryFilters($historyQuery, $historyFilters);
            });
        }

        if ($filters['q'] !== '') {
            $search = $filters['q'];
            $query->where(function (Builder $searchQuery) use ($search): void {
                $searchQuery->where('nama_lengkap', 'like', "%{$search}%")
                    ->orWhere('nisn', 'like', "%{$search}%")
                    ->orWhere('nomor_induk', 'like', "%{$search}%");
            });
        }

        return $query->with(['riwayatKelas' => function (Relation $historyQuery) use ($historyFilters): void {
            $this->applyHistoryFilters($historyQuery, $historyFilters);
            $historyQuery->with(['kelas', 'tahunAjaran'])
                ->orderByDesc('tahun_ajaran_id')
                ->orderByDesc('id');
        }])->orderBy('nama_lengkap');
    }

    /** @param array{tahun_ajaran?: int|null, kelas?: string, q?: string, st?: string} $filters */
    private function applyHistoryFilters(Builder|Relation $historyQuery, array $filters): void
    {
        if ($filters['tahun_ajaran'] !== null) {
            $historyQuery->where('tahun_ajaran_id', $filters['tahun_ajaran']);
        }

        if ($filters['kelas'] !== '') {
            $historyQuery->whereHas('kelas', fn (Builder $classQuery) => $classQuery->where('nama', $filters['kelas']));
        }

        if ($filters['st'] !== '') {
            $historyQuery->where('status', $filters['st']);
        }
    }

    /** @return array{tahun_ajaran: int|null, kelas: string, q: string, st: string} */
    private function validatedFilters(Request $request): array
    {
        $filters = $request->validate([
            'tahun_ajaran' => ['nullable', 'integer', 'exists:sekolah_tahun_ajaran,id'],
            'kelas' => ['nullable', 'string', 'exists:sekolah_kelas,nama'],
            'q' => ['nullable', 'string', 'max:150'],
            'st' => ['nullable', 'in:Aktif'],
        ]);

        if ($request->hasSession() && $request->filled('tahun_ajaran')) {
            $request->session()->put('data_siswa_tahun_ajaran_id', (int) $filters['tahun_ajaran']);
        }

        $rememberedYearId = $request->hasSession()
            ? $request->session()->get('data_siswa_tahun_ajaran_id')
            : null;
        if ($rememberedYearId !== null
            && (! is_numeric($rememberedYearId) || ! SekolahTahunAjaran::whereKey((int) $rememberedYearId)->exists())) {
            $request->session()->forget('data_siswa_tahun_ajaran_id');
            $rememberedYearId = null;
        }

        $tahunAjaranId = $filters['tahun_ajaran'] ?? $rememberedYearId
            ?? SekolahTahunAjaran::where('sedang_berjalan', true)->value('id');
        $tahunAjaranId ??= SekolahTahunAjaran::latest('id')->value('id');

        return [
            'tahun_ajaran' => $tahunAjaranId === null ? null : (int) $tahunAjaranId,
            'kelas' => $filters['kelas'] ?? '',
            'q' => trim($filters['q'] ?? ''),
            'st' => $filters['st'] ?? '',
        ];
    }

    /** @param Collection<int, SiswaBiodata> $siswaList
     * @param  array{tahun_ajaran: int|null, kelas: string, q: string, st: string}  $filters
     */
    private function renderListPdf($siswaList, array $filters): Response
    {
        $tahunAjaranLabel = $filters['tahun_ajaran'] === null
            ? 'Semua Tahun Ajaran'
            : (SekolahTahunAjaran::find($filters['tahun_ajaran'])?->nama ?? '-');
        $kelasLabel = $filters['kelas'] !== '' ? $filters['kelas'] : 'Semua Kelas';

        return Pdf::loadView('admin.siswa-daftar-pdf', compact('siswaList', 'tahunAjaranLabel', 'kelasLabel'))
            ->setPaper('a4', 'landscape')
            ->stream('daftar-siswa-guru-'.now()->format('Ymd-His').'.pdf');
    }
}
