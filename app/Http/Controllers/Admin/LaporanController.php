<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SekolahKelas;
use App\Models\SekolahTahunAjaran;
use App\Models\SiswaKehadiran;
use App\Models\SiswaNilai;
use App\Models\SiswaRiwayatKelas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class LaporanController extends Controller
{
    public function index(Request $request): View
    {
        $taAktifObj = SekolahTahunAjaran::where('sedang_berjalan', 1)->first()
            ?? SekolahTahunAjaran::latest('id')->first();
        $defaultTa = $taAktifObj ? $taAktifObj->nama : '2024/2025';

        $ta = $request->input('ta', $defaultTa);
        $taList = SekolahTahunAjaran::pluck('nama')->toArray();
        if (empty($taList)) {
            $taList = ['2024/2025', '2023/2024', '2022/2023'];
        }

        $report = $this->buildReportRows($ta);

        $identitas = DB::table('sekolah_identitas')->where('id', 1)->first();

        return view('admin.laporan', [
            'ta' => $ta,
            'taList' => $taList,
            'rows' => $report['rows'],
            'totalSiswa' => $report['totalSiswa'],
            'identitas' => $identitas,
        ]);
    }

    public function cetak(Request $request)
    {
        $ta = $request->input('ta', '2024/2025');
        $rows = $this->buildReportRows($ta)['rows'];

        $identitas = DB::table('sekolah_identitas')->where('id', 1)->first();

        return view('admin.laporan-cetak', compact('ta', 'rows', 'identitas'));
    }

    /**
     * @return array{rows: array<int, array{kelas: string, siswa_count: int, rata_nilai: string, kehadiran: string}>, totalSiswa: int}
     */
    private function buildReportRows(string $ta): array
    {
        $taId = SekolahTahunAjaran::where('nama', $ta)->value('id') ?? 0;
        $kelasList = SekolahKelas::orderBy('nama')->get(['id', 'nama']);

        if ($kelasList->isEmpty()) {
            $kelasList = DB::table('siswa_riwayat_kelas as riwayat')
                ->join('sekolah_kelas as kelas', 'kelas.id', '=', 'riwayat.kelas_id')
                ->where('riwayat.tahun_ajaran_id', $taId)
                ->whereNull('riwayat.deleted_at')
                ->select('kelas.id', 'kelas.nama')
                ->distinct()
                ->orderBy('kelas.nama')
                ->get();
        }

        $rows = [];
        $totalSiswa = 0;

        foreach ($kelasList as $kelas) {
            $riwayatIds = SiswaRiwayatKelas::where('tahun_ajaran_id', $taId)
                ->where('kelas_id', $kelas->id)
                ->whereNull('deleted_at')
                ->pluck('id')
                ->toArray();
            $siswaCount = count($riwayatIds);
            $totalSiswa += $siswaCount;

            $avgNilai = '-';
            $kehadiran = '-';
            if ($siswaCount > 0) {
                $average = SiswaNilai::whereIn('riwayat_kelas_id', $riwayatIds)->avg('nilai_angka');
                if ($average !== null) {
                    $avgNilai = number_format($average, 1, '.', '');
                }

                $absences = SiswaKehadiran::whereIn('riwayat_kelas_id', $riwayatIds)
                    ->selectRaw('SUM(sakit + izin + alpa) as total')
                    ->value('total') ?? 0;
                $kehadiran = $absences.' hari';
            }

            $rows[] = [
                'kelas' => $kelas->nama,
                'siswa_count' => $siswaCount,
                'rata_nilai' => $avgNilai,
                'kehadiran' => $kehadiran,
            ];
        }

        return ['rows' => $rows, 'totalSiswa' => $totalSiswa];
    }
}
