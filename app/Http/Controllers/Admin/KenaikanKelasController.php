<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\CatatanAktivitas;
use App\Models\Admin\CatatanKenaikanKelas;
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

        // Catatan kenaikan kelas terakhir yang belum dibatalkan
        $riwayatKenaikanTerakhir = CatatanKenaikanKelas::with(['kelasAsal', 'tahunAjaranAsal', 'tahunAjaranTujuan'])
            ->whereNull('dibatalkan_at')
            ->latest('id')
            ->first();

        return view('admin.kenaikan-kelas', compact(
            'taAktif',
            'taBerikutnya',
            'kelasList',
            'kelasAsal',
            'siswaList',
            'nextKelasMap',
            'riwayatKenaikanTerakhir'
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

            $riwayatBaruIds = [];
            $dataSebelumnya = [];

            foreach ($keputusan as $siswaId => $aksi) {
                $siswa = SiswaBiodata::find($siswaId);
                if (! $siswa) {
                    continue;
                }

                $rkLama = SiswaRiwayatKelas::where('siswa_id', $siswaId)
                    ->where('tahun_ajaran_id', $taAktifId)
                    ->whereNull('deleted_at')
                    ->first();

                if ($rkLama) {
                    $dataSebelumnya[] = [
                        'type'                       => 'riwayat_lama',
                        'id'                         => $rkLama->id,
                        'kelas_id'                   => $rkLama->kelas_id,
                        'tahun_ajaran_id'            => $rkLama->tahun_ajaran_id,
                        'status'                     => $rkLama->status,
                        'keputusan_kenaikan'         => $rkLama->keputusan_kenaikan,
                        'kelas_tujuan_id'            => $rkLama->kelas_tujuan_id,
                        'tanggal_keputusan_kenaikan' => $rkLama->tanggal_keputusan_kenaikan,
                        'siswa_id'                   => $siswaId,
                        'siswa_tahun_lulus'          => $siswa->tahun_lulus,
                        'siswa_alasan_keluar'        => $siswa->alasan_keluar,
                        'siswa_kelas_keluar'         => $siswa->kelas_keluar,
                        'siswa_tanggal_keluar'       => $siswa->tanggal_keluar,
                    ];
                }

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
                    $rkBaru = SiswaRiwayatKelas::updateOrCreate(
                        ['siswa_id' => $siswaId, 'tahun_ajaran_id' => $taBaru->id],
                        ['kelas_id' => $targetKelasObj->id, 'status' => 'Aktif']
                    );
                    $riwayatBaruIds[] = $rkBaru->id;
                    $naikCount++;

                } elseif ($aksi === 'Tinggal') {
                    if ($rkLama) {
                        $rkLama->update([
                            'keputusan_kenaikan' => 'TIDAK NAIK',
                            'tanggal_keputusan_kenaikan' => now()->toDateString(),
                        ]);
                    }
                    $rkBaru = SiswaRiwayatKelas::updateOrCreate(
                        ['siswa_id' => $siswaId, 'tahun_ajaran_id' => $taBaru->id],
                        ['kelas_id' => $rkLama?->kelas_id ?? $kelasAsalId, 'status' => 'Aktif']
                    );
                    $riwayatBaruIds[] = $rkBaru->id;
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

            // Catat di tabel catatan_kenaikan_kelas untuk fitur Undo
            CatatanKenaikanKelas::create([
                'pengguna_id'            => auth()->id(),
                'kelas_asal_id'          => $kelasAsalId,
                'tahun_ajaran_asal_id'   => $taAktifId,
                'tahun_ajaran_tujuan_id' => $taBaru->id,
                'jumlah_siswa'           => count($keputusan),
                'riwayat_baru'           => json_encode($riwayatBaruIds),
                'data_sebelumnya'        => json_encode($dataSebelumnya),
                'created_at'             => now(),
            ]);

            CatatanAktivitas::create([
                'pengguna_id' => auth()->id(),
                'peran'       => auth()->user()->peran ?? 'admin',
                'aksi'        => 'kenaikan_kelas',
                'keterangan'  => "Kenaikan kelas {$kelasAsal} ({$taAktif} -> {$taBerikutnya}): {$naikCount} lanjut, {$lulusCount} lulus, {$keluarCount} keluar.",
                'objek_tipe'  => 'kenaikan_kelas',
                'alamat_ip'   => $request->ip(),
            ]);

            DB::commit();

            return redirect()->route('admin.kenaikan-kelas', ['kelas' => $kelasAsal])
                ->with('success', "Proses kenaikan kelas {$kelasAsal} berhasil: {$naikCount} lanjut ke {$taBerikutnya}, {$lulusCount} lulus, {$keluarCount} keluar.");
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Gagal memproses kenaikan kelas: '.$e->getMessage());
        }
    }

    public function batalkan(Request $request, $id)
    {
        $catatan = CatatanKenaikanKelas::findOrFail($id);
        if ($catatan->dibatalkan_at) {
            return back()->with('error', 'Kenaikan kelas ini sudah pernah dibatalkan.');
        }

        DB::beginTransaction();
        try {
            // 1. Kembalikan data lama
            $dataSebelumnya = json_decode($catatan->data_sebelumnya ?? '[]', true);
            foreach ($dataSebelumnya as $b) {
                if (isset($b['id'])) {
                    SiswaRiwayatKelas::where('id', $b['id'])->update([
                        'kelas_id'                   => $b['kelas_id'],
                        'tahun_ajaran_id'            => $b['tahun_ajaran_id'],
                        'status'                     => $b['status'],
                        'keputusan_kenaikan'         => $b['keputusan_kenaikan'],
                        'kelas_tujuan_id'            => $b['kelas_tujuan_id'],
                        'tanggal_keputusan_kenaikan' => $b['tanggal_keputusan_kenaikan'],
                    ]);
                }
                if (isset($b['siswa_id'])) {
                    SiswaBiodata::where('id', $b['siswa_id'])->update([
                        'tahun_lulus'    => $b['siswa_tahun_lulus'],
                        'alasan_keluar'  => $b['siswa_alasan_keluar'],
                        'kelas_keluar'   => $b['siswa_kelas_keluar'],
                        'tanggal_keluar' => $b['siswa_tanggal_keluar'],
                    ]);
                }
            }

            // 2. Hapus baris riwayat kelas baru yang dibuat oleh proses ini
            $riwayatBaru = json_decode($catatan->riwayat_baru ?? '[]', true);
            if (!empty($riwayatBaru)) {
                SiswaRiwayatKelas::whereIn('id', $riwayatBaru)->delete();
            }

            // 3. Tandai catatan dibatalkan
            $catatan->update(['dibatalkan_at' => now()]);

            CatatanAktivitas::create([
                'pengguna_id' => auth()->id(),
                'peran'       => auth()->user()->peran ?? 'admin',
                'aksi'        => 'batalkan_kenaikan',
                'keterangan'  => "Membatalkan kenaikan kelas batch #{$id}.",
                'objek_tipe'  => 'kenaikan_kelas',
                'alamat_ip'   => $request->ip(),
            ]);

            DB::commit();

            return back()->with('success', 'Kenaikan kelas berhasil dibatalkan. Riwayat siswa dikembalikan ke kondisi sebelumnya.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal membatalkan kenaikan: ' . $e->getMessage());
        }
    }
}
