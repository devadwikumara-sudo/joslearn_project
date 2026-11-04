<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CatatanAktivitas;
use App\Models\SekolahKelas;
use App\Models\SekolahTahunAjaran;
use App\Models\SiswaBiodata;
use App\Models\SiswaRiwayatKelas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class KenaikanKelasController extends Controller
{
    public function index(Request $request): View
    {
        $taAktifObj = SekolahTahunAjaran::where('sedang_berjalan', 1)->first()
            ?? SekolahTahunAjaran::latest('id')->first();
        $taAktif = $taAktifObj ? $taAktifObj->nama : '2024/2025';
        $taAktifId = $taAktifObj?->id ?? 0;

        // Hitung tahun ajaran berikutnya (contoh: 2024/2025 -> 2025/2026)
        $parts = explode('/', $taAktif);
        $taBerikutnya = (count($parts) === 2 && is_numeric($parts[0]))
            ? ((int) $parts[0] + 1).'/'.((int) $parts[1] + 1)
            : '2025/2026';

        $kelasList = SekolahKelas::pluck('nama')->toArray();
        if (empty($kelasList)) {
            $kelasList = ['X-1', 'X-2', 'XI-1', 'XI-2', 'XII-1', 'XII-2'];
        }

        $kelasAsal = $request->input('kelas', $kelasList[0] ?? 'X-1');

        // Ambil siswa aktif di kelas asal
        $kelasAsalId = SekolahKelas::where('nama', $kelasAsal)->value('id') ?? 0;
        $siswaList = SiswaBiodata::whereNull('deleted_at')
            ->whereHas('riwayatKelas', function ($q) use ($kelasAsalId, $taAktifId) {
                $q->where('kelas_id', $kelasAsalId)
                    ->where('tahun_ajaran_id', $taAktifId)
                    ->whereNull('deleted_at')
                    ->where('status', 'Aktif');
            })->with(['riwayatKelas' => function ($q) use ($taAktifId) {
                $q->where('tahun_ajaran_id', $taAktifId)
                    ->whereNull('deleted_at')
                    ->with('kelas');
            }])->get();

        // Hitung kelas tujuan berikutnya untuk tiap kelas
        $nextKelasMap = [];
        foreach ($kelasList as $k) {
            if (str_starts_with($k, 'X-')) {
                $nextKelasMap[$k] = str_replace('X-', 'XI-', $k);
            } elseif (str_starts_with($k, 'XI-')) {
                $nextKelasMap[$k] = str_replace('XI-', 'XII-', $k);
            } else {
                $nextKelasMap[$k] = 'LULUS';
            }
        }

        return view('admin.kenaikan-kelas', compact(
            'taAktif',
            'taBerikutnya',
            'kelasList',
            'kelasAsal',
            'siswaList',
            'nextKelasMap'
        ));
    }

    public function proses(Request $request)
    {
        $taAktif = $request->input('ta_aktif', '2024/2025');
        $taBerikutnya = $request->input('ta_berikutnya', '2025/2026');
        $kelasAsal = $request->input('kelas_asal');
        $keputusan = $request->input('keputusan', []); // array [siswa_id => 'Naik'/'Tinggal'/'Lulus'/'Keluar']
        $kelasTujuan = $request->input('kelas_tujuan', []); // array [siswa_id => 'XI-1']

        if (empty($keputusan)) {
            return back()->with('error', 'Tidak ada data siswa yang diproses.');
        }

        DB::beginTransaction();
        try {
            // Pastikan tahun ajaran baru ada di database
            $taBaru = SekolahTahunAjaran::firstOrCreate(['nama' => $taBerikutnya], ['sedang_berjalan' => 0]);
            $taAktifId = SekolahTahunAjaran::where('nama', $taAktif)->value('id') ?? 0;
            $kelasAsalId = SekolahKelas::where('nama', $kelasAsal)->value('id');

            $naikCount = 0;
            $lulusCount = 0;
            $keluarCount = 0;

            foreach ($keputusan as $siswaId => $aksi) {
                $siswa = SiswaBiodata::find($siswaId);
                if (! $siswa) {
                    continue;
                }

                $rkLama = SiswaRiwayatKelas::where('siswa_id', $siswaId)
                    ->where('tahun_ajaran_id', $taAktifId)
                    ->whereNull('deleted_at')
                    ->first();

                if ($aksi === 'Naik') {
                    $targetKelas = $kelasTujuan[$siswaId] ?? $kelasAsal;
                    $targetTingkat = str_starts_with($targetKelas, 'XII-')
                        ? 'XII'
                        : (str_starts_with($targetKelas, 'XI-') ? 'XI' : 'X');
                    $targetKelasObj = SekolahKelas::firstOrCreate(
                        ['nama' => $targetKelas],
                        ['tingkat' => $targetTingkat]
                    );
                    if ($rkLama) {
                        $rkLama->update([
                            'keputusan_kenaikan' => 'NAIK',
                            'kelas_tujuan_id' => $targetKelasObj->id,
                            'tanggal_keputusan_kenaikan' => now()->toDateString(),
                        ]);
                    }

                    // Buat riwayat kelas di tahun baru
                    SiswaRiwayatKelas::updateOrCreate(
                        ['siswa_id' => $siswaId, 'tahun_ajaran_id' => $taBaru->id],
                        ['kelas_id' => $targetKelasObj->id, 'status' => 'Aktif']
                    );
                    $naikCount++;

                } elseif ($aksi === 'Tinggal') {
                    if ($rkLama) {
                        $rkLama->update([
                            'keputusan_kenaikan' => 'TIDAK NAIK',
                            'tanggal_keputusan_kenaikan' => now()->toDateString(),
                        ]);
                    }
                    SiswaRiwayatKelas::updateOrCreate(
                        ['siswa_id' => $siswaId, 'tahun_ajaran_id' => $taBaru->id],
                        ['kelas_id' => $rkLama?->kelas_id ?? $kelasAsalId, 'status' => 'Aktif']
                    );
                    $naikCount++;

                } elseif ($aksi === 'Lulus') {
                    if ($rkLama) {
                        $rkLama->update([
                            'status' => 'Lulus',
                            'keputusan_kenaikan' => 'LULUS',
                            'tanggal_keputusan_kenaikan' => now()->toDateString(),
                        ]);
                    }
                    $siswa->update([
                        'tahun_lulus' => substr($taAktif, 5, 4) ?: now()->format('Y'),
                        'alasan_keluar' => 'Lulus',
                        'kelas_keluar' => $kelasAsal,
                    ]);
                    $lulusCount++;

                } elseif ($aksi === 'Keluar') {
                    if ($rkLama) {
                        $rkLama->update(['status' => 'Keluar']);
                    }
                    $siswa->update([
                        'alasan_keluar' => 'Keluar',
                        'kelas_keluar' => $kelasAsal,
                        'tanggal_keluar' => now()->toDateString(),
                    ]);
                    $keluarCount++;
                }
            }

            CatatanAktivitas::create([
                'pengguna_id' => auth()->id(),
                'peran' => auth()->user()->peran ?? 'admin',
                'aksi' => 'kenaikan_kelas',
                'keterangan' => "Kenaikan kelas {$kelasAsal} ({$taAktif} -> {$taBerikutnya}): {$naikCount} lanjut, {$lulusCount} lulus, {$keluarCount} keluar.",
                'objek_tipe' => 'kenaikan_kelas',
                'alamat_ip' => $request->ip(),
            ]);

            DB::commit();

            return redirect()->route('admin.kenaikan-kelas', ['kelas' => $kelasAsal])
                ->with('success', "Proses kenaikan kelas {$kelasAsal} berhasil: {$naikCount} lanjut ke {$taBerikutnya}, {$lulusCount} lulus, {$keluarCount} keluar.");
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Gagal memproses kenaikan kelas: '.$e->getMessage());
        }
    }
}
