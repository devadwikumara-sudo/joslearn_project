<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\ImportUnggahan;
use App\Models\Auth\Pengguna;
use App\Models\Guru\GuruBiodata;
use App\Models\SekolahKelas;
use App\Models\SekolahTahunAjaran;
use App\Services\AksesGuru;
use App\Services\ImportBatchHistory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ImportDataController extends Controller
{
    private const TYPES = [
        'siswa' => 'Siswa',
        'guru' => 'Guru',
        'nilai' => 'Nilai',
        'kehadiran' => 'Kehadiran',
        'ekskul' => 'Ekskul',
    ];

    public function index(
        Request $request,
        AksesGuru $aksesGuru,
        ImportBatchHistory $importBatchHistory,
        ImportBatchController $importBatchController
    ): View|RedirectResponse {
        $user = $request->user();
        abort_unless($user instanceof Pengguna, 403);

        $allowedTypes = $this->allowedTypes($user, $aksesGuru);
        abort_unless($allowedTypes !== [], 403);

        $isAdmin = $user->peran === 'admin';
        $indexRoute = $isAdmin ? 'admin.import' : 'guru.import-data';
        $type = (string) $request->query('tab', $request->query('jenis', $allowedTypes[0]));
        $allTab = $type === 'semua';
        if ($allTab) {
            abort_unless($isAdmin, 403);
        } else {
            abort_unless(isset(self::TYPES[$type]), 404);
            abort_unless(in_array($type, $allowedTypes, true), 403);
        }
        $tab = $type;
        $tabView = $allTab
            ? 'all'
            : (in_array($type, ['nilai', 'kehadiran', 'ekskul'], true) ? 'academic' : $type);

        if ($request->query->has('jenis') && ! $request->query->has('tab')) {
            $query = $request->query->all();
            unset($query['jenis']);
            $query['tab'] = $type;

            return redirect()->route($indexRoute, $query);
        }

        $batchDetail = null;
        if ($request->query->has('batch')) {
            abort_unless($isAdmin && in_array($type, ['nilai', 'kehadiran', 'ekskul'], true), 404);
            $batchId = filter_var($request->query('batch'), FILTER_VALIDATE_INT);
            abort_unless(is_int($batchId), 404);
            $batch = ImportUnggahan::query()->findOrFail($batchId);
            abort_unless($batch->jenis === $type, 404);
            $batchDetail = $importBatchController->managedBatchDetails($batch, $request->boolean('edit'));
        }

        $tahunAjaranList = SekolahTahunAjaran::query()->orderBy('nama')->get(['id', 'nama', 'sedang_berjalan']);
        $tahunAjaranAktif = $tahunAjaranList->firstWhere('sedang_berjalan', true)
            ?? $tahunAjaranList->first();

        if ($request->query->has('tahun_ajaran_id')) {
            $requestedYearId = filter_var($request->query('tahun_ajaran_id'), FILTER_VALIDATE_INT);
            abort_unless(is_int($requestedYearId), 404);
            $tahunAjaranTerpilih = $tahunAjaranList->firstWhere('id', $requestedYearId);
            abort_unless($tahunAjaranTerpilih !== null, 404);
        } else {
            $tahunAjaranTerpilih = $tahunAjaranAktif;
        }

        $tahunAjaranTerpilihId = $tahunAjaranTerpilih?->id;
        $taAktif = $tahunAjaranTerpilih?->nama ?? '';

        if ($isAdmin) {
            $kelasList = SekolahKelas::query()->orderBy('nama')->pluck('nama')
                ->sortBy(static fn (string $nama): string => $nama, SORT_NATURAL)
                ->values();
            $kelasOptions = SekolahKelas::query()->orderBy('nama')->get(['id', 'nama', 'tingkat']);
        } else {
            $kelas = $type === 'nilai'
                ? $aksesGuru->kelasDiajar($user)
                : $aksesGuru->kelasWali($user);
            $kelasList = $kelas->pluck('nama')
                ->sortBy(static fn (string $nama): string => $nama, SORT_NATURAL)
                ->values();
            $kelasOptions = $kelas;
        }

        $templateRoute = $isAdmin ? 'admin.import.template' : 'guru.import-data.template';
        $typeLabel = $allTab ? 'Semua Data' : self::TYPES[$type];
        $typeJs = $allTab ? 'semua' : ($type === 'kehadiran' ? 'hadir' : $type);
        $availableTypes = array_intersect_key(self::TYPES, array_flip($allowedTypes));
        if ($isAdmin) {
            $availableTypes = ['semua' => 'Semua Data'] + $availableTypes;
        }
        $batches = collect();
        $riwayatImport = collect();
        $totalGuru = null;

        if ($isAdmin && in_array($type, ['nilai', 'kehadiran', 'ekskul'], true)) {
            $batches = $importBatchHistory->forYear($type, $tahunAjaranTerpilihId);
        } elseif ($isAdmin && $type === 'siswa' && $tahunAjaranTerpilihId !== null) {
            $riwayatImport = DB::table('siswa_riwayat_kelas as riwayat')
                ->join('sekolah_kelas as kelas', 'kelas.id', '=', 'riwayat.kelas_id')
                ->where('riwayat.tahun_ajaran_id', $tahunAjaranTerpilihId)
                ->whereNull('riwayat.deleted_at')
                ->select('kelas.nama as kelas')
                ->selectRaw('COUNT(*) as baris')
                ->groupBy('kelas.id', 'kelas.nama')
                ->orderBy('kelas.nama')
                ->get();
        } elseif ($isAdmin && $type === 'guru') {
            $totalGuru = GuruBiodata::query()->count();
        }

        return view('admin.import.data', compact(
            'allowedTypes',
            'availableTypes',
            'batchDetail',
            'batches',
            'indexRoute',
            'isAdmin',
            'kelasList',
            'kelasOptions',
            'riwayatImport',
            'taAktif',
            'tahunAjaranList',
            'tahunAjaranTerpilihId',
            'tab',
            'tabView',
            'templateRoute',
            'totalGuru',
            'type',
            'typeJs',
            'typeLabel'
        ));
    }

    public function downloadTemplate(
        Request $request,
        string $jenis,
        AksesGuru $aksesGuru,
        ImportTemplateController $templateController
    ): BinaryFileResponse {
        $user = $request->user();
        abort_unless($user instanceof Pengguna, 403);
        abort_unless(in_array($jenis, $this->allowedTypes($user, $aksesGuru), true), 403);

        return $templateController->download($jenis);
    }

    /**
     * @return array<int, string>
     */
    private function allowedTypes(Pengguna $user, AksesGuru $aksesGuru): array
    {
        if ($user->peran === 'admin') {
            return array_keys(self::TYPES);
        }

        if ($user->peran !== 'guru') {
            return [];
        }

        $types = [];
        if ($aksesGuru->adalahGuruMapel($user)) {
            $types[] = 'nilai';
        }
        if ($aksesGuru->adalahWali($user)) {
            $types[] = 'kehadiran';
            $types[] = 'ekskul';
        }

        return $types;
    }
}
