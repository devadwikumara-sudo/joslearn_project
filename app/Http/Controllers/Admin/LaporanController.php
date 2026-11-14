<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SekolahKelas;
use App\Models\SekolahTahunAjaran;
use App\Models\SiswaBiodata;
use App\Models\SiswaEkskul;
use App\Models\SiswaKehadiran;
use App\Models\SiswaNilai;
use App\Models\SiswaRiwayatKelas;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LaporanController extends Controller
{
    public function index(Request $request): View|StreamedResponse
    {
        $tab = $request->query('tab', 'laporan');
        abort_unless(is_string($tab) && in_array($tab, ['laporan', 'kualitas'], true), 404);

        if ($tab === 'kualitas') {
            $filter = $request->validate([
                'jenis' => ['sometimes', 'string', 'in:semua,kesalahan,peringatan'],
            ])['jenis'] ?? 'semua';
            $qualityData = $this->buildQualityData($filter);

            if ($request->has('export')) {
                return $this->exportCsv($qualityData['masalah']);
            }

            return view('admin.laporan', [
                'tab' => $tab,
                ...$qualityData,
                'filter' => $filter,
            ]);
        }

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
            'tab' => $tab,
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
                ->where('status', 'Aktif')
                ->whereNull('deleted_at')
                ->whereIn('siswa_id', SiswaBiodata::aktif($taId)->select('id'))
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

    /**
     * @return array{
     *     masalah: array<int, array{jenis: string, kolom: string, siswa: string, nisn: string, masalah: string, id: int|null, siswa_ids?: array<int, int>}>,
     *     jumlahKesalahan: int,
     *     jumlahPeringatan: int,
     *     totalSiswa: int,
     *     totalBaris: int
     * }
     */
    private function buildQualityData(string $filter): array
    {
        $students = SiswaBiodata::aktif()->get();
        $totalSiswa = $students->count();
        $masalah = [];

        foreach ($students as $student) {
            if (! $student->nisn || ! preg_match('/^\d{10}$/', $student->nisn)) {
                $masalah[] = [
                    'jenis' => 'Kesalahan',
                    'kolom' => 'NISN',
                    'siswa' => $student->nama_lengkap,
                    'nisn' => $student->nisn ?: '-',
                    'masalah' => 'NISN kosong atau bukan 10 digit angka',
                    'id' => $student->id,
                ];
            }

            if (! $student->nama_lengkap) {
                $masalah[] = [
                    'jenis' => 'Kesalahan',
                    'kolom' => 'Nama Lengkap',
                    'siswa' => 'ID#'.$student->id,
                    'nisn' => $student->nisn ?: '-',
                    'masalah' => 'Nama lengkap kosong',
                    'id' => $student->id,
                ];
            }

            if (! $student->foto_ada || $student->foto_ada == 0) {
                $masalah[] = [
                    'jenis' => 'Peringatan',
                    'kolom' => 'Foto',
                    'siswa' => $student->nama_lengkap ?: 'ID#'.$student->id,
                    'nisn' => $student->nisn ?: '-',
                    'masalah' => 'Foto siswa belum diunggah',
                    'id' => $student->id,
                ];
            }

            if ($student->tahun_lulus && (! $student->no_skl || ! $student->no_ijazah_sma || ! $student->no_transkrip)) {
                $missingDocuments = array_filter([
                    ! $student->no_skl ? 'No. SKL' : null,
                    ! $student->no_ijazah_sma ? 'No. Ijazah SMA' : null,
                    ! $student->no_transkrip ? 'No. Transkrip' : null,
                ]);
                $masalah[] = [
                    'jenis' => 'Peringatan',
                    'kolom' => 'Dokumen Lulus',
                    'siswa' => $student->nama_lengkap,
                    'nisn' => $student->nisn ?: '-',
                    'masalah' => 'Siswa lulus tapi belum ada: '.implode(', ', $missingDocuments),
                    'id' => $student->id,
                ];
            }
        }

        $duplicateNisn = $students
            ->filter(fn (SiswaBiodata $student): bool => $student->nisn !== null)
            ->groupBy('nisn')
            ->filter(fn (Collection $group): bool => $group->count() > 1);

        foreach ($duplicateNisn as $nisn => $group) {
            $masalah[] = [
                'jenis' => 'Kesalahan',
                'kolom' => 'NISN Duplikat',
                'siswa' => "NISN {$nisn}",
                'nisn' => (string) $nisn,
                'masalah' => "NISN duplikat — ditemukan pada {$group->count()} siswa",
                'id' => null,
                'siswa_ids' => $group->pluck('id')->map(static fn ($id): int => (int) $id)->all(),
            ];
        }

        $duplicateStudentNumbers = $students
            ->filter(fn (SiswaBiodata $student): bool => $student->nomor_induk !== null)
            ->groupBy('nomor_induk')
            ->filter(fn (Collection $group): bool => $group->count() > 1);

        foreach ($duplicateStudentNumbers as $studentNumber => $group) {
            $masalah[] = [
                'jenis' => 'Kesalahan',
                'kolom' => 'Nomor Induk Duplikat',
                'siswa' => "NI {$studentNumber}",
                'nisn' => '-',
                'masalah' => "Nomor Induk duplikat — ditemukan pada {$group->count()} siswa",
                'id' => null,
                'siswa_ids' => $group->pluck('id')->map(static fn ($id): int => (int) $id)->all(),
            ];
        }

        if ($filter === 'kesalahan') {
            $masalah = array_filter($masalah, fn (array $issue): bool => $issue['jenis'] === 'Kesalahan');
        } elseif ($filter === 'peringatan') {
            $masalah = array_filter($masalah, fn (array $issue): bool => $issue['jenis'] === 'Peringatan');
        }

        $masalah = array_values($masalah);

        return [
            'masalah' => $masalah,
            'jumlahKesalahan' => collect($masalah)->where('jenis', 'Kesalahan')->count(),
            'jumlahPeringatan' => collect($masalah)->where('jenis', 'Peringatan')->count(),
            'totalSiswa' => $totalSiswa,
            'totalBaris' => SiswaNilai::count() + SiswaKehadiran::count() + SiswaEkskul::count(),
        ];
    }

    /**
     * @param  array<int, array{jenis: string, kolom: string, siswa: string, nisn: string, masalah: string, id: int|null, siswa_ids?: array<int, int>}>  $masalah
     */
    private function exportCsv(array $masalah): StreamedResponse
    {
        $filename = 'laporan-kualitas-data-'.date('Ymd').'.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($masalah): void {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($file, ['Jenis', 'Kolom', 'Nama Siswa', 'NISN', 'Masalah']);
            foreach ($masalah as $issue) {
                fputcsv($file, [$issue['jenis'], $issue['kolom'], $issue['siswa'], $issue['nisn'], $issue['masalah']]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
