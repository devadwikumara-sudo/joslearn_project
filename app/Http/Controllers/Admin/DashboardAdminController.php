<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\CatatanAktivitas;
use App\Models\Guru\GuruBiodata;
use App\Models\SekolahKelas;
use App\Models\SekolahTahunAjaran;
use App\Models\SiswaBiodata;
use App\Models\SiswaRiwayatKelas;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardAdminController extends Controller
{
    public function index(): View
    {
        // 1. Tahun Ajaran Berjalan
        $taAktifObj = SekolahTahunAjaran::where('sedang_berjalan', 1)->first()
            ?? SekolahTahunAjaran::latest('id')->first();
        $taAktif = $taAktifObj ? $taAktifObj->nama : '2024/2025';

        // 2. Query Siswa Aktif pada Tahun Ajaran Ini
        $riwayatQuery = SiswaRiwayatKelas::whereNull('deleted_at')
            ->where('tahun_ajaran_id', $taAktifObj?->id ?? 0);
        $siswaAktifIds = (clone $riwayatQuery)->where('status', 'Aktif')->pluck('siswa_id')->toArray();
        $totalSiswaAktif = count($siswaAktifIds);

        // 3. Guru & Alumni & Sampah
        $totalGuru = GuruBiodata::count();
        $totalAlumni = SiswaBiodata::graduated()->count();
        $totalSampah = DB::table('siswa_biodata')->whereNotNull('deleted_at')->count()
            + DB::table('guru_biodata')->whereNotNull('deleted_at')->count();

        // 4. Gender Siswa Aktif
        $siswaAktifList = SiswaBiodata::whereIn('id', $siswaAktifIds)->get();
        $totalLaki = $siswaAktifList->filter(function ($s) {
            $jk = strtoupper(trim($s->jenis_kelamin ?? ''));

            return $jk === 'L' || str_starts_with($jk, 'LAKI');
        })->count();
        $totalPerempuan = $siswaAktifList->filter(function ($s) {
            $jk = strtoupper(trim($s->jenis_kelamin ?? ''));

            return $jk === 'P' || str_starts_with($jk, 'PEREMPUAN');
        })->count();

        // 5. Mutasi (Pindah / Keluar) pada TA ini
        $totalMutasi = (clone $riwayatQuery)->whereIn('status', ['Pindah keluar', 'Keluar', 'Pindah'])->count();

        // 6. Kelengkapan Data Siswa Aktif
        $requiredCols = [
            'nisn' => 'NISN',
            'nomor_induk' => 'NIS',
            'nik' => 'NIK',
            'tempat_lahir' => 'Tempat Lahir',
            'tanggal_lahir' => 'Tanggal Lahir',
            'jenis_kelamin' => 'Jenis Kelamin',
            'agama' => 'Agama',
            'nama_ayah' => 'Nama Ayah',
            'nama_ibu' => 'Nama Ibu',
            'alamat_jalan' => 'Alamat',
            'nomor_telepon' => 'No. HP',
        ];

        $perluDilengkapi = [];
        $totalFields = $totalSiswaAktif * count($requiredCols);
        $missingFieldsCount = 0;

        // Dapatkan kelas terkini per siswa
        $kelasMap = (clone $riwayatQuery)->where('status', 'Aktif')
            ->with('kelas:id,nama')
            ->get(['siswa_id', 'kelas_id'])
            ->mapWithKeys(fn (SiswaRiwayatKelas $riwayat): array => [
                $riwayat->siswa_id => $riwayat->kelas?->nama ?? '-',
            ])
            ->toArray();

        foreach ($siswaAktifList as $s) {
            $missing = [];
            foreach ($requiredCols as $col => $lbl) {
                $val = trim($s->{$col} ?? '');
                if ($val === '' || $val === '-') {
                    $missing[] = $lbl;
                    $missingFieldsCount++;
                }
            }
            if (! empty($missing)) {
                $perluDilengkapi[] = [
                    'id' => $s->id,
                    'nama' => $s->nama_lengkap ?? 'Tanpa Nama',
                    'kelas' => $kelasMap[$s->id] ?? '-',
                    'missing' => $missing,
                ];
            }
        }

        // Urutkan yang paling banyak kurangnya
        usort($perluDilengkapi, fn ($a, $b) => count($b['missing']) <=> count($a['missing']));
        $perluDilengkapi = array_slice($perluDilengkapi, 0, 6);

        $persenLengkap = $totalFields > 0
            ? max(0, min(100, round((1 - ($missingFieldsCount / $totalFields)) * 100)))
            : 100;

        // 7. Komposisi per Kelas
        $allKelas = SekolahKelas::orderBy('nama')->pluck('nama')->toArray();
        if (empty($allKelas)) {
            $allKelas = (clone $riwayatQuery)->where('status', 'Aktif')
                ->with('kelas:id,nama')
                ->get(['kelas_id'])
                ->pluck('kelas.nama')
                ->filter()
                ->unique()
                ->values()
                ->toArray();
        }
        sort($allKelas);
        $kelasIds = SekolahKelas::pluck('id', 'nama')->toArray();

        $komposisiKelas = [];
        foreach ($allKelas as $k) {
            $kelasId = $kelasIds[$k] ?? null;
            if ($kelasId === null) {
                continue;
            }

            $idsInK = (clone $riwayatQuery)->where('status', 'Aktif')->where('kelas_id', $kelasId)->pluck('siswa_id')->toArray();
            $countK = count($idsInK);
            $studentsInK = $siswaAktifList->whereIn('id', $idsInK);
            $lInK = $studentsInK->filter(fn ($s) => strtoupper(trim($s->jenis_kelamin ?? '')) === 'L' || str_starts_with(strtoupper(trim($s->jenis_kelamin ?? '')), 'LAKI'))->count();
            $pInK = $studentsInK->filter(fn ($s) => strtoupper(trim($s->jenis_kelamin ?? '')) === 'P' || str_starts_with(strtoupper(trim($s->jenis_kelamin ?? '')), 'PEREMPUAN'))->count();

            // Hitung kelengkapan di kelas ini
            $kMissing = 0;
            foreach ($studentsInK as $s) {
                foreach ($requiredCols as $col => $lbl) {
                    $val = trim($s->{$col} ?? '');
                    if ($val === '' || $val === '-') {
                        $kMissing++;
                    }
                }
            }
            $kTotal = $countK * count($requiredCols);
            $kPersen = $kTotal > 0 ? round((1 - ($kMissing / $kTotal)) * 100) : 100;

            $komposisiKelas[] = [
                'kelas' => $k,
                'l' => $lInK,
                'p' => $pInK,
                'total' => $countK,
                'persen' => $kPersen,
            ];
        }

        // 8. Siswa per Angkatan (Tingkat X, XI, XII)
        $angkatanX = 0;
        $angkatanXI = 0;
        $angkatanXII = 0;

        foreach ($kelasMap as $sId => $kName) {
            $upper = strtoupper($kName);
            if (str_starts_with($upper, 'XII') || str_starts_with($upper, '12')) {
                $angkatanXII++;
            } elseif (str_starts_with($upper, 'XI') || str_starts_with($upper, '11')) {
                $angkatanXI++;
            } elseif (str_starts_with($upper, 'X') || str_starts_with($upper, '10')) {
                $angkatanX++;
            }
        }

        // 9. Alumni per Tahun Lulus
        $alumniPerTahun = DB::table('siswa_riwayat_kelas as riwayat')
            ->join('sekolah_tahun_ajaran as tahun', 'tahun.id', '=', 'riwayat.tahun_ajaran_id')
            ->whereNull('riwayat.deleted_at')
            ->where('riwayat.status', 'Lulus')
            ->selectRaw('RIGHT(tahun.nama, 4) as thn, COUNT(DISTINCT riwayat.siswa_id) as total')
            ->groupBy('thn')
            ->orderByDesc('thn')
            ->pluck('total', 'thn')
            ->toArray();

        // 10. Aktivitas Terbaru (Log)
        $aktivitasTerbaru = CatatanAktivitas::latest('id')->take(5)->get();

        return view('admin.dashboard', compact(
            'taAktif',
            'totalSiswaAktif',
            'totalGuru',
            'totalAlumni',
            'totalSampah',
            'totalLaki',
            'totalPerempuan',
            'totalMutasi',
            'persenLengkap',
            'komposisiKelas',
            'perluDilengkapi',
            'angkatanX',
            'angkatanXI',
            'angkatanXII',
            'alumniPerTahun',
            'aktivitasTerbaru'
        ));
    }
}
