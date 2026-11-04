<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SekolahKelas;
use App\Models\SekolahTahunAjaran;
use App\Models\SiswaBiodata;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DataSiswaController extends Controller
{
    public function index(Request $request): View
    {
        $taAktifObj = SekolahTahunAjaran::where('sedang_berjalan', 1)->first()
            ?? SekolahTahunAjaran::latest('id')->first();
        $taAktif = $taAktifObj?->nama ?? '2024/2025';
        $taAktifId = $taAktifObj?->id ?? 0;

        $kelasSelected = $request->input('kelas', '');
        $search = trim($request->input('q', ''));
        $statusFilter = trim($request->input('st', ''));

        $kelasList = SekolahKelas::orderBy('nama')->pluck('nama')->toArray();
        sort($kelasList);
        $kelasIds = SekolahKelas::pluck('id', 'nama')->toArray();

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

        $siswaList = null;
        if ($kelasSelected !== '' || $search !== '' || $statusFilter !== '' || $kelasList === []) {
            $query = SiswaBiodata::query();

            if ($kelasSelected !== '' && $kelasSelected !== '*') {
                $query->whereHas('riwayatKelas', function ($q) use ($kelasSelected, $taAktifId, $kelasIds) {
                    $q->where('tahun_ajaran_id', $taAktifId)
                        ->whereNull('deleted_at')
                        ->where('kelas_id', $kelasIds[$kelasSelected] ?? 0);
                });
            }

            if ($statusFilter !== '') {
                $query->whereHas('riwayatKelas', function ($q) use ($statusFilter, $taAktifId) {
                    $q->where('tahun_ajaran_id', $taAktifId)
                        ->whereNull('deleted_at')
                        ->where('status', $statusFilter);
                });
            }

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
        $taAktif = $taAktifObj?->nama ?? '2024/2025';

        $taAktifId = $taAktifObj?->id ?? 0;
        $kelasList = SekolahKelas::orderBy('nama')->pluck('nama')->toArray();

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

    public function foto(int $id): BinaryFileResponse
    {
        $siswa = SiswaBiodata::findOrFail($id);
        $path = $siswa->foto;
        abort_if(! $path, 404);

        $headers = [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ];

        if (str_starts_with($path, 'siswa/foto/')) {
            $filename = substr($path, strlen('siswa/foto/'));
            abort_if(
                $filename === ''
                    || basename($filename) !== $filename
                    || ! in_array(strtolower(pathinfo($filename, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp'], true),
                404
            );

            $disk = Storage::disk('local');
            abort_unless($disk->exists($path), 404);

            return response()->file($disk->path($path), $headers);
        }

        if (str_starts_with($path, 'uploads/foto/')) {
            $filename = substr($path, strlen('uploads/foto/'));
            abort_if(
                $filename === ''
                    || basename($filename) !== $filename
                    || ! in_array(strtolower(pathinfo($filename, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp'], true),
                404
            );

            $legacyPath = public_path('uploads/foto/'.$filename);
            abort_unless(is_file($legacyPath), 404);

            return response()->file($legacyPath, $headers);
        }

        abort(404);
    }

    public function show(int $id): View
    {
        $siswa = SiswaBiodata::with(['riwayatKelas' => function ($query) {
            $query->with(['kelas', 'tahunAjaran'])->orderBy('tahun_ajaran_id');
        }])->findOrFail($id);

        return view('admin.siswa-detail', compact('siswa'));
    }

    public function destroy(int $id)
    {
        $siswa = SiswaBiodata::findOrFail($id);
        $nama = $siswa->nama_lengkap ?? 'Siswa';
        $siswa->delete(); // Soft delete ke tempat sampah

        return back()->with('success', "Data {$nama} berhasil dipindahkan ke Tempat Sampah.");
    }
}
