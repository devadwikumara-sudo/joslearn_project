<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Auth\Pengguna;
use App\Models\Guru\GuruBiodata;
use App\Models\SekolahKelas;
use App\Models\SekolahTahunAjaran;
use App\Services\AksesGuru;
use App\Services\ImportBatchHistory;
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

    public function index(Request $request, AksesGuru $aksesGuru, ImportBatchHistory $importBatchHistory): View
    {
        $user = $request->user();
        abort_unless($user instanceof Pengguna, 403);

        $allowedTypes = $this->allowedTypes($user, $aksesGuru);
        abort_unless($allowedTypes !== [], 403);

        $type = (string) $request->query('jenis', $allowedTypes[0]);
        abort_unless(isset(self::TYPES[$type]), 404);
        abort_unless(in_array($type, $allowedTypes, true), 403);

        $isAdmin = $user->peran === 'admin';
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
        } else {
            $kelas = $type === 'nilai'
                ? $aksesGuru->kelasDiajar($user)
                : $aksesGuru->kelasWali($user);
            $kelasList = $kelas->pluck('nama')
                ->sortBy(static fn (string $nama): string => $nama, SORT_NATURAL)
                ->values();
        }

        $indexRoute = $isAdmin ? 'admin.import-data' : 'guru.import-data';
        $templateRoute = $isAdmin ? 'admin.import-data.template' : 'guru.import-data.template';
        $typeLabel = self::TYPES[$type];
        $typeJs = $type === 'kehadiran' ? 'hadir' : $type;
        $availableTypes = array_intersect_key(self::TYPES, array_flip($allowedTypes));
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
            'batches',
            'indexRoute',
            'isAdmin',
            'kelasList',
            'riwayatImport',
            'taAktif',
            'tahunAjaranList',
            'tahunAjaranTerpilihId',
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
