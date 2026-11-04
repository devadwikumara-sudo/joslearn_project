<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiswaBiodata;
use App\Models\SiswaNilai;
use App\Models\SiswaKehadiran;
use App\Models\SiswaEkskul;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CekKualitasDataController extends Controller
{
    public function index(Request $request): View
    {
        $filter = $request->input('jenis', 'semua'); // kesalahan | peringatan | semua

        // Ambil semua siswa aktif (tidak dihapus)
        $semua = SiswaBiodata::whereNull('deleted_at')->get();
        $totalSiswa = $semua->count();

        $masalah = [];

        foreach ($semua as $s) {
            // 1. NISN kosong atau bukan 10 digit → Kesalahan
            if (!$s->nisn || !preg_match('/^\d{10}$/', $s->nisn)) {
                $masalah[] = [
                    'jenis'   => 'Kesalahan',
                    'kolom'   => 'NISN',
                    'siswa'   => $s->nama_lengkap,
                    'nisn'    => $s->nisn ?: '-',
                    'masalah' => 'NISN kosong atau bukan 10 digit angka',
                    'id'      => $s->id,
                ];
            }

            // 2. Nama kosong → Kesalahan
            if (!$s->nama_lengkap) {
                $masalah[] = [
                    'jenis'   => 'Kesalahan',
                    'kolom'   => 'Nama Lengkap',
                    'siswa'   => 'ID#' . $s->id,
                    'nisn'    => $s->nisn ?: '-',
                    'masalah' => 'Nama lengkap kosong',
                    'id'      => $s->id,
                ];
            }

            // 3. Foto belum ada → Peringatan
            if (!$s->foto_ada || $s->foto_ada == 0) {
                $masalah[] = [
                    'jenis'   => 'Peringatan',
                    'kolom'   => 'Foto',
                    'siswa'   => $s->nama_lengkap ?: 'ID#' . $s->id,
                    'nisn'    => $s->nisn ?: '-',
                    'masalah' => 'Foto siswa belum diunggah',
                    'id'      => $s->id,
                ];
            }

            // 4. Siswa Lulus tanpa SKL/Ijazah → Peringatan
            if ($s->tahun_lulus && (!$s->no_skl || !$s->no_ijazah_sma || !$s->no_transkrip)) {
                $hilang = array_filter([
                    !$s->no_skl       ? 'No. SKL'       : null,
                    !$s->no_ijazah_sma? 'No. Ijazah SMA': null,
                    !$s->no_transkrip ? 'No. Transkrip'  : null,
                ]);
                $masalah[] = [
                    'jenis'   => 'Peringatan',
                    'kolom'   => 'Dokumen Lulus',
                    'siswa'   => $s->nama_lengkap,
                    'nisn'    => $s->nisn ?: '-',
                    'masalah' => 'Siswa lulus tapi belum ada: ' . implode(', ', $hilang),
                    'id'      => $s->id,
                ];
            }
        }

        // 5. NISN Duplikat → Kesalahan
        $dupNISN = DB::table('siswa_biodata')
            ->select('nisn', DB::raw('COUNT(*) as cnt'))
            ->whereNotNull('nisn')
            ->whereNull('deleted_at')
            ->groupBy('nisn')
            ->having('cnt', '>', 1)
            ->get();

        foreach ($dupNISN as $dup) {
            $masalah[] = [
                'jenis'   => 'Kesalahan',
                'kolom'   => 'NISN Duplikat',
                'siswa'   => "NISN {$dup->nisn}",
                'nisn'    => $dup->nisn,
                'masalah' => "NISN duplikat — ditemukan pada {$dup->cnt} siswa",
                'id'      => null,
            ];
        }

        // 6. Nomor Induk Duplikat → Kesalahan
        $dupNI = DB::table('siswa_biodata')
            ->select('nomor_induk', DB::raw('COUNT(*) as cnt'))
            ->whereNotNull('nomor_induk')
            ->whereNull('deleted_at')
            ->groupBy('nomor_induk')
            ->having('cnt', '>', 1)
            ->get();

        foreach ($dupNI as $dup) {
            $masalah[] = [
                'jenis'   => 'Kesalahan',
                'kolom'   => 'Nomor Induk Duplikat',
                'siswa'   => "NI {$dup->nomor_induk}",
                'nisn'    => '-',
                'masalah' => "Nomor Induk duplikat — ditemukan pada {$dup->cnt} siswa",
                'id'      => null,
            ];
        }

        // Filter
        if ($filter === 'kesalahan') {
            $masalah = array_filter($masalah, fn($m) => $m['jenis'] === 'Kesalahan');
        } elseif ($filter === 'peringatan') {
            $masalah = array_filter($masalah, fn($m) => $m['jenis'] === 'Peringatan');
        }
        $masalah = array_values($masalah);

        // Statistik
        $allMasalah = collect($masalah); // setelah filter
        $jumlahKesalahan  = collect($masalah)->where('jenis', 'Kesalahan')->count();
        $jumlahPeringatan = collect($masalah)->where('jenis', 'Peringatan')->count();

        $totalBaris = SiswaNilai::count() + SiswaKehadiran::count() + SiswaEkskul::count();

        // Export CSV
        if ($request->has('export')) {
            return $this->exportCsv($masalah);
        }

        return view('admin.cek-kualitas-data', compact(
            'masalah',
            'jumlahKesalahan',
            'jumlahPeringatan',
            'totalSiswa',
            'totalBaris',
            'filter'
        ));
    }

    private function exportCsv(array $masalah)
    {
        $filename = 'laporan-kualitas-data-' . date('Ymd') . '.csv';
        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($masalah) {
            $f = fopen('php://output', 'w');
            fprintf($f, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8
            fputcsv($f, ['Jenis', 'Kolom', 'Nama Siswa', 'NISN', 'Masalah']);
            foreach ($masalah as $m) {
                fputcsv($f, [$m['jenis'], $m['kolom'], $m['siswa'], $m['nisn'], $m['masalah']]);
            }
            fclose($f);
        };

        return response()->stream($callback, 200, $headers);
    }
}

