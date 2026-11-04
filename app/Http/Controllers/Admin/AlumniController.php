<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use App\Models\SiswaBiodata;
use App\Models\SiswaRiwayatKelas;
use App\Models\SekolahTahunAjaran;

class AlumniController extends Controller
{
    public function index(Request $request): View
    {
        $taAktifObj = SekolahTahunAjaran::where('sedang_berjalan', 1)->first()
            ?? SekolahTahunAjaran::latest('id')->first();
        $taAktif = $taAktifObj ? $taAktifObj->nama : '2024/2025';

        $JN = ['Alumni (Lulus)', 'Pindah / Keluar', 'Semua peserta didik'];
        $aj = $request->input('aj', $JN[0]);
        $ay = $request->input('ay', '');
        $aq = trim($request->input('aq', ''));

        // Ambil daftar tahun lulus yang tersedia untuk filter dropdown
        $tahunList = SiswaBiodata::whereNotNull('tahun_lulus')
            ->where('tahun_lulus', '!=', '')
            ->distinct()
            ->pluck('tahun_lulus')
            ->toArray();

        $tahunKeluarList = SiswaBiodata::whereNotNull('tanggal_keluar')
            ->selectRaw('YEAR(tanggal_keluar) as thn')
            ->distinct()
            ->pluck('thn')
            ->filter()
            ->map(fn($y) => (string)$y)
            ->toArray();

        $ys = array_values(array_unique(array_filter(array_merge($tahunList, $tahunKeluarList))));
        rsort($ys);
        if (empty($ys)) {
            $ys = [(string)date('Y'), (string)(date('Y') - 1), (string)(date('Y') - 2)];
        }

        $query = SiswaBiodata::query();

        // Filter Jenis Siswa
        if ($aj === 'Alumni (Lulus)') {
            $query->where(function($q) {
                $q->whereNotNull('tahun_lulus')
                  ->where('tahun_lulus', '!=', '')
                  ->orWhere('alasan_keluar', 'Lulus')
                  ->orWhereHas('riwayatKelas', function($rq) {
                      $rq->where('status', 'Lulus');
                  });
            });
        } elseif ($aj === 'Pindah / Keluar') {
            $query->where(function($q) {
                $q->whereIn('alasan_keluar', ['Pindah', 'Keluar', 'Pindah keluar'])
                  ->orWhereNotNull('tanggal_keluar')
                  ->orWhereHas('riwayatKelas', function($rq) {
                      $rq->whereIn('status', ['Pindah keluar', 'Keluar']);
                  });
            });
        }

        // Filter Tahun
        if ($ay !== '') {
            $query->where(function($q) use ($ay) {
                $q->where('tahun_lulus', $ay)
                  ->orWhereRaw('YEAR(tanggal_keluar) = ?', [$ay]);
            });
        }

        // Filter Search
        if ($aq !== '') {
            $query->where(function($q) use ($aq) {
                $q->where('nama_lengkap', 'like', "%{$aq}%")
                  ->orWhere('nomor_induk', 'like', "%{$aq}%")
                  ->orWhere('nisn', 'like', "%{$aq}%");
            });
        }

        $totalFiltered = (clone $query)->count();

        $alumni = $query->with(['riwayatKelas' => function($rq) {
            $rq->latest('id');
        }])
        ->orderBy('nomor_induk', 'asc')
        ->paginate(25)
        ->withQueryString();

        return view('admin.alumni', compact(
            'JN',
            'aj',
            'ay',
            'aq',
            'ys',
            'totalFiltered',
            'alumni',
            'taAktif'
        ));
    }

    public function show(int $id)
    {
        return redirect()->route('admin.data-siswa.show', $id);
    }

    public function export(Request $request)
    {
        $aj = $request->input('aj', 'Alumni (Lulus)');
        $ay = $request->input('ay', '');
        $aq = trim($request->input('aq', ''));

        $query = SiswaBiodata::query();

        if ($aj === 'Alumni (Lulus)') {
            $query->where(function($q) {
                $q->whereNotNull('tahun_lulus')
                  ->where('tahun_lulus', '!=', '')
                  ->orWhere('alasan_keluar', 'Lulus')
                  ->orWhereHas('riwayatKelas', function($rq) {
                      $rq->where('status', 'Lulus');
                  });
            });
        } elseif ($aj === 'Pindah / Keluar') {
            $query->where(function($q) {
                $q->whereIn('alasan_keluar', ['Pindah', 'Keluar', 'Pindah keluar'])
                  ->orWhereNotNull('tanggal_keluar')
                  ->orWhereHas('riwayatKelas', function($rq) {
                      $rq->whereIn('status', ['Pindah keluar', 'Keluar']);
                  });
            });
        }

        if ($ay !== '') {
            $query->where(function($q) use ($ay) {
                $q->where('tahun_lulus', $ay)
                  ->orWhereRaw('YEAR(tanggal_keluar) = ?', [$ay]);
            });
        }

        if ($aq !== '') {
            $query->where(function($q) use ($aq) {
                $q->where('nama_lengkap', 'like', "%{$aq}%")
                  ->orWhere('nomor_induk', 'like', "%{$aq}%")
                  ->orWhere('nisn', 'like', "%{$aq}%");
            });
        }

        $rows = $query->orderBy('nomor_induk', 'asc')->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="kontrol-induk-alumni-' . date('Ymd-His') . '.csv"',
        ];

        $callback = function() use ($rows) {
            $output = fopen('php://output', 'w');
            // Write BOM for Excel UTF-8 support
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($output, [
                'No', 'Induk', 'Nama Peserta Didik', 'NISN', 'Jenis Registrasi',
                'Tanggal Masuk', 'Masuk Kelas', 'Sekolah Asal SMP', 'Keluar Karena',
                'Tanggal Keluar', 'Keluar Kelas', 'Sekolah Tujuan', 'Nomor Peserta PSAJ',
                'Nomor SKL', 'Nomor Ijazah Nasional', 'Tgl Pengesahan Ijazah',
                'Nomor Transkrip Nilai', 'Melanjutkan Studi / Bekerja', 'Tahun Lulus', 'Status'
            ]);

            $no = 1;
            foreach ($rows as $s) {
                $status = $s->tahun_lulus ? 'Lulus' : ($s->alasan_keluar ?: 'Alumni');
                fputcsv($output, [
                    $no++,
                    $s->nomor_induk ?: '-',
                    $s->nama_lengkap ?: '-',
                    $s->nisn ?: '-',
                    $s->jenis_registrasi ?: '-',
                    $s->tanggal_diterima ?: '-',
                    ($s->kelas_diterima ?: '') . ($s->rombel_diterima ? '-' . $s->rombel_diterima : ''),
                    $s->sekolah_asal ?: '-',
                    $s->alasan_keluar ?: '-',
                    $s->tanggal_keluar ?: '-',
                    $s->kelas_keluar ?: '-',
                    $s->sekolah_tujuan ?: '-',
                    $s->nomor_peserta_psaj ?: '-',
                    $s->nomor_skl ?: '-',
                    $s->nomor_ijazah_sma ?: '-',
                    $s->tanggal_ijazah_sma ?: '-',
                    $s->nomor_transkrip ?: '-',
                    $s->kegiatan_setelah_lulus ?: '-',
                    $s->tahun_lulus ?: ($s->tanggal_keluar ? substr($s->tanggal_keluar, 0, 4) : '-'),
                    $status
                ]);
            }
            fclose($output);
        };

        return response()->stream($callback, 200, $headers);
    }
}
