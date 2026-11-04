<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SekolahKelas;
use App\Models\SekolahTahunAjaran;
use App\Models\SiswaBiodata;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DataSiswaController extends Controller
{
    public function index(Request $request): View
    {
        // 1. Tahun Ajaran Berjalan
        $taAktifObj = SekolahTahunAjaran::where('sedang_berjalan', 1)->first()
            ?? SekolahTahunAjaran::latest('id')->first();
        $taAktif = $taAktifObj ? $taAktifObj->nama : '2024/2025';
        $taAktifId = $taAktifObj?->id ?? 0;

        $kelasSelected = $request->input('kelas', '');
        $search = trim($request->input('q', ''));
        $statusFilter = trim($request->input('st', ''));

        // 2. Daftar Semua Kelas dari Master Kelas / Riwayat
        $kelasList = SekolahKelas::orderBy('nama')->pluck('nama')->toArray();
        if (empty($kelasList)) {
            $kelasList = DB::table('siswa_riwayat_kelas as riwayat')
                ->join('sekolah_kelas as kelas', 'kelas.id', '=', 'riwayat.kelas_id')
                ->where('riwayat.tahun_ajaran_id', $taAktifId)
                ->whereNull('riwayat.deleted_at')
                ->distinct()
                ->orderBy('kelas.nama')
                ->pluck('kelas.nama')
                ->toArray();
        }
        if (empty($kelasList)) {
            $kelasList = ['X-1', 'X-2', 'XI-1', 'XI-2', 'XII-1', 'XII-2'];
        }
        sort($kelasList);
        $kelasIds = SekolahKelas::pluck('id', 'nama')->toArray();

        // 3. Hitung jumlah siswa per kelas untuk tampilan grid
        $kelasCounts = array_fill_keys($kelasList, 0);
        $countsByClass = DB::table('siswa_riwayat_kelas as riwayat')
            ->join('sekolah_kelas as kelas', 'kelas.id', '=', 'riwayat.kelas_id')
            ->where('riwayat.tahun_ajaran_id', $taAktifId)
            ->whereNull('riwayat.deleted_at')
            ->where('riwayat.status', 'Aktif')
            ->select('kelas.nama')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('kelas.id', 'kelas.nama')
            ->pluck('total', 'kelas.nama');
        foreach ($countsByClass as $className => $count) {
            $kelasCounts[$className] = $count;
        }

        // 4. Jika ada kelas yang dipilih atau ada pencarian, ambil data tabel siswa
        $siswaList = null;
        if ($kelasSelected !== '' || $search !== '' || $statusFilter !== '') {
            $query = SiswaBiodata::whereNull('deleted_at');

            $query->whereHas('riwayatKelas', function ($q) use ($kelasSelected, $statusFilter, $taAktifId, $kelasIds) {
                $q->where('tahun_ajaran_id', $taAktifId)
                    ->whereNull('deleted_at');

                if ($kelasSelected !== '' && $kelasSelected !== '*') {
                    $q->where('kelas_id', $kelasIds[$kelasSelected] ?? 0);
                }

                if ($statusFilter !== '') {
                    $q->where('status', $statusFilter);
                }
            });

            // Pencarian Nama / NISN / NIS
            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('nama_lengkap', 'like', "%{$search}%")
                        ->orWhere('nisn', 'like', "%{$search}%")
                        ->orWhere('nomor_induk', 'like', "%{$search}%");
                });
            }

            $siswaList = $query->with(['riwayatKelas' => function ($q) use ($taAktifId) {
                $q->where('tahun_ajaran_id', $taAktifId)
                    ->whereNull('deleted_at')
                    ->with('kelas');
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

        $taAktifId = $taAktifObj?->id ?? 0;
        $kelasList = SekolahKelas::orderBy('nama')->pluck('nama')->toArray();
        if (empty($kelasList)) {
            $kelasList = ['X-1', 'X-2', 'XI-1', 'XI-2', 'XII-1', 'XII-2'];
        }

        $taList = SekolahTahunAjaran::pluck('nama')->toArray();
        if (empty($taList)) {
            $taList = ['2024/2025', '2023/2024', '2022/2023'];
        }

        // Ambil riwayat batch import siswa pada tahun ini
        $riwayatImport = DB::table('siswa_riwayat_kelas as riwayat')
            ->join('sekolah_kelas as kelas', 'kelas.id', '=', 'riwayat.kelas_id')
            ->where('riwayat.tahun_ajaran_id', $taAktifId)
            ->whereNull('riwayat.deleted_at')
            ->select('kelas.nama as kelas')
            ->selectRaw('COUNT(*) as baris')
            ->groupBy('kelas.id', 'kelas.nama')
            ->orderBy('kelas.nama')
            ->get();

        return view('admin.import.siswa', compact(
            'taAktif',
            'kelasList',
            'taList',
            'riwayatImport'
        ));
    }

    public function export(): StreamedResponse
    {
        $taAktifId = SekolahTahunAjaran::where('sedang_berjalan', 1)->value('id')
            ?? SekolahTahunAjaran::latest('id')->value('id')
            ?? 0;
        $siswaList = SiswaBiodata::with(['riwayatKelas' => function ($query) use ($taAktifId) {
            $query->where('tahun_ajaran_id', $taAktifId)
                ->with('kelas');
        }])->orderBy('nama_lengkap')->get();

        return response()->streamDownload(function () use ($siswaList): void {
            $output = fopen('php://output', 'w');
            if ($output === false) {
                throw new RuntimeException('Tidak dapat membuka output ekspor data siswa.');
            }

            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($output, ['NISN', 'NIS', 'Nama', 'Kelas', 'Jenis Kelamin', 'Tempat Lahir', 'Tanggal Lahir', 'Agama', 'Status']);

            foreach ($siswaList as $siswa) {
                $riwayat = $siswa->riwayatKelas->first();
                fputcsv($output, [
                    $siswa->nisn,
                    $siswa->nomor_induk,
                    $siswa->nama_lengkap,
                    $riwayat?->kelas?->nama ?? '-',
                    $siswa->jenis_kelamin,
                    $siswa->tempat_lahir,
                    $siswa->tanggal_lahir,
                    $siswa->agama,
                    $riwayat?->status ?? '-',
                ]);
            }

            fclose($output);
        }, 'data-siswa-'.now()->format('Ymd-His').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function pdf(int $id): Response
    {
        $taAktifId = SekolahTahunAjaran::where('sedang_berjalan', 1)->value('id')
            ?? SekolahTahunAjaran::latest('id')->value('id')
            ?? 0;
        $siswa = SiswaBiodata::with(['riwayatKelas' => function ($query) use ($taAktifId) {
            $query->where('tahun_ajaran_id', $taAktifId)
                ->with(['kelas', 'tahunAjaran']);
        }])->findOrFail($id);

        return Pdf::loadView('admin.siswa-pdf', compact('siswa'))
            ->setPaper('a4')
            ->stream('biodata-siswa-'.$siswa->nisn.'.pdf');
    }

    public function show(int $id): View
    {
        $taAktifObj = SekolahTahunAjaran::where('sedang_berjalan', 1)->first()
            ?? SekolahTahunAjaran::latest('id')->first();
        $siswa = SiswaBiodata::with([
            'riwayatKelas.kelas',
            'riwayatKelas.tahunAjaran',
            'nilai.mapel',
            'kehadiran',
            'ekskul',
        ])->findOrFail($id);

        $kelasList = SekolahKelas::orderBy('nama')->pluck('nama')->toArray();
        if (empty($kelasList)) {
            $kelasList = ['X-1', 'X-2', 'XI-1', 'XI-2', 'XII-1', 'XII-2'];
        }

        return view('admin.form-pendataan', [
            'edit' => $siswa,
            'taAktif' => $taAktifObj?->nama ?? '2024/2025',
            'kelasList' => $kelasList,
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
