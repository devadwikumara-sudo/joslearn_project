<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use App\Models\SiswaBiodata;
use App\Models\SiswaRiwayatKelas;
use App\Models\SekolahKelas;
use App\Models\SekolahTahunAjaran;
use App\Models\GuruBiodata;
use App\Models\CatatanAktivitas;

class DashboardAdminController extends Controller
{
    public function index(): View
    {
        // 1. Tahun Ajaran Berjalan
        $taAktifObj = SekolahTahunAjaran::where('sedang_berjalan', 1)->first()
            ?? SekolahTahunAjaran::latest('id')->first();
        $taAktif = $taAktifObj ? $taAktifObj->nama : '2024/2025';

        // 2. Query Siswa Aktif pada Tahun Ajaran Ini
        $riwayatQuery = SiswaRiwayatKelas::where('tahun_ajaran', $taAktif);
        $siswaAktifIds = (clone $riwayatQuery)->where('status', 'Aktif')->pluck('siswa_id')->toArray();
        $totalSiswaAktif = count($siswaAktifIds);

        // 3. Guru & Alumni & Sampah
        $totalGuru = GuruBiodata::count();
        $totalAlumni = SiswaRiwayatKelas::where('status', 'Lulus')->distinct('siswa_id')->count('siswa_id');
        $totalSampah = SiswaBiodata::onlyTrashed()->count() + GuruBiodata::onlyTrashed()->count();

        // 4. Gender Siswa Aktif
        $siswaAktifList = SiswaBiodata::whereIn('id', $siswaAktifIds)->get();
        $totalLaki = $siswaAktifList->filter(function($s) {
            $jk = strtoupper(trim($s->jenis_kelamin ?? ''));
            return $jk === 'L' || str_starts_with($jk, 'LAKI');
        })->count();
        $totalPerempuan = $siswaAktifList->filter(function($s) {
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
        $kelasMap = (clone $riwayatQuery)->where('status', 'Aktif')->pluck('kelas', 'siswa_id')->toArray();

        foreach ($siswaAktifList as $s) {
            $missing = [];
            foreach ($requiredCols as $col => $lbl) {
                $val = trim($s->{$col} ?? '');
                if ($val === '' || $val === '-') {
                    $missing[] = $lbl;
                    $missingFieldsCount++;
                }
            }
            if (!empty($missing)) {
                $perluDilengkapi[] = [
                    'id' => $s->id,
                    'nama' => $s->nama_lengkap ?? 'Tanpa Nama',
                    'kelas' => $kelasMap[$s->id] ?? '-',
                    'missing' => $missing,
                ];
            }
        }

        // Urutkan yang paling banyak kurangnya
        usort($perluDilengkapi, fn($a, $b) => count($b['missing']) <=> count($a['missing']));
        $perluDilengkapi = array_slice($perluDilengkapi, 0, 6);

        $persenLengkap = $totalFields > 0
            ? max(0, min(100, round((1 - ($missingFieldsCount / $totalFields)) * 100)))
            : 100;

        // 7. Komposisi per Kelas
        $allKelas = SekolahKelas::pluck('nama')->toArray();
        if (empty($allKelas)) {
            $allKelas = (clone $riwayatQuery)->whereNotNull('kelas')->distinct()->pluck('kelas')->toArray();
        }
        sort($allKelas);

        $komposisiKelas = [];
        foreach ($allKelas as $k) {
            $idsInK = (clone $riwayatQuery)->where('status', 'Aktif')->where('kelas', $k)->pluck('siswa_id')->toArray();
            $countK = count($idsInK);
            if ($countK === 0 && empty(SekolahKelas::where('nama', $k)->first())) {
                continue;
            }
            $studentsInK = $siswaAktifList->whereIn('id', $idsInK);
            $lInK = $studentsInK->filter(fn($s) => strtoupper(trim($s->jenis_kelamin ?? '')) === 'L' || str_starts_with(strtoupper(trim($s->jenis_kelamin ?? '')), 'LAKI'))->count();
            $pInK = $studentsInK->filter(fn($s) => strtoupper(trim($s->jenis_kelamin ?? '')) === 'P' || str_starts_with(strtoupper(trim($s->jenis_kelamin ?? '')), 'PEREMPUAN'))->count();

            // Hitung kelengkapan di kelas ini
            $kMissing = 0;
            foreach ($studentsInK as $s) {
                foreach ($requiredCols as $col => $lbl) {
                    $val = trim($s->{$col} ?? '');
                    if ($val === '' || $val === '-') $kMissing++;
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
        $alumniPerTahun = SiswaRiwayatKelas::where('status', 'Lulus')
            ->selectRaw('COALESCE(tahun_lulus, SUBSTRING_INDEX(tahun_ajaran, "/", -1)) as thn, COUNT(DISTINCT siswa_id) as total')
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
