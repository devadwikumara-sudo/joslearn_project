<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use App\Models\SekolahKelas;
use App\Models\SekolahTahunAjaran;
use App\Models\SiswaRiwayatKelas;
use App\Models\SiswaNilai;
use App\Models\SiswaKehadiran;

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

        // Ambil daftar kelas dari master kelas atau riwayat
        $kelasList = SekolahKelas::pluck('nama')->toArray();
        if (empty($kelasList)) {
            $kelasList = SiswaRiwayatKelas::where('tahun_ajaran', $ta)
                ->whereNotNull('kelas')
                ->distinct()
                ->pluck('kelas')
                ->toArray();
        }
        if (empty($kelasList)) {
            $kelasList = ['X-1', 'X-2', 'XI-1', 'XI-2', 'XII-1', 'XII-2'];
        }
        sort($kelasList);

        // Rekap per kelas
        $rows = [];
        $totalSiswa = 0;
        $totalAbsenSemua = 0;
        $totalNilaiSemua = 0;
        $countNilaiSemua = 0;

        foreach ($kelasList as $k) {
            // ID riwayat kelas untuk kelas & TA ini
            $riwayatIds = SiswaRiwayatKelas::where('tahun_ajaran', $ta)
                ->where('kelas', $k)
                ->pluck('id')
                ->toArray();

            $siswaCount = count($riwayatIds);
            $totalSiswa += $siswaCount;

            $avgNilai = '-';
            if ($siswaCount > 0) {
                $avgQuery = SiswaNilai::whereIn('riwayat_kelas_id', $riwayatIds)->avg('nilai_angka');
                if ($avgQuery !== null) {
                    $avgNilai = number_format($avgQuery, 1, '.', '');
                    $totalNilaiSemua += $avgQuery * $siswaCount;
                    $countNilaiSemua += $siswaCount;
                }
            }

            $kehadiran = '-';
            if ($siswaCount > 0) {
                $totalKetidakhadiran = SiswaKehadiran::whereIn('riwayat_kelas_id', $riwayatIds)
                    ->selectRaw('SUM(sakit + izin + alpa) as total')
                    ->value('total') ?? 0;
                $kehadiran = $totalKetidakhadiran . ' hari';
                $totalAbsenSemua += $totalKetidakhadiran;
            }

            $rows[] = [
                'kelas'       => $k,
                'siswa_count' => $siswaCount,
                'rata_nilai'  => $avgNilai,
                'kehadiran'   => $kehadiran,
            ];
        }

        $identitas = DB::table('sekolah_identitas')->where('id', 1)->first();

        return view('admin.laporan', compact(
            'ta',
            'taList',
            'rows',
            'totalSiswa',
            'identitas'
        ));
    }

    public function cetak(Request $request)
    {
        $ta = $request->input('ta', '2024/2025');
        $kelasList = SekolahKelas::pluck('nama')->toArray();
        if (empty($kelasList)) {
            $kelasList = ['X-1', 'X-2', 'XI-1', 'XI-2', 'XII-1', 'XII-2'];
        }
        sort($kelasList);

        $rows = [];
        foreach ($kelasList as $k) {
            $riwayatIds = SiswaRiwayatKelas::where('tahun_ajaran', $ta)
                ->where('kelas', $k)
                ->pluck('id')
                ->toArray();

            $siswaCount = count($riwayatIds);
            $avgNilai = '-';
            if ($siswaCount > 0) {
                $avgQuery = SiswaNilai::whereIn('riwayat_kelas_id', $riwayatIds)->avg('nilai_angka');
                if ($avgQuery !== null) {
                    $avgNilai = number_format($avgQuery, 1, '.', '');
                }
            }

            $kehadiran = '-';
            if ($siswaCount > 0) {
                $total = SiswaKehadiran::whereIn('riwayat_kelas_id', $riwayatIds)
                    ->selectRaw('SUM(sakit + izin + alpa) as total')
                    ->value('total') ?? 0;
                $kehadiran = $total . ' hari';
            }

            $rows[] = [
                'kelas'       => $k,
                'siswa_count' => $siswaCount,
                'rata_nilai'  => $avgNilai,
                'kehadiran'   => $kehadiran,
            ];
        }

        $identitas = DB::table('sekolah_identitas')->where('id', 1)->first();

        return view('admin.laporan-cetak', compact('ta', 'rows', 'identitas'));
    }
}
