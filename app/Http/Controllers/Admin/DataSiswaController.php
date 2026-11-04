<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use App\Models\SiswaBiodata;
use App\Models\SiswaRiwayatKelas;
use App\Models\SekolahKelas;
use App\Models\SekolahTahunAjaran;

class DataSiswaController extends Controller
{
    public function index(Request $request): View
    {
        // 1. Tahun Ajaran Berjalan
        $taAktifObj = SekolahTahunAjaran::where('sedang_berjalan', 1)->first()
            ?? SekolahTahunAjaran::latest('id')->first();
        $taAktif = $taAktifObj ? $taAktifObj->nama : '2024/2025';

        $kelasSelected = $request->input('kelas', '');
        $search = trim($request->input('q', ''));
        $statusFilter = trim($request->input('st', ''));

        // 2. Daftar Semua Kelas dari Master Kelas / Riwayat
        $kelasList = SekolahKelas::pluck('nama')->toArray();
        if (empty($kelasList)) {
            $kelasList = SiswaRiwayatKelas::where('tahun_ajaran', $taAktif)
                ->whereNotNull('kelas')
                ->distinct()
                ->pluck('kelas')
                ->toArray();
        }
        if (empty($kelasList)) {
            $kelasList = ['X-1', 'X-2', 'XI-1', 'XI-2', 'XII-1', 'XII-2'];
        }
        sort($kelasList);

        // 3. Hitung jumlah siswa per kelas untuk tampilan grid
        $kelasCounts = [];
        foreach ($kelasList as $k) {
            $kelasCounts[$k] = SiswaRiwayatKelas::where('tahun_ajaran', $taAktif)
                ->where('kelas', $k)
                ->where('status', 'Aktif')
                ->count();
        }

        // 4. Jika ada kelas yang dipilih atau ada pencarian, ambil data tabel siswa
        $siswaList = null;
        if ($kelasSelected !== '' || $search !== '' || $statusFilter !== '') {
            $query = SiswaBiodata::query();

            // Filter Kelas
            if ($kelasSelected !== '*' && $kelasSelected !== '') {
                $query->whereHas('riwayatKelas', function($q) use ($kelasSelected, $taAktif) {
                    $q->where('kelas', $kelasSelected)
                      ->where('tahun_ajaran', $taAktif);
                });
            } else {
                // Semua kelas
                $query->whereHas('riwayatKelas', function($q) use ($taAktif) {
                    $q->where('tahun_ajaran', $taAktif);
                });
            }

            // Filter Status
            if ($statusFilter !== '') {
                $query->whereHas('riwayatKelas', function($q) use ($statusFilter, $taAktif) {
                    $q->where('status', $statusFilter)
                      ->where('tahun_ajaran', $taAktif);
                });
            }

            // Pencarian Nama / NISN / NIS
            if ($search !== '') {
                $query->where(function($q) use ($search) {
                    $q->where('nama_lengkap', 'like', "%{$search}%")
                      ->orWhere('nisn', 'like', "%{$search}%")
                      ->orWhere('nomor_induk', 'like', "%{$search}%");
                });
            }

            $siswaList = $query->with(['riwayatKelas' => function($q) use ($taAktif) {
                $q->where('tahun_ajaran', $taAktif);
            }])
            ->latest('id')
            ->paginate(25)
            ->withQueryString();
        }

        return view('admin.data-siswa', compact(
            'taAktif',
            'kelasList',
            'kelasCounts',
            'kelasSelected',
            'search',
            'statusFilter',
            'siswaList'
        ));
    }

    public function importView(): View
    {
        $taAktifObj = SekolahTahunAjaran::where('sedang_berjalan', 1)->first()
            ?? SekolahTahunAjaran::latest('id')->first();
        $taAktif = $taAktifObj ? $taAktifObj->nama : '2024/2025';

        $kelasList = SekolahKelas::pluck('nama')->toArray();
        if (empty($kelasList)) {
            $kelasList = ['X-1', 'X-2', 'XI-1', 'XI-2', 'XII-1', 'XII-2'];
        }

        $taList = SekolahTahunAjaran::pluck('nama')->toArray();
        if (empty($taList)) {
            $taList = ['2024/2025', '2023/2024', '2022/2023'];
        }

        // Ambil riwayat batch import siswa pada tahun ini
        $riwayatImport = SiswaRiwayatKelas::where('tahun_ajaran', $taAktif)
            ->selectRaw('kelas, count(*) as baris')
            ->groupBy('kelas')
            ->get();

        return view('admin.import.siswa', compact(
            'taAktif',
            'kelasList',
            'taList',
            'riwayatImport'
        ));
    }

    public function show(int $id): View
    {
        $siswa = SiswaBiodata::with(['riwayatKelas', 'nilai', 'kehadiran', 'ekskul'])->findOrFail($id);
        return view('admin.form-pendataan', [
            'edit' => $siswa,
        ]);
    }

    public function destroy(int $id)
    {
        $siswa = SiswaBiodata::findOrFail($id);
        $nama = $siswa->nama_lengkap ?? 'Siswa';
        $siswa->delete(); // Soft delete ke tempat sampah

        return back()->with('success', "Data {$nama} berhasil dipindahkan ke Tempat Sampah.");
    }
}
