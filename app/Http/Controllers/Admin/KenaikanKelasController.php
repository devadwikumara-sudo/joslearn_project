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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class KenaikanKelasController extends Controller
{
    public function index(Request $request, AksesGuru $aksesGuru): View
    {
        $pengguna = $request->user();
        $isAdmin = $pengguna->peran === 'admin';
        $routePrefix = $isAdmin ? 'admin' : 'guru';
        $tab = $request->query('tab', 'kenaikan');
        abort_unless(
            is_string($tab) && ($isAdmin ? in_array($tab, ['kenaikan', 'mutasi'], true) : $tab === 'kenaikan'),
            403,
            'Tab ini tidak tersedia untuk akun Anda.'
        );

        $tahunAjaranList = SekolahTahunAjaran::query()->orderByDesc('id')->get(['id', 'nama']);
        $taAktifObj = SekolahTahunAjaran::where('sedang_berjalan', 1)->first();
        if ($isAdmin) {
            if ($request->filled('tahun_ajaran')) {
                $tahunAjaranTerpilih = $request->query('tahun_ajaran');
                abort_unless(is_string($tahunAjaranTerpilih) && ctype_digit($tahunAjaranTerpilih), 404);
                $taAktifObj = SekolahTahunAjaran::findOrFail((int) $tahunAjaranTerpilih);
            } else {
                $taAktifObj ??= SekolahTahunAjaran::latest('id')->first();
            }
        } else {
            abort_unless($taAktifObj, 403, 'Tidak ada tahun ajaran berjalan.');
            if ($request->filled('tahun_ajaran')) {
                $tahunAjaranTerpilih = $request->query('tahun_ajaran');
                abort_unless(
                    is_string($tahunAjaranTerpilih)
                        && ctype_digit($tahunAjaranTerpilih)
                        && (int) $tahunAjaranTerpilih === (int) $taAktifObj->id,
                    403,
                    'Wali kelas hanya dapat melihat tahun ajaran berjalan.'
                );
            }
            $tahunAjaranList = collect([$taAktifObj]);
        }

        $taAktif = $taAktifObj ? $taAktifObj->nama : '2024/2025';
        $taAktifId = $taAktifObj?->id ?? 0;
        $waliKelasList = $aksesGuru->kelasWali($pengguna);

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

        $kelasDefault = $isAdmin && $tab === 'mutasi' ? '*' : ($kelasAsalList[0] ?? '');
        $kelasAsalTerpilih = $request->query('kelas', $kelasDefault);
        abort_unless(is_string($kelasAsalTerpilih), 422, 'Kelas yang dipilih tidak valid.');
        $kelasAsal = $kelasAsalTerpilih;
        $kelasObj = $kelasAsal === '*' ? null : SekolahKelas::where('nama', $kelasAsal)->first();
        abort_unless($isAdmin || ($kelasObj && $aksesGuru->bolehKenaikanKelas($pengguna, (int) $kelasObj->id)), 403, 'Anda bukan wali kelas untuk kelas yang dipilih.');

        $kelasList = $kelasTujuanList;
        $tingkatAsalKelas = $this->tingkatKelas($kelasAsal);
        $isKelas12 = $tingkatAsalKelas === 'XII';
        $tingkatTujuanNaik = match ($tingkatAsalKelas) {
            'X' => 'XI',
            'XI' => 'XII',
            default => null,
        };
        $kelasNaikList = $tingkatTujuanNaik === null
            ? []
            : array_values(array_filter(
                $kelasList,
                fn (string $namaKelas): bool => $this->tingkatKelas($namaKelas) === $tingkatTujuanNaik
            ));
        $kelasAsalId = $kelasAsal === '*' ? null : ($kelasObj?->id ?? 0);
        $filterRiwayatKelas = function (Builder|Relation $query) use ($taAktifId, $kelasAsalId): void {
            $this->filterRiwayatKelas($query, (int) $taAktifId, $kelasAsalId);
        };
        $filterRiwayatAktif = function (Builder|Relation $query) use ($filterRiwayatKelas): void {
            $filterRiwayatKelas($query);
            $query->where('status', 'Aktif');
        };
        $filterRiwayatBelumDiproses = function (Builder|Relation $query) use ($filterRiwayatAktif): void {
            $filterRiwayatAktif($query);
            $query->whereNull('keputusan_kenaikan');
        };

        $siswaList = $kelasAsal === '*'
            ? collect()
            : SiswaBiodata::aktif((int) $taAktifId)
                ->whereHas('riwayatKelas', $filterRiwayatBelumDiproses)
                ->with(['riwayatKelas' => function (Builder|Relation $query) use ($filterRiwayatBelumDiproses): void {
                    $filterRiwayatBelumDiproses($query);
                    $query->with('kelas');
                }])
                ->get();

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

        $riwayatQuery = CatatanKenaikanKelas::with(['kelasAsal', 'tahunAjaranAsal', 'tahunAjaranTujuan'])
            ->whereNull('dibatalkan_at')
            ->where('tahun_ajaran_asal_id', $taAktifId);
        $riwayatKenaikanList = $riwayatQuery->latest('id')->get();
        $latestBatchIdBySourceClass = [];
        foreach ($riwayatKenaikanList as $batch) {
            $latestBatchIdBySourceClass[(int) $batch->kelas_asal_id] ??= (int) $batch->id;
        }
        $kelasWaliIds = array_map('intval', $waliKelasList->modelKeys());
        $riwayatSumberKenaikan = collect();
        foreach ($riwayatKenaikanList as $batch) {
            $dataSebelumnya = json_decode($batch->data_sebelumnya ?? '[]', true);
            foreach (is_array($dataSebelumnya) ? $dataSebelumnya : [] as $entry) {
                if (! is_array($entry) || ($entry['type'] ?? null) !== 'riwayat_lama' || ! isset($entry['id'])) {
                    continue;
                }

                $riwayatSumberKenaikan->push([
                    'riwayat_id' => (int) $entry['id'],
                    'batch' => $batch,
                ]);
            }
        }

        $riwayatKenaikanById = SiswaRiwayatKelas::with(['siswa', 'kelas', 'kelasTujuan'])
            ->whereIn('id', $riwayatSumberKenaikan->pluck('riwayat_id')->unique()->all())
            ->get()
            ->keyBy('id');
        $riwayatKenaikanSiswa = $riwayatSumberKenaikan
            ->map(function (array $entry) use ($riwayatKenaikanById, $isAdmin, $kelasWaliIds, $latestBatchIdBySourceClass): ?array {
                $riwayat = $riwayatKenaikanById->get($entry['riwayat_id']);
                if ($riwayat === null) {
                    return null;
                }

                $batch = $entry['batch'];
                $keputusan = match ($riwayat->keputusan_kenaikan) {
                    'NAIK' => 'Naik',
                    'TIDAK NAIK' => 'Tinggal',
                    'LULUS' => 'Lulus',
                    default => $riwayat->status === 'Keluar' ? 'Keluar' : $riwayat->status,
                };

                return [
                    'nama_siswa' => $riwayat->siswa?->nama_lengkap ?? '-',
                    'nisn' => $riwayat->siswa?->nisn,
                    'siswa_id' => (int) $riwayat->siswa_id,
                    'batch_id' => (int) $batch->id,
                    'kelas_asal' => $riwayat->kelas?->nama ?? '-',
                    'kelas_asal_id' => (int) $riwayat->kelas_id,
                    'keputusan' => $keputusan,
                    'kelas_tujuan' => $riwayat->kelasTujuan?->nama,
                    'kelas_tujuan_id' => $riwayat->kelas_tujuan_id === null
                        ? null
                        : (int) $riwayat->kelas_tujuan_id,
                    'tahun_tujuan' => in_array($keputusan, ['Naik', 'Tinggal'], true)
                        ? $batch->tahunAjaranTujuan?->nama
                        : null,
                    'tahun_tujuan_id' => in_array($keputusan, ['Naik', 'Tinggal'], true)
                        ? ($batch->tahun_ajaran_tujuan_id === null
                            ? null
                            : (int) $batch->tahun_ajaran_tujuan_id)
                        : null,
                    'tanggal' => $riwayat->tanggal_keputusan_kenaikan ?? $batch->created_at,
                    'boleh_hapus' => $isAdmin || (
                        in_array((int) $batch->kelas_asal_id, $kelasWaliIds, true)
                        && ($latestBatchIdBySourceClass[(int) $batch->kelas_asal_id] ?? null) === (int) $batch->id
                    ),
                ];
            })
            ->filter()
            ->values();
        if ($kelasAsalId !== null) {
            $riwayatKenaikanSiswa = $riwayatKenaikanSiswa
                ->filter(fn (array $riwayat): bool => $riwayat['kelas_asal_id'] === $kelasAsalId
                    || $riwayat['kelas_tujuan_id'] === $kelasAsalId)
                ->values();
        }
        $batchRiwayatTerakhirId = $riwayatKenaikanSiswa->first()['batch_id'] ?? null;
        $riwayatKenaikanTerakhir = $batchRiwayatTerakhirId === null
            ? null
            : $riwayatKenaikanList->firstWhere('id', $batchRiwayatTerakhirId);
        $riwayatKenaikanTerakhirBolehDibatalkan = $riwayatKenaikanTerakhir !== null && ($isAdmin || (
            in_array((int) $riwayatKenaikanTerakhir->kelas_asal_id, $kelasWaliIds, true)
            && ($latestBatchIdBySourceClass[(int) $riwayatKenaikanTerakhir->kelas_asal_id] ?? null) === (int) $riwayatKenaikanTerakhir->id
        ));

        $siswaAktif = collect();
        $riwayatMutasi = collect();
        if ($isAdmin) {
            $siswaAktif = SiswaBiodata::aktif((int) $taAktifId)
                ->whereHas('riwayatKelas', $filterRiwayatAktif)
                ->with(['riwayatKelas' => function (Builder|Relation $query) use ($filterRiwayatAktif): void {
                    $filterRiwayatAktif($query);
                    $query->with('kelas');
                }])
                ->orderBy('nama_lengkap')
                ->get();

            $riwayatMutasi = SiswaBiodata::query()
                ->where(function (Builder $query) use ($filterRiwayatKelas): void {
                    $query->whereHas('riwayatKelas', function (Builder $historyQuery) use ($filterRiwayatKelas): void {
                        $filterRiwayatKelas($historyQuery);
                        $historyQuery->whereIn('status', ['Pindah keluar', 'Keluar']);
                    })->orWhere(function (Builder $incomingQuery) use ($filterRiwayatKelas): void {
                        $incomingQuery->where('jenis_registrasi', 'Pindahan')
                            ->whereHas('riwayatKelas', function (Builder $historyQuery) use ($filterRiwayatKelas): void {
                                $filterRiwayatKelas($historyQuery);
                            });
                    });
                })
                ->with(['riwayatKelas' => function (Builder|Relation $query) use ($filterRiwayatKelas): void {
                    $filterRiwayatKelas($query);
                    $query->with('kelas');
                }])
                ->latest('updated_at')
                ->get();
        }

        return view('admin.kenaikan-mutasi', compact(
            'taAktif',
            'taAktifId',
            'taBerikutnya',
            'kelasList',
            'kelasNaikList',
            'kelasAsalList',
            'kelasAsal',
            'isKelas12',
            'siswaList',
            'nextKelasMap',
            'riwayatKenaikanTerakhir',
            'riwayatKenaikanTerakhirBolehDibatalkan',
            'riwayatKenaikanSiswa',
            'routePrefix',
            'tab',
            'isAdmin',
            'tahunAjaranList',
            'siswaAktif',
            'riwayatMutasi'
        ));
    }

    private function filterRiwayatKelas(Builder|Relation $query, int $tahunAjaranId, ?int $kelasId): void
    {
        $query->where('tahun_ajaran_id', $tahunAjaranId)
            ->whereNull('deleted_at');
        if ($kelasId !== null) {
            $query->where('kelas_id', $kelasId);
        }
    }

    public function proses(Request $request, AksesGuru $aksesGuru): RedirectResponse
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

        $tingkatAsal = $this->tingkatKelas($validated['kelas_asal']);
        if ($tingkatAsal === null) {
            throw ValidationException::withMessages([
                'kelas_asal' => 'Format kelas asal harus berupa X-..., XI-..., atau XII-....',
            ]);
        }
        $kelasAsalObj = SekolahKelas::where('nama', $validated['kelas_asal'])->first();
        if ($kelasAsalObj === null || $kelasAsalObj->tingkat !== $tingkatAsal) {
            throw ValidationException::withMessages([
                'kelas_asal' => 'Kelas asal tidak ditemukan atau tingkatnya tidak sesuai.',
            ]);
        }
        abort_unless(
            $isAdmin || $aksesGuru->bolehKenaikanKelas($pengguna, (int) $kelasAsalObj->id),
            403,
            'Anda bukan wali kelas untuk kelas yang dipilih.'
        );

        $tingkatTujuan = match ($tingkatAsal) {
            'X' => 'XI',
            'XI' => 'XII',
            default => null,
        };
        $kelasTujuanPerSiswa = [];
        foreach ($validated['keputusan'] as $siswaId => $aksi) {
            if ($aksi === 'Lulus' && $tingkatAsal !== 'XII') {
                throw ValidationException::withMessages([
                    "keputusan.{$siswaId}" => 'Kelulusan hanya dapat diproses untuk siswa kelas XII.',
                ]);
            }

            if ($aksi === 'Naik') {
                if ($tingkatTujuan === null) {
                    throw ValidationException::withMessages([
                        "keputusan.{$siswaId}" => 'Siswa kelas XII tidak dapat dinaikkan ke tingkat berikutnya.',
                    ]);
                }

                $namaKelasTujuan = trim((string) ($validated['kelas_tujuan'][$siswaId] ?? ''));
                $tingkatKelasTujuan = $this->tingkatKelas($namaKelasTujuan);
                if ($tingkatKelasTujuan !== $tingkatTujuan) {
                    throw ValidationException::withMessages([
                        "kelas_tujuan.{$siswaId}" => "Kelas tujuan wajib berada satu tingkat di atas kelas asal ({$tingkatTujuan}).",
                    ]);
                }

                $kelasTujuan = SekolahKelas::where('nama', $namaKelasTujuan)->first();
                if ($kelasTujuan !== null && $kelasTujuan->tingkat !== $tingkatKelasTujuan) {
                    throw ValidationException::withMessages([
                        "kelas_tujuan.{$siswaId}" => 'Tingkat kelas tujuan tidak sesuai dengan nama kelas.',
                    ]);
                }
                if ($kelasTujuan === null && ! $isAdmin) {
                    throw ValidationException::withMessages([
                        "kelas_tujuan.{$siswaId}" => 'Kelas tujuan tidak ditemukan pada data kelas sekolah.',
                    ]);
                }

                $kelasTujuanPerSiswa[$siswaId] = $namaKelasTujuan;
            }
        }

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
                ->whereHas('siswa', fn (Builder $query) => $query->aktif($taAktifId))
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
            $tinggalCount = 0;
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
                    $namaKelasTujuan = $kelasTujuanPerSiswa[$siswaId];
                    if ($isAdmin) {
                        $tujuanKelas = SekolahKelas::firstOrCreate(
                            ['nama' => $namaKelasTujuan],
                            ['tingkat' => $this->tingkatKelas($namaKelasTujuan)]
                        );
                    } else {
                        $tujuanKelas = SekolahKelas::where('nama', $namaKelasTujuan)->first();
                    }
                    if ($tujuanKelas === null) {
                        throw new \RuntimeException('Kelas tujuan tidak tersedia saat proses kenaikan berlangsung.');
                    }
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
                    if ($aksi === 'Naik') {
                        $naikCount++;
                    } else {
                        $tinggalCount++;
                    }
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
                'keterangan' => "Kenaikan kelas {$kelasAsalObj->nama} ({$taAktif} -> {$taTujuan}): {$naikCount} naik, {$tinggalCount} tinggal, {$lulusCount} lulus, {$keluarCount} keluar.",
                'objek_tipe' => 'kenaikan_kelas',
                'alamat_ip' => $request->ip(),
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal memproses kenaikan kelas.', [
                'pengguna_id' => $pengguna->id,
                'kelas_asal_id' => $kelasAsalId,
                'tahun_ajaran_id' => $taAktifId,
                'exception' => $e,
            ]);

            return back()->with('error', 'Proses kenaikan kelas gagal. Silakan coba kembali atau hubungi administrator.');
        }

        if ($naikCount > 0 || $tinggalCount > 0) {
            $request->session()->put('data_siswa_tahun_ajaran_id', (int) $taBaru->id);
        }

        $routePrefix = $isAdmin ? 'admin' : 'guru';
        $successMessage = "Proses kenaikan kelas {$kelasAsalObj->nama} berhasil: {$naikCount} naik ke {$taTujuan}, {$tinggalCount} tinggal di kelas asal, {$lulusCount} lulus, {$keluarCount} keluar.";

        return redirect()->to(route($routePrefix.'.kenaikan-mutasi', [
            'tab' => 'kenaikan',
            'tahun_ajaran' => $taAktifId,
            'kelas' => $kelasAsalObj->nama,
        ]).'#riwayat')->with('success', $successMessage);
    }

    public function batalkan(Request $request, $id, AksesGuru $aksesGuru)
    {
        $catatan = CatatanKenaikanKelas::findOrFail($id);
        if ($catatan->dibatalkan_at) {
            return back()->with('error', 'Kenaikan kelas ini sudah pernah dibatalkan.');
        }

        $pengguna = $request->user();
        $this->authorizeBatchCancellation($request, $catatan, $aksesGuru);

        DB::beginTransaction();
        try {
            // 1. Kembalikan data lama
            $dataSebelumnya = json_decode($catatan->data_sebelumnya ?? '[]', true);
            foreach ($dataSebelumnya as $b) {
                if (($b['type'] ?? null) === 'riwayat_lama') {
                    SiswaRiwayatKelas::where('id', $b['id'])->update([
                        'kelas_id' => $b['kelas_id'],
                        'tahun_ajaran_id' => $b['tahun_ajaran_id'],
                        'status' => $b['status'],
                        'keputusan_kenaikan' => $b['keputusan_kenaikan'],
                        'kelas_tujuan_id' => $b['kelas_tujuan_id'],
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
                        'tahun_lulus' => $b['siswa_tahun_lulus'],
                        'alasan_keluar' => $b['siswa_alasan_keluar'],
                        'kelas_keluar' => $b['siswa_kelas_keluar'],
                        'tanggal_keluar' => $b['siswa_tanggal_keluar'],
                    ]);
                }
            }

            // 2. Hapus baris riwayat kelas baru yang dibuat oleh proses ini
            $riwayatBaru = json_decode($catatan->riwayat_baru ?? '[]', true);
            if (! empty($riwayatBaru)) {
                SiswaRiwayatKelas::whereIn('id', $riwayatBaru)->delete();
            }

            // 3. Tandai catatan dibatalkan
            $catatan->update(['dibatalkan_at' => now()]);

            CatatanAktivitas::create([
                'pengguna_id' => $pengguna->id,
                'peran' => $pengguna->peran,
                'aksi' => 'batalkan_kenaikan',
                'keterangan' => "Membatalkan kenaikan kelas batch #{$id}.",
                'objek_tipe' => 'kenaikan_kelas',
                'alamat_ip' => $request->ip(),
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal membatalkan kenaikan kelas.', [
                'pengguna_id' => $pengguna->id,
                'catatan_kenaikan_id' => $id,
                'exception' => $e,
            ]);

            return back()->with('error', 'Pembatalan kenaikan kelas gagal. Silakan coba kembali atau hubungi administrator.');
        }

        $request->session()->put('data_siswa_tahun_ajaran_id', (int) $catatan->tahun_ajaran_asal_id);

        return back()->with('success', 'Kenaikan kelas berhasil dibatalkan. Riwayat siswa dikembalikan ke kondisi sebelumnya.');
    }

    public function batalkanSiswa(Request $request, int $id, int $siswaId, AksesGuru $aksesGuru): RedirectResponse
    {
        $hasil = DB::transaction(function () use ($request, $id, $siswaId, $aksesGuru): array {
            $catatan = CatatanKenaikanKelas::with('kelasAsal')
                ->lockForUpdate()
                ->findOrFail($id);
            abort_unless($catatan->dibatalkan_at === null, 409, 'Riwayat kenaikan ini sudah dibatalkan.');
            $this->authorizeBatchCancellation($request, $catatan, $aksesGuru);

            $dataSebelumnya = json_decode($catatan->data_sebelumnya ?? '[]', true);
            $riwayatBaru = json_decode($catatan->riwayat_baru ?? '[]', true);
            abort_unless(is_array($dataSebelumnya) && is_array($riwayatBaru), 409, 'Data riwayat kenaikan tidak dapat dibaca.');

            $snapshotSumber = collect($dataSebelumnya)->first(
                fn (mixed $entry): bool => is_array($entry)
                    && ($entry['type'] ?? null) === 'riwayat_lama'
                    && (int) ($entry['siswa_id'] ?? 0) === $siswaId
            );
            abort_unless(is_array($snapshotSumber), 404, 'Siswa tidak ditemukan pada riwayat kenaikan ini.');

            $riwayatSumber = SiswaRiwayatKelas::withTrashed()
                ->whereKey((int) $snapshotSumber['id'])
                ->where('siswa_id', $siswaId)
                ->lockForUpdate()
                ->firstOrFail();

            $snapshotTujuan = collect($dataSebelumnya)
                ->filter(fn (mixed $entry): bool => is_array($entry)
                    && ($entry['type'] ?? null) === 'riwayat_tujuan_sebelumnya'
                    && isset($entry['id']))
                ->values();
            $riwayatTujuanLama = SiswaRiwayatKelas::withTrashed()
                ->whereIn('id', $snapshotTujuan->pluck('id')->map(fn ($targetId): int => (int) $targetId)->all())
                ->where('siswa_id', $siswaId)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            $riwayatTujuanBaru = SiswaRiwayatKelas::withTrashed()
                ->whereIn('id', array_map('intval', array_filter($riwayatBaru, 'is_numeric')))
                ->where('siswa_id', $siswaId)
                ->lockForUpdate()
                ->get();

            if ($riwayatSumber->trashed()) {
                $riwayatSumber->restore();
            }
            $riwayatSumber->update([
                'kelas_id' => $snapshotSumber['kelas_id'],
                'tahun_ajaran_id' => $snapshotSumber['tahun_ajaran_id'],
                'status' => $snapshotSumber['status'],
                'keputusan_kenaikan' => $snapshotSumber['keputusan_kenaikan'],
                'kelas_tujuan_id' => $snapshotSumber['kelas_tujuan_id'],
                'tanggal_keputusan_kenaikan' => $snapshotSumber['tanggal_keputusan_kenaikan'],
            ]);

            foreach ($riwayatTujuanLama as $riwayatTujuan) {
                $snapshot = $snapshotTujuan->first(
                    fn (array $entry): bool => (int) $entry['id'] === (int) $riwayatTujuan->id
                );
                abort_unless(is_array($snapshot), 409, 'Riwayat kelas tujuan sebelumnya tidak ditemukan.');
                if ($riwayatTujuan->trashed()) {
                    $riwayatTujuan->restore();
                }
                $riwayatTujuan->update([
                    'kelas_id' => $snapshot['kelas_id'],
                    'tahun_ajaran_id' => $snapshot['tahun_ajaran_id'],
                    'status' => $snapshot['status'],
                    'wali_guru_id' => $snapshot['wali_guru_id'],
                    'nama_wali_kelas' => $snapshot['nama_wali_kelas'],
                    'nip_wali_kelas' => $snapshot['nip_wali_kelas'],
                ]);
            }
            foreach ($riwayatTujuanBaru as $riwayat) {
                if (! $riwayat->trashed()) {
                    $riwayat->delete();
                }
            }

            $siswaQuery = SiswaBiodata::withTrashed()->whereKey($siswaId);
            abort_unless($siswaQuery->exists(), 404, 'Data siswa tidak ditemukan.');
            $siswaQuery->update([
                'tahun_lulus' => $snapshotSumber['siswa_tahun_lulus'],
                'alasan_keluar' => $snapshotSumber['siswa_alasan_keluar'],
                'kelas_keluar' => $snapshotSumber['siswa_kelas_keluar'],
                'tanggal_keluar' => $snapshotSumber['siswa_tanggal_keluar'],
            ]);

            $riwayatTujuanLamaIds = $riwayatTujuanLama->modelKeys();
            $riwayatTujuanBaruIds = $riwayatTujuanBaru->modelKeys();
            $dataSebelumnyaTersisa = array_values(array_filter(
                $dataSebelumnya,
                fn (mixed $entry): bool => ! is_array($entry)
                    || ! (($entry['type'] ?? null) === 'riwayat_lama'
                        && (int) ($entry['id'] ?? 0) === (int) $snapshotSumber['id'])
                    && ! (($entry['type'] ?? null) === 'riwayat_tujuan_sebelumnya'
                        && in_array((int) ($entry['id'] ?? 0), $riwayatTujuanLamaIds, true))
            ));
            $riwayatBaruTersisa = array_values(array_filter(
                $riwayatBaru,
                fn (mixed $targetId): bool => ! in_array((int) $targetId, $riwayatTujuanBaruIds, true)
            ));
            $jumlahSiswaTersisa = count(array_filter(
                $dataSebelumnyaTersisa,
                fn (mixed $entry): bool => is_array($entry) && ($entry['type'] ?? null) === 'riwayat_lama'
            ));

            $perubahanCatatan = [
                'jumlah_siswa' => $jumlahSiswaTersisa,
                'data_sebelumnya' => json_encode($dataSebelumnyaTersisa, JSON_THROW_ON_ERROR),
                'riwayat_baru' => json_encode($riwayatBaruTersisa, JSON_THROW_ON_ERROR),
            ];
            if ($jumlahSiswaTersisa === 0) {
                $perubahanCatatan['dibatalkan_at'] = now();
            }
            $catatan->update($perubahanCatatan);

            $pengguna = $request->user();
            CatatanAktivitas::create([
                'pengguna_id' => $pengguna->id,
                'peran' => $pengguna->peran,
                'aksi' => 'batalkan_kenaikan_siswa',
                'keterangan' => "Membatalkan kenaikan siswa #{$siswaId} pada batch #{$catatan->id}.",
                'objek_tipe' => 'siswa_riwayat_kelas',
                'objek_id' => (int) $snapshotSumber['id'],
                'alamat_ip' => $request->ip(),
            ]);

            $kelasAsal = $catatan->kelasAsal?->nama;
            abort_unless(is_string($kelasAsal) && $kelasAsal !== '', 404, 'Kelas asal tidak ditemukan.');

            return [
                'tahun_ajaran_id' => (int) $catatan->tahun_ajaran_asal_id,
                'kelas_asal' => $kelasAsal,
            ];
        });

        $request->session()->put('data_siswa_tahun_ajaran_id', $hasil['tahun_ajaran_id']);
        $routePrefix = $request->user()->peran === 'admin' ? 'admin' : 'guru';

        return redirect()->to(route($routePrefix.'.kenaikan-mutasi', [
            'tab' => 'kenaikan',
            'tahun_ajaran' => $hasil['tahun_ajaran_id'],
            'kelas' => $hasil['kelas_asal'],
        ]).'#riwayat')->with('success', 'Riwayat siswa dihapus dan kenaikan kelasnya dibatalkan. Siswa dikembalikan ke kelas asal.');
    }

    private function authorizeBatchCancellation(Request $request, CatatanKenaikanKelas $catatan, AksesGuru $aksesGuru): void
    {
        $pengguna = $request->user();
        if ($pengguna->peran === 'admin') {
            return;
        }

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

    private function tingkatKelas(string $namaKelas): ?string
    {
        if (preg_match('/^(X|XI|XII)-.+$/', trim($namaKelas), $matches) !== 1) {
            return null;
        }

        return $matches[1];
    }
}
