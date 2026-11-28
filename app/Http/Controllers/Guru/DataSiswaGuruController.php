<?php

namespace App\Http\Controllers\Guru;

use App\Exports\FormattedTableExport;
use App\Http\Controllers\Controller;
use App\Models\SekolahTahunAjaran;
use App\Models\SiswaBiodata;
use App\Services\AcademicRecordAccess;
use App\Services\AksesGuru;
use App\Support\StudentSearch;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DataSiswaGuruController extends Controller
{
    public function index(Request $request, AksesGuru $aksesGuru): View
    {
        $lingkup = $this->lingkupDataSiswa($request);
        $kelasAkses = $aksesGuru->kelasUntukDataSiswa($request->user(), $lingkup);
        abort_if($kelasAkses->isEmpty(), 403);

        $kelasList = $kelasAkses->pluck('nama')->all();
        sort($kelasList, SORT_NATURAL);
        $kelasIds = $kelasAkses->modelKeys();
        $filters = $this->validatedFilters($request, $kelasList, $lingkup);
        $siswa = $this->filteredStudents($filters, $kelasIds)
            ->paginate(25)
            ->withQueryString();
        $adaKelasWali = $aksesGuru->kelasWali($request->user())->isNotEmpty();
        $adaKelasMapel = $aksesGuru->kelasDiajar($request->user())->isNotEmpty();

        return view('guru.data-siswa', compact(
            'siswa',
            'kelasList',
            'filters',
            'lingkup',
            'adaKelasWali',
            'adaKelasMapel'
        ));
    }

    public function wali(Request $request, AksesGuru $aksesGuru): View
    {
        $request->query->set('tab', 'wali');

        return $this->index($request, $aksesGuru);
    }

    public function export(Request $request, AksesGuru $aksesGuru): BinaryFileResponse
    {
        [$filters, $kelasIds] = $this->scopedFilters($request, $aksesGuru);
        $siswa = $this->filteredStudents($filters, $kelasIds)->get();

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

    public function pdfAll(Request $request, AksesGuru $aksesGuru): Response
    {
        [$filters, $kelasIds] = $this->scopedFilters($request, $aksesGuru);
        $siswaList = $this->filteredStudents($filters, $kelasIds)->get();

        return $this->renderListPdf($siswaList, $filters);
    }

    public function pdfSelected(Request $request, AksesGuru $aksesGuru): Response
    {
        [$filters, $kelasIds] = $this->scopedFilters($request, $aksesGuru);
        $selection = $request->validate([
            'siswa_ids' => ['required', 'array', 'min:1'],
            'siswa_ids.*' => ['required', 'integer', 'distinct', 'exists:siswa_biodata,id'],
        ]);
        $siswaList = $this->filteredStudents($filters, $kelasIds)
            ->whereIn('id', $selection['siswa_ids'])
            ->get();

        abort_if($siswaList->isEmpty(), 404);

        return $this->renderListPdf($siswaList, $filters);
    }

    public function show(int $id, Request $request, AcademicRecordAccess $access, AksesGuru $aksesGuru): View
    {
        $lingkup = $this->lingkupDataSiswa($request);
        abort_unless($aksesGuru->bolehMelihatSiswa($request->user(), $id, $lingkup), 403);

        $siswa = SiswaBiodata::aktif()->with(['riwayatKelas' => function ($query): void {
            $query->with(['kelas', 'tahunAjaran', 'nilai.mapel', 'kehadiran', 'ekskul'])->orderByDesc('tahun_ajaran_id');
        }])->findOrFail($id);
        $academicManagePermissions = [];
        foreach ($siswa->riwayatKelas as $history) {
            $academicManagePermissions[$history->id] = $access->permissionsFor($request->user(), $history);
        }

        return view('guru.data-siswa-detail', compact('siswa', 'academicManagePermissions', 'lingkup'));
    }

    /**
     * @param  array{tahun_ajaran?: int|null, kelas?: string, q?: string, st?: string, tab?: string}  $filters
     * @param  array<int, int>  $kelasIds
     */
    private function filteredStudents(array $filters, array $kelasIds): Builder
    {
        $tahunBerjalanId = SekolahTahunAjaran::query()->where('sedang_berjalan', true)->value('id');
        abort_if($tahunBerjalanId === null, 403);

        $query = SiswaBiodata::aktif((int) $tahunBerjalanId)
            ->whereHas('riwayatKelas', function (Builder $historyQuery) use ($tahunBerjalanId, $kelasIds): void {
                $historyQuery->where('tahun_ajaran_id', $tahunBerjalanId)
                    ->where('status', 'Aktif')
                    ->whereIn('kelas_id', $kelasIds);
            });
        $historyFilters = $filters;

        if ($historyFilters['tahun_ajaran'] !== null || $historyFilters['kelas'] !== '' || $historyFilters['st'] !== '') {
            $query->whereHas('riwayatKelas', function (Builder $historyQuery) use ($historyFilters): void {
                $this->applyHistoryFilters($historyQuery, $historyFilters);
            });
        }

        if ($filters['q'] !== '') {
            StudentSearch::apply($query, $filters['q']);
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

    /**
     * @param  array<int, string>  $kelasList
     * @return array{tahun_ajaran: int|null, kelas: string, q: string, st: string, tab: string}
     */
    private function validatedFilters(Request $request, array $kelasList, string $lingkup): array
    {
        $filters = $request->validate([
            'tahun_ajaran' => ['nullable', 'integer', 'exists:sekolah_tahun_ajaran,id'],
            'kelas' => ['nullable', 'string', Rule::in($kelasList)],
            'q' => ['nullable', 'string', 'max:150'],
            'st' => ['nullable', 'in:Aktif,Lulus,Pindah keluar'],
            'tab' => ['nullable', 'in:mapel,wali'],
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
            'tab' => $lingkup,
        ];
    }

    /**
     * @return array{0: array{tahun_ajaran: int|null, kelas: string, q: string, st: string, tab: string}, 1: array<int, int>}
     */
    private function scopedFilters(Request $request, AksesGuru $aksesGuru): array
    {
        $lingkup = $this->lingkupDataSiswa($request);
        $kelasAkses = $aksesGuru->kelasUntukDataSiswa($request->user(), $lingkup);
        abort_if($kelasAkses->isEmpty(), 403);

        $kelasList = $kelasAkses->pluck('nama')->all();
        sort($kelasList, SORT_NATURAL);

        return [
            $this->validatedFilters($request, $kelasList, $lingkup),
            $kelasAkses->modelKeys(),
        ];
    }

    private function lingkupDataSiswa(Request $request): string
    {
        $lingkup = $request->input('tab', 'mapel');
        abort_unless(in_array($lingkup, ['mapel', 'wali'], true), 422);

        return $lingkup;
    }

    /** @param Collection<int, SiswaBiodata> $siswaList
     * @param  array{tahun_ajaran: int|null, kelas: string, q: string, st: string, tab: string}  $filters
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
