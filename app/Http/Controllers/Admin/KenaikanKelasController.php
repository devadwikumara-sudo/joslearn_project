<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\CatatanAktivitas;
use App\Models\Admin\CatatanKenaikanKelas;
use App\Models\Guru\GuruWaliKelas;
use App\Models\SekolahKelas;
use App\Models\SekolahTahunAjaran;
use App\Models\SiswaBiodata;
use App\Models\SiswaRiwayatKelas;
use App\Services\AksesGuru;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class KenaikanKelasController extends Controller
{
    public function index(Request $request, AksesGuru $aksesGuru): View
    {
        $pengguna = $request->user();
        $isAdmin = $pengguna->peran === 'admin';
        $routePrefix = $isAdmin ? 'admin' : 'guru';
        $taAktifObj = SekolahTahunAjaran::where('sedang_berjalan', 1)->first();
        if ($isAdmin) {
            $taAktifObj ??= SekolahTahunAjaran::latest('id')->first();
        } else {
            abort_unless($taAktifObj, 403, 'Tidak ada tahun ajaran berjalan.');
        }

        $taAktif = $taAktifObj ? $taAktifObj->nama : '2024/2025';
        $taAktifId = $taAktifObj?->id ?? 0;
        $waliKelasList = $aksesGuru->kelasWali($pengguna);

        // Hitung tahun ajaran berikutnya (contoh: 2024/2025 -> 2025/2026)
        $parts = explode('/', $taAktif);
        $taBerikutnya = (count($parts) === 2 && is_numeric($parts[0]))
            ? ((int) $parts[0] + 1).'/'.((int) $parts[1] + 1)
            : '2025/2026';

        $kelasTujuanList = SekolahKelas::pluck('nama')->toArray();
        if ($isAdmin && empty($kelasTujuanList)) {
            $kelasTujuanList = ['X-1', 'X-2', 'XI-1', 'XI-2', 'XII-1', 'XII-2'];
        }

        $kelasAsalList = $isAdmin
            ? $kelasTujuanList
            : $waliKelasList->pluck('nama')->all();
        if ($isAdmin && empty($kelasAsalList)) {
            $kelasAsalList = ['X-1', 'X-2', 'XI-1', 'XI-2', 'XII-1', 'XII-2'];
        }

        $kelasAsal = $request->input('kelas', $kelasAsalList[0] ?? '');
        $kelasObj = SekolahKelas::where('nama', $kelasAsal)->first();
        abort_unless($isAdmin || ($kelasObj && $aksesGuru->bolehKenaikanKelas($pengguna, (int) $kelasObj->id)), 403, 'Anda bukan wali kelas untuk kelas yang dipilih.');

        $kelasList = $kelasTujuanList;

        // Ambil siswa aktif di kelas asal
        $kelasAsalId = $kelasObj?->id ?? 0;
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
        $riwayatQuery = CatatanKenaikanKelas::with(['kelasAsal', 'tahunAjaranAsal', 'tahunAjaranTujuan'])
            ->whereNull('dibatalkan_at');
        if (! $isAdmin) {
            $riwayatQuery
                ->whereIn('kelas_asal_id', $waliKelasList->modelKeys())
                ->where('tahun_ajaran_asal_id', $taAktifId);
        }
        $riwayatKenaikanTerakhir = $riwayatQuery->latest('id')->first();

        return view('admin.kenaikan-kelas', compact(
            'taAktif',
            'taBerikutnya',
            'kelasList',
            'kelasAsalList',
            'kelasAsal',
            'siswaList',
            'nextKelasMap',
            'riwayatKenaikanTerakhir',
            'routePrefix'
        ));
    }

    public function proses(Request $request, AksesGuru $aksesGuru)
    {
        $validated = $request->validate([
            'kelas_asal' => 'required|string|max:100',
            'keputusan' => 'required|array|min:1',
            'keputusan.*' => 'required|in:Naik,Tinggal,Lulus,Keluar',
            'kelas_tujuan' => 'sometimes|array',
            'kelas_tujuan.*' => 'nullable|string|max:100',
        ]);

        $pengguna = $request->user();
        $isAdmin = $pengguna->peran === 'admin';
        $taAktifObj = $isAdmin
            ? SekolahTahunAjaran::where('nama', $request->input('ta_aktif'))->first()
                ?? SekolahTahunAjaran::where('sedang_berjalan', 1)->first()
                ?? SekolahTahunAjaran::latest('id')->first()
            : SekolahTahunAjaran::where('sedang_berjalan', 1)->first();
        abort_unless($taAktifObj, 422, 'Tahun ajaran berjalan tidak ditemukan.');

        $taAktif = $taAktifObj->nama;
        if ($isAdmin) {
            $taBerikutnya = $request->input('ta_berikutnya', $this->tahunAjaranBerikutnya($taAktif));
        } else {
            $taBerikutnya = $this->tahunAjaranBerikutnya($taAktif);
        }

        $kelasAsalObj = SekolahKelas::where('nama', $validated['kelas_asal'])->first();
        if (! $kelasAsalObj && $isAdmin) {
            $kelasAsalObj = SekolahKelas::firstOrCreate(
                ['nama' => $validated['kelas_asal']],
                ['tingkat' => $this->tingkatKelas($validated['kelas_asal'])]
            );
        }
        abort_unless($kelasAsalObj, 422, 'Kelas asal tidak ditemukan.');
        abort_unless(
            $isAdmin || $aksesGuru->bolehKenaikanKelas($pengguna, (int) $kelasAsalObj->id),
            403,
            'Anda bukan wali kelas untuk kelas yang dipilih.'
        );

        $taAktifId = (int) $taAktifObj->id;
        $kelasAsalId = (int) $kelasAsalObj->id;
        $taTujuan = $this->tahunAjaranBerikutnya($taAktif);
        if ($isAdmin) {
            $taTujuan = $taBerikutnya;
        }

        $riwayatAsalBySiswa = [];
        foreach (array_keys($validated['keputusan']) as $siswaId) {
            $rkLama = SiswaRiwayatKelas::with('siswa')
                ->where('siswa_id', $siswaId)
                ->where('tahun_ajaran_id', $taAktifId)
                ->where('kelas_id', $kelasAsalId)
                ->where('status', 'Aktif')
                ->whereNull('deleted_at')
                ->first();
            abort_unless($rkLama && $rkLama->siswa, 422, 'Siswa yang dipilih bukan siswa aktif di kelas asal.');
            $riwayatAsalBySiswa[$siswaId] = $rkLama;
        }

        DB::beginTransaction();
        try {
            $taBaru = SekolahTahunAjaran::firstOrCreate(
                ['nama' => $taTujuan],
                ['sedang_berjalan' => 0]
            );

            $naikCount = 0;
            $lulusCount = 0;
            $keluarCount = 0;
            $riwayatBaruIds = [];
            $dataSebelumnya = [];

            foreach ($validated['keputusan'] as $siswaId => $aksi) {
                $rkLama = $riwayatAsalBySiswa[$siswaId];
                $siswa = $rkLama->siswa;
                $dataSebelumnya[] = [
                    'type' => 'riwayat_lama',
                    'id' => $rkLama->id,
                    'kelas_id' => $rkLama->kelas_id,
                    'tahun_ajaran_id' => $rkLama->tahun_ajaran_id,
                    'status' => $rkLama->status,
                    'keputusan_kenaikan' => $rkLama->keputusan_kenaikan,
                    'kelas_tujuan_id' => $rkLama->kelas_tujuan_id,
                    'tanggal_keputusan_kenaikan' => $rkLama->tanggal_keputusan_kenaikan,
                    'siswa_id' => $siswa->id,
                    'siswa_tahun_lulus' => $siswa->tahun_lulus,
                    'siswa_alasan_keluar' => $siswa->alasan_keluar,
                    'siswa_kelas_keluar' => $siswa->kelas_keluar,
                    'siswa_tanggal_keluar' => $siswa->tanggal_keluar,
                ];

                $tujuanKelas = null;
                if ($aksi === 'Naik') {
                    $namaKelasTujuan = $request->input("kelas_tujuan.{$siswaId}", $kelasAsalObj->nama);
                    $tujuanKelas = SekolahKelas::where('nama', $namaKelasTujuan)->first();
                    if (! $tujuanKelas && $isAdmin) {
                        $tujuanKelas = SekolahKelas::firstOrCreate(
                            ['nama' => $namaKelasTujuan],
                            ['tingkat' => $this->tingkatKelas($namaKelasTujuan)]
                        );
                    }
                    abort_unless($tujuanKelas, 422, 'Kelas tujuan tidak ditemukan.');
                } elseif ($aksi === 'Tinggal') {
                    $tujuanKelas = $kelasAsalObj;
                }

                if ($aksi === 'Naik' || $aksi === 'Tinggal') {
                    $rkLama->update([
                        'keputusan_kenaikan' => $aksi === 'Naik' ? 'NAIK' : 'TIDAK NAIK',
                        'kelas_tujuan_id' => $tujuanKelas->id,
                        'tanggal_keputusan_kenaikan' => now()->toDateString(),
                    ]);
                    $this->simpanRiwayatTujuan(
                        (int) $siswa->id,
                        (int) $tujuanKelas->id,
                        $taBaru,
                        $riwayatBaruIds,
                        $dataSebelumnya
                    );
                    $naikCount++;
                } elseif ($aksi === 'Lulus') {
                    $rkLama->update([
                        'status' => 'Lulus',
                        'keputusan_kenaikan' => 'LULUS',
                        'kelas_tujuan_id' => null,
                        'tanggal_keputusan_kenaikan' => now()->toDateString(),
                    ]);
                    $siswa->update([
                        'tahun_lulus' => substr($taAktif, 5, 4) ?: now()->format('Y'),
                        'alasan_keluar' => 'Lulus',
                        'kelas_keluar' => $kelasAsalObj->nama,
                    ]);
                    $lulusCount++;
                } else {
                    $rkLama->update([
                        'status' => 'Keluar',
                        'keputusan_kenaikan' => null,
                        'kelas_tujuan_id' => null,
                        'tanggal_keputusan_kenaikan' => now()->toDateString(),
                    ]);
                    $siswa->update([
                        'alasan_keluar' => 'Keluar',
                        'kelas_keluar' => $kelasAsalObj->nama,
                        'tanggal_keluar' => now()->toDateString(),
                    ]);
                    $keluarCount++;
                }
            }

            CatatanKenaikanKelas::create([
                'pengguna_id' => $pengguna->id,
                'kelas_asal_id' => $kelasAsalId,
                'tahun_ajaran_asal_id' => $taAktifId,
                'tahun_ajaran_tujuan_id' => $taBaru->id,
                'jumlah_siswa' => count($validated['keputusan']),
                'riwayat_baru' => json_encode($riwayatBaruIds),
                'data_sebelumnya' => json_encode($dataSebelumnya),
                'created_at' => now(),
            ]);

            CatatanAktivitas::create([
                'pengguna_id' => $pengguna->id,
                'peran' => $pengguna->peran,
                'aksi' => 'kenaikan_kelas',
                'keterangan' => "Kenaikan kelas {$kelasAsalObj->nama} ({$taAktif} -> {$taTujuan}): {$naikCount} lanjut, {$lulusCount} lulus, {$keluarCount} keluar.",
                'objek_tipe' => 'kenaikan_kelas',
                'alamat_ip' => $request->ip(),
            ]);

            DB::commit();

            $routePrefix = $isAdmin ? 'admin' : 'guru';

            return redirect()->route($routePrefix.'.kenaikan-kelas', ['kelas' => $kelasAsalObj->nama])
                ->with('success', "Proses kenaikan kelas {$kelasAsalObj->nama} berhasil: {$naikCount} lanjut ke {$taTujuan}, {$lulusCount} lulus, {$keluarCount} keluar.");
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Gagal memproses kenaikan kelas: '.$e->getMessage());
        }
    }

    public function batalkan(Request $request, $id, AksesGuru $aksesGuru)
    {
        $catatan = CatatanKenaikanKelas::findOrFail($id);
        if ($catatan->dibatalkan_at) {
            return back()->with('error', 'Kenaikan kelas ini sudah pernah dibatalkan.');
        }

        $pengguna = $request->user();
        $isAdmin = $pengguna->peran === 'admin';
        if (! $isAdmin) {
            $tahunBerjalanId = SekolahTahunAjaran::where('sedang_berjalan', 1)->value('id');
            abort_unless(
                $tahunBerjalanId
                    && (int) $catatan->tahun_ajaran_asal_id === (int) $tahunBerjalanId
                    && $aksesGuru->bolehKenaikanKelas($pengguna, (int) $catatan->kelas_asal_id),
                403,
                'Anda tidak berhak membatalkan kenaikan kelas ini.'
            );

            $idKenaikanTerakhir = CatatanKenaikanKelas::query()
                ->where('kelas_asal_id', $catatan->kelas_asal_id)
                ->where('tahun_ajaran_asal_id', $tahunBerjalanId)
                ->whereNull('dibatalkan_at')
                ->latest('id')
                ->value('id');
            abort_unless((int) $idKenaikanTerakhir === (int) $catatan->id, 403, 'Hanya kenaikan kelas terakhir yang dapat dibatalkan.');
        }

        DB::beginTransaction();
        try {
            // 1. Kembalikan data lama
            $dataSebelumnya = json_decode($catatan->data_sebelumnya ?? '[]', true);
            foreach ($dataSebelumnya as $b) {
                if (($b['type'] ?? null) === 'riwayat_lama') {
                    SiswaRiwayatKelas::where('id', $b['id'])->update([
                        'kelas_id'                   => $b['kelas_id'],
                        'tahun_ajaran_id'            => $b['tahun_ajaran_id'],
                        'status'                     => $b['status'],
                        'keputusan_kenaikan'         => $b['keputusan_kenaikan'],
                        'kelas_tujuan_id'            => $b['kelas_tujuan_id'],
                        'tanggal_keputusan_kenaikan' => $b['tanggal_keputusan_kenaikan'],
                    ]);
                } elseif (($b['type'] ?? null) === 'riwayat_tujuan_sebelumnya') {
                    SiswaRiwayatKelas::where('id', $b['id'])->update([
                        'kelas_id' => $b['kelas_id'],
                        'tahun_ajaran_id' => $b['tahun_ajaran_id'],
                        'status' => $b['status'],
                        'wali_guru_id' => $b['wali_guru_id'],
                        'nama_wali_kelas' => $b['nama_wali_kelas'],
                        'nip_wali_kelas' => $b['nip_wali_kelas'],
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
                'pengguna_id' => $pengguna->id,
                'peran'       => $pengguna->peran,
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

    private function simpanRiwayatTujuan(
        int $siswaId,
        int $kelasTujuanId,
        SekolahTahunAjaran $tahunAjaranTujuan,
        array &$riwayatBaruIds,
        array &$dataSebelumnya
    ): void {
        $riwayatTujuan = SiswaRiwayatKelas::where('siswa_id', $siswaId)
            ->where('tahun_ajaran_id', $tahunAjaranTujuan->id)
            ->first();

        if ($riwayatTujuan) {
            $dataSebelumnya[] = [
                'type' => 'riwayat_tujuan_sebelumnya',
                'id' => $riwayatTujuan->id,
                'kelas_id' => $riwayatTujuan->kelas_id,
                'tahun_ajaran_id' => $riwayatTujuan->tahun_ajaran_id,
                'status' => $riwayatTujuan->status,
                'wali_guru_id' => $riwayatTujuan->wali_guru_id,
                'nama_wali_kelas' => $riwayatTujuan->nama_wali_kelas,
                'nip_wali_kelas' => $riwayatTujuan->nip_wali_kelas,
            ];
        }

        $wali = GuruWaliKelas::with('guru')
            ->where('tahun_ajaran_id', $tahunAjaranTujuan->id)
            ->where('kelas_id', $kelasTujuanId)
            ->first();

        $data = [
            'kelas_id' => $kelasTujuanId,
            'status' => 'Aktif',
            'wali_guru_id' => $wali?->guru_id,
            'nama_wali_kelas' => $wali?->guru?->nama,
            'nip_wali_kelas' => $wali?->guru?->nip,
        ];

        if ($riwayatTujuan) {
            $riwayatTujuan->update($data);
        } else {
            $riwayatTujuan = SiswaRiwayatKelas::create($data + [
                'siswa_id' => $siswaId,
                'tahun_ajaran_id' => $tahunAjaranTujuan->id,
            ]);
            $riwayatBaruIds[] = $riwayatTujuan->id;
        }
    }

    private function tahunAjaranBerikutnya(string $tahunAjaran): string
    {
        $parts = explode('/', $tahunAjaran);

        return count($parts) === 2 && is_numeric($parts[0]) && is_numeric($parts[1])
            ? ((int) $parts[0] + 1).'/'.((int) $parts[1] + 1)
            : '2025/2026';
    }

    private function tingkatKelas(string $namaKelas): string
    {
        if (str_starts_with(strtoupper($namaKelas), 'XII-')) {
            return 'XII';
        }

        return str_starts_with(strtoupper($namaKelas), 'XI-') ? 'XI' : 'X';
    }
}
