<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\SekolahTahunAjaran;
use App\Models\SekolahKelas;
use App\Models\SekolahMapel;
use App\Models\SiswaBiodata;
use App\Models\SiswaRiwayatKelas;
use App\Models\SiswaNilai;
use App\Models\SiswaKehadiran;
use App\Models\SiswaEkskul;
use App\Models\GuruBiodata;
use App\Models\Pengguna;
use App\Models\CatatanAktivitas;

class ImportBatchController extends Controller
{
    /**
     * Endpoint untuk menerima data hasil parsing Excel fleksibel (dari SheetJS client-side).
     */
    public function saveBatch(Request $request)
    {
        $type       = $request->input('type', 'siswa'); // siswa, nilai, hadir, ekskul, guru
        $taName     = $request->input('tahun_ajaran', '2024/2025');
        $semester   = $request->input('semester', 'Genap');
        $mode       = $request->input('mode', 'semua');
        $kelasInput = $request->input('kelas', '');
        $dup        = $request->input('dup', 'timpa');
        $rows       = $request->input('rows', []);
        $newClasses = $request->input('new_classes', []);
        $newMapel   = $request->input('new_mapel', []);

        if (empty($rows) || !is_array($rows)) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada data baris yang diterima untuk diimpor.'
            ], 422);
        }

        DB::beginTransaction();
        try {
            // 1. Tahun Ajaran
            $ta = SekolahTahunAjaran::firstOrCreate(
                ['nama' => $taName],
                ['sedang_berjalan' => 0]
            );

            // 2. Buat kelas baru jika ada
            $allClassNames = collect($rows)->pluck('kelas')->merge($newClasses)->unique()->filter()->values();
            foreach ($allClassNames as $cName) {
                $cName = $this->normK($cName);
                if ($cName) {
                    $this->ensureKelas($cName);
                }
            }

            // 3. Buat mapel baru jika ada
            if (!empty($newMapel) && is_array($newMapel)) {
                foreach ($newMapel as $m) {
                    if (!empty($m['kode']) && !empty($m['nama'])) {
                        SekolahMapel::firstOrCreate(
                            ['kode' => strtolower(substr($m['kode'], 0, 3))],
                            ['nama' => $m['nama']]
                        );
                    }
                }
            }

            $savedCount = 0;
            $skippedCount = 0;

            // 4. Proses berdasarkan jenis data
            if ($type === 'siswa') {
                list($savedCount, $skippedCount) = $this->saveSiswa($rows, $ta, $mode, $kelasInput, $dup);
            } elseif ($type === 'nilai') {
                list($savedCount, $skippedCount) = $this->saveNilai($rows, $ta, $semester, $mode, $kelasInput);
            } elseif ($type === 'hadir') {
                list($savedCount, $skippedCount) = $this->saveKehadiran($rows, $ta, $semester, $mode, $kelasInput);
            } elseif ($type === 'ekskul') {
                list($savedCount, $skippedCount) = $this->saveEkskul($rows, $ta, $semester, $mode, $kelasInput);
            } elseif ($type === 'guru') {
                list($savedCount, $skippedCount) = $this->saveGuru($rows);
            }

            // 5. Catat Log Aktivitas
            CatatanAktivitas::create([
                'pengguna_id' => auth()->id(),
                'peran'       => auth()->user()->peran ?? 'admin',
                'aksi'        => 'import_' . $type,
                'keterangan'  => "Import {$type} {$taName}: {$savedCount} baris tersimpan, {$skippedCount} dilewati.",
                'objek_tipe'  => $type,
                'alamat_ip'   => $request->ip(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'count'   => $savedCount,
                'skipped' => $skippedCount,
                'message' => "Berhasil mengimpor {$savedCount} data {$type}" . ($skippedCount > 0 ? " ({$skippedCount} baris dilewati)" : "") . "."
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Import error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyimpan ke database: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Normalisasi nama kelas ke standar (contoh: '10 IPA 2' -> 'X-IPA 2')
     */
    private function normK($k)
    {
        $k = trim(strtoupper($k ?? ''));
        if (!$k || $k === '-') return null;
        $k = preg_replace('/^KELAS\s*/', '', $k);
        if (preg_match('/^(XII|XI|X|12|11|10)(?![A-Z])[\s\-._\/]*(.*)$/', $k, $m)) {
            $rom = ['10' => 'X', '11' => 'XI', '12' => 'XII'];
            $t = $rom[$m[1]] ?? $m[1];
            $r = trim(preg_replace('/[\s\-._]+$/', '', preg_replace('/\s+/', ' ', $m[2])));
            return $r ? "{$t}-{$r}" : "{$t}-1";
        }
        return $k;
    }

    private function ensureKelas($cName)
    {
        $cName = $this->normK($cName);
        if (!$cName) return null;
        $tk = 'X';
        if (str_starts_with($cName, 'XII')) $tk = 'XII';
        elseif (str_starts_with($cName, 'XI')) $tk = 'XI';

        // Pastikan format memenuhi cek_kelas_format: nama LIKE CONCAT(tingkat, '-%')
        if (!str_starts_with($cName, "{$tk}-")) {
            $cName = "{$tk}-" . ltrim(substr($cName, strlen($tk)), '- ');
        }

        return SekolahKelas::firstOrCreate(
            ['nama' => $cName],
            ['tingkat' => $tk]
        );
    }

    private function normDate($val)
    {
        if (empty($val) || $val === '-' || $val === '0000-00-00') return null;
        $val = trim((string)$val);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $val)) return $val;
        if (preg_match('/^(\d{1,2})[\/\.-](\d{1,2})[\/\.-](\d{4})$/', $val, $m)) {
            return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        }
        if (is_numeric($val) && (float)$val > 20000 && (float)$val < 80000) {
            $days = (int)$val - 25569;
            return date('Y-m-d', $days * 86400);
        }
        $ts = strtotime($val);
        return $ts ? date('Y-m-d', $ts) : null;
    }

    private function normN($v)
    {
        $v = trim((string)$v);
        if (preg_match('/^\d{6,9}$/', $v)) {
            return str_pad($v, 10, '0', STR_PAD_LEFT);
        }
        return $v;
    }

    private function cleanVal($v)
    {
        if ($v === null || $v === '' || $v === '-') return null;
        return is_string($v) ? trim($v) : $v;
    }

    private function saveSiswa($rows, $ta, $mode, $kelasInput, $dup)
    {
        $saved = 0;
        $skipped = 0;

        foreach ($rows as $r) {
            $nisn = $this->normN($r['nisn'] ?? '');
            $nama = $this->cleanVal($r['nama'] ?? $r['nama_lengkap'] ?? '');
            if (!$nisn && !$nama) {
                $skipped++;
                continue;
            }

            // Tentukan kelas
            $kStr = $mode === 'semua' ? ($r['kelas'] ?? '') : $kelasInput;
            $kStr = $this->normK($kStr);
            $kelas = $kStr ? $this->ensureKelas($kStr) : null;

            // Jika NISN belum 10 digit tetapi ada nomor induk, buat fallback
            if (!preg_match('/^\d{10}$/', $nisn)) {
                $nisn = str_pad(preg_replace('/\D/', '', $nisn), 10, '0', STR_PAD_LEFT);
                if ($nisn === '0000000000') {
                    $nisn = str_pad(mt_rand(1, 9999999999), 10, '0', STR_PAD_LEFT);
                }
            }

            $siswa = SiswaBiodata::where('nisn', $nisn)->first();
            if ($siswa && $dup === 'skip') {
                // Jangan timpa biodata, cukup update riwayat kelas jika ada
            } else {
                $data = [
                    'nama_lengkap'       => $nama ?: 'Siswa ' . $nisn,
                    'nama_panggilan'     => $this->cleanVal($r['panggilan'] ?? null),
                    'nisn'               => $nisn,
                    'nomor_induk'        => $this->cleanVal($r['nis'] ?? $r['nomor_induk'] ?? null),
                    'jenis_kelamin'      => in_array(strtoupper($r['jk'] ?? ''), ['P', 'PEREMPUAN']) ? 'P' : 'L',
                    'tempat_lahir'       => $this->cleanVal($r['tmp'] ?? $r['tempat_lahir'] ?? null),
                    'tanggal_lahir'      => $this->normDate($r['tgl'] ?? $r['tanggal_lahir'] ?? null),
                    'agama'              => $this->cleanVal($r['agama'] ?? null),
                    'kewarganegaraan'    => $this->cleanVal($r['kewarganegaraan'] ?? 'Warga Negara Indonesia'),
                    'nik'                => preg_match('/^\d{16}$/', (string)($r['nik'] ?? '')) ? $r['nik'] : null,
                    'anak_ke'            => is_numeric($r['anak_ke'] ?? null) ? (int)$r['anak_ke'] : null,
                    'jumlah_saudara'     => $this->cleanVal($r['jml_saudara'] ?? $r['jumlah_saudara'] ?? null),
                    'status_yatim'       => $this->cleanVal($r['yatim'] ?? $r['status_yatim'] ?? null),
                    'bahasa_sehari_hari' => $this->cleanVal($r['bahasa'] ?? $r['bahasa_sehari_hari'] ?? null),
                    'alamat_jalan'       => $this->cleanVal($r['alamat'] ?? $r['alamat_jalan'] ?? null),
                    'alamat_rt_rw'       => $this->cleanVal($r['rt_rw'] ?? $r['alamat_rt_rw'] ?? null),
                    'alamat_desa'        => $this->cleanVal($r['kelurahan'] ?? $r['alamat_desa'] ?? null),
                    'alamat_kecamatan'   => $this->cleanVal($r['kecamatan'] ?? $r['alamat_kecamatan'] ?? null),
                    'alamat_kabupaten'   => $this->cleanVal($r['kab'] ?? $r['alamat_kabupaten'] ?? null),
                    'alamat_provinsi'    => $this->cleanVal($r['prov'] ?? $r['alamat_provinsi'] ?? null),
                    'alamat_kode_pos'    => $this->cleanVal($r['kodepos'] ?? $r['alamat_kode_pos'] ?? null),
                    'nomor_hp'           => $this->cleanVal($r['hp'] ?? $r['nomor_hp'] ?? null),
                    'tempat_tinggal'     => $this->cleanVal($r['tinggal'] ?? $r['tempat_tinggal'] ?? null),
                    'jarak_ke_sekolah_km'=> is_numeric($r['jarak'] ?? null) ? (float)$r['jarak'] : null,
                    'golongan_darah'     => $this->cleanVal($r['gol_darah'] ?? $r['golongan_darah'] ?? null),
                    'penyakit'           => $this->cleanVal($r['penyakit'] ?? null),
                    'kelainan_fisik'     => $this->cleanVal($r['kelainan'] ?? $r['kelainan_fisik'] ?? null),
                    'tinggi_badan_cm'    => is_numeric($r['tinggi'] ?? null) ? (int)$r['tinggi'] : null,
                    'berat_badan_kg'     => is_numeric($r['berat'] ?? null) ? (int)$r['berat'] : null,
                    'sekolah_asal'       => $this->cleanVal($r['asal'] ?? $r['sekolah_asal'] ?? null),
                    'tanggal_ijazah_smp' => $this->cleanVal($r['tgl_ijazah_smp'] ?? null),
                    'nomor_ijazah_smp'   => $this->cleanVal($r['no_ijazah_smp'] ?? $r['nomor_ijazah_smp'] ?? null),
                    'lama_belajar_tahun' => is_numeric($r['lama'] ?? null) ? (int)$r['lama'] : 3,
                    'jenis_registrasi'   => $this->cleanVal($r['masuk'] ?? $r['jenis_registrasi'] ?? 'Siswa Baru'),
                    'pindahan_dari_sekolah' => $this->cleanVal($r['sekolah_pindah'] ?? $r['pindahan_dari_sekolah'] ?? null),
                    'alasan_pindah'      => $this->cleanVal($r['alasan_pindah'] ?? null),
                    'kelas_diterima'     => $this->cleanVal($r['kelas_masuk'] ?? $r['kelas_diterima'] ?? null),
                    'rombel_diterima'    => $this->cleanVal($r['rombel_masuk'] ?? $r['rombel_diterima'] ?? null),
                    'tanggal_diterima'   => $this->normDate($r['tgl_masuk'] ?? $r['tanggal_diterima'] ?? null),

                    // Orang tua
                    'nama_ayah'          => $this->cleanVal($r['ayah'] ?? $r['nama_ayah'] ?? null),
                    'agama_ayah'         => $this->cleanVal($r['agama_ayah'] ?? null),
                    'kewarganegaraan_ayah' => $this->cleanVal($r['wn_ayah'] ?? $r['kewarganegaraan_ayah'] ?? null),
                    'pendidikan_ayah'    => $this->cleanVal($r['pend_ayah'] ?? $r['pendidikan_ayah'] ?? null),
                    'pekerjaan_ayah'     => $this->cleanVal($r['kerja_ayah'] ?? $r['pekerjaan_ayah'] ?? null),
                    'penghasilan_ayah'   => $this->cleanVal($r['gaji_ayah'] ?? $r['penghasilan_ayah'] ?? null),
                    'nomor_hp_ayah'      => $this->cleanVal($r['hp_ayah'] ?? $r['nomor_hp_ayah'] ?? null),
                    'keadaan_ayah'       => $this->cleanVal($r['ket_ayah'] ?? $r['keadaan_ayah'] ?? null),

                    'nama_ibu'           => $this->cleanVal($r['ibu'] ?? $r['nama_ibu'] ?? null),
                    'agama_ibu'          => $this->cleanVal($r['agama_ibu'] ?? null),
                    'kewarganegaraan_ibu'=> $this->cleanVal($r['wn_ibu'] ?? $r['kewarganegaraan_ibu'] ?? null),
                    'pendidikan_ibu'     => $this->cleanVal($r['pend_ibu'] ?? $r['pendidikan_ibu'] ?? null),
                    'pekerjaan_ibu'      => $this->cleanVal($r['kerja_ibu'] ?? $r['pekerjaan_ibu'] ?? null),
                    'penghasilan_ibu'    => $this->cleanVal($r['gaji_ibu'] ?? $r['penghasilan_ibu'] ?? null),
                    'nomor_hp_ibu'       => $this->cleanVal($r['hp_ibu'] ?? $r['nomor_hp_ibu'] ?? null),
                    'keadaan_ibu'        => $this->cleanVal($r['ket_ibu'] ?? $r['keadaan_ibu'] ?? null),

                    'ortu_alamat_jalan'  => $this->cleanVal($r['al_jalan'] ?? $r['ortu_alamat_jalan'] ?? null),
                    'ortu_alamat_rt_rw'  => $this->cleanVal($r['al_rt'] ?? $r['ortu_alamat_rt_rw'] ?? null),
                    'ortu_alamat_desa'   => $this->cleanVal($r['al_desa'] ?? $r['ortu_alamat_desa'] ?? null),
                    'ortu_alamat_kecamatan' => $this->cleanVal($r['al_kec'] ?? $r['ortu_alamat_kecamatan'] ?? null),
                    'ortu_alamat_kabupaten' => $this->cleanVal($r['al_kab'] ?? $r['ortu_alamat_kabupaten'] ?? null),
                    'ortu_alamat_provinsi'  => $this->cleanVal($r['al_prov'] ?? $r['ortu_alamat_provinsi'] ?? null),
                    'ortu_alamat_kode_pos'  => $this->cleanVal($r['al_pos'] ?? $r['ortu_alamat_kode_pos'] ?? null),

                    // Wali
                    'nama_wali'          => $this->cleanVal($r['wali'] ?? $r['nama_wali'] ?? null),
                    'agama_wali'         => $this->cleanVal($r['agama_wali'] ?? null),
                    'kewarganegaraan_wali' => $this->cleanVal($r['wn_wali'] ?? $r['kewarganegaraan_wali'] ?? null),
                    'pendidikan_wali'    => $this->cleanVal($r['pend_wali'] ?? $r['pendidikan_wali'] ?? null),
                    'pekerjaan_wali'     => $this->cleanVal($r['kerja_wali'] ?? $r['pekerjaan_wali'] ?? null),
                    'penghasilan_wali'   => $this->cleanVal($r['gaji_wali'] ?? $r['penghasilan_wali'] ?? null),
                    'nomor_hp_wali'      => $this->cleanVal($r['hp_wali'] ?? $r['nomor_hp_wali'] ?? null),

                    // Keluar / Lulus
                    'tanggal_keluar'     => $this->normDate($r['tgl_keluar'] ?? $r['tanggal_keluar'] ?? null),
                    'kelas_keluar'       => $this->cleanVal($r['kelas_keluar'] ?? null),
                    'alasan_keluar'      => $this->cleanVal($r['alasan_keluar'] ?? null),
                    'sekolah_tujuan'     => $this->cleanVal($r['tujuan'] ?? $r['sekolah_tujuan'] ?? null),
                    'nomor_peserta_psaj' => $this->cleanVal($r['nopes'] ?? $r['nomor_peserta_psaj'] ?? null),
                    'nomor_skl'          => $this->cleanVal($r['no_skl'] ?? $r['nomor_skl'] ?? null),
                    'nomor_ijazah_sma'   => $this->cleanVal($r['no_ijazah_sma'] ?? $r['nomor_ijazah_sma'] ?? null),
                    'tanggal_ijazah_sma' => $this->normDate($r['tgl_ijazah_sma'] ?? $r['tanggal_ijazah_sma'] ?? null),
                    'nomor_transkrip'    => $this->cleanVal($r['no_transkrip'] ?? $r['nomor_transkrip'] ?? null),
                    'tahun_lulus'        => !empty($r['thn_lulus']) ? (string)$r['thn_lulus'] : (!empty($r['tahun_lulus']) ? (string)$r['tahun_lulus'] : null),
                    'kegiatan_setelah_lulus' => $this->cleanVal($r['lanjut'] ?? $r['kegiatan_setelah_lulus'] ?? null),
                    'catatan'            => $this->cleanVal($r['catatan'] ?? null),
                ];

                if ($siswa) {
                    $siswa->update($data);
                } else {
                    $siswa = SiswaBiodata::create($data);
                }
            }

            // Simpan / update riwayat kelas
            if ($siswa && $kelas) {
                $statusSiswa = 'Aktif';
                if (!empty($r['alasan_keluar']) && stripos($r['alasan_keluar'], 'Lulus') !== false) {
                    $statusSiswa = 'Lulus';
                } elseif (!empty($r['status']) && in_array($r['status'], ['Aktif', 'Pindah keluar', 'Keluar', 'Lulus'])) {
                    $statusSiswa = $r['status'];
                }

                SiswaRiwayatKelas::updateOrCreate(
                    [
                        'siswa_id'        => $siswa->id,
                        'tahun_ajaran_id' => $ta->id,
                    ],
                    [
                        'kelas_id'        => $kelas->id,
                        'status'          => $statusSiswa,
                    ]
                );
            }

            $saved++;
        }

        return [$saved, $skipped];
    }

    private function saveNilai($rows, $ta, $semester, $mode, $kelasInput)
    {
        $saved = 0;
        $skipped = 0;
        $mapelCache = SekolahMapel::all()->keyBy('kode');

        foreach ($rows as $r) {
            $nisn = $this->normN($r['nisn'] ?? '');
            $siswa = null;
            if ($nisn) {
                $siswa = SiswaBiodata::where('nisn', $nisn)->first();
            }
            if (!$siswa && !empty($r['nama'])) {
                $siswa = SiswaBiodata::where('nama_lengkap', $r['nama'])->first();
            }
            if (!$siswa) {
                $skipped++;
                continue;
            }

            $kStr = $mode === 'semua' ? ($r['kelas'] ?? '') : $kelasInput;
            $kStr = $this->normK($kStr);
            $kelas = $kStr ? $this->ensureKelas($kStr) : null;

            $riwayat = SiswaRiwayatKelas::firstOrCreate(
                [
                    'siswa_id'        => $siswa->id,
                    'tahun_ajaran_id' => $ta->id,
                ],
                [
                    'kelas_id'        => $kelas ? $kelas->id : ($this->ensureKelas('X-1')->id),
                    'status'          => 'Aktif',
                ]
            );

            $sem = !empty($r['sem']) ? (stripos($r['sem'], 'genap') !== false ? 'Genap' : 'Ganjil') : $semester;

            // Cari kolom-kolom nilai (contoh: mtk, ind, pnc, dsb)
            $rowSaved = false;
            foreach ($r as $key => $val) {
                $kLow = strtolower(trim($key));
                if (in_array($kLow, ['nisn', 'nis', 'nama', 'kelas', 'sem', 'mode', 'ta', 'id'])) continue;
                if ($val === '' || $val === null || !is_numeric($val)) continue;

                $numVal = (float)$val;
                if ($numVal < 0 || $numVal > 100) continue;

                $mapel = $mapelCache->get($kLow);
                if (!$mapel) {
                    $mapel = SekolahMapel::firstOrCreate(
                        ['kode' => substr($kLow, 0, 3)],
                        ['nama' => strtoupper($key)]
                    );
                    $mapelCache->put($mapel->kode, $mapel);
                }

                SiswaNilai::updateOrCreate(
                    [
                        'riwayat_kelas_id' => $riwayat->id,
                        'semester'         => $sem,
                        'mapel_id'         => $mapel->id,
                    ],
                    [
                        'nilai_angka'      => $numVal,
                    ]
                );
                $rowSaved = true;
            }

            if ($rowSaved) {
                $saved++;
            } else {
                $skipped++;
            }
        }

        return [$saved, $skipped];
    }

    private function saveKehadiran($rows, $ta, $semester, $mode, $kelasInput)
    {
        $saved = 0;
        $skipped = 0;

        foreach ($rows as $r) {
            $nisn = $this->normN($r['nisn'] ?? '');
            $siswa = null;
            if ($nisn) {
                $siswa = SiswaBiodata::where('nisn', $nisn)->first();
            }
            if (!$siswa && !empty($r['nama'])) {
                $siswa = SiswaBiodata::where('nama_lengkap', $r['nama'])->first();
            }
            if (!$siswa) {
                $skipped++;
                continue;
            }

            $kStr = $mode === 'semua' ? ($r['kelas'] ?? '') : $kelasInput;
            $kStr = $this->normK($kStr);
            $kelas = $kStr ? $this->ensureKelas($kStr) : null;

            $riwayat = SiswaRiwayatKelas::firstOrCreate(
                [
                    'siswa_id'        => $siswa->id,
                    'tahun_ajaran_id' => $ta->id,
                ],
                [
                    'kelas_id'        => $kelas ? $kelas->id : ($this->ensureKelas('X-1')->id),
                    'status'          => 'Aktif',
                ]
            );

            $sem = !empty($r['sem']) ? (stripos($r['sem'], 'genap') !== false ? 'Genap' : 'Ganjil') : $semester;

            SiswaKehadiran::updateOrCreate(
                [
                    'riwayat_kelas_id' => $riwayat->id,
                    'semester'         => $sem,
                ],
                [
                    'sakit'       => (int)($r['sakit'] ?? 0),
                    'izin'        => (int)($r['izin'] ?? 0),
                    'alpa'        => (int)($r['alpa'] ?? 0),
                    'kokurikuler' => $this->cleanVal($r['kokur'] ?? $r['kokurikuler'] ?? null),
                ]
            );

            $saved++;
        }

        return [$saved, $skipped];
    }

    private function saveEkskul($rows, $ta, $semester, $mode, $kelasInput)
    {
        $saved = 0;
        $skipped = 0;

        foreach ($rows as $r) {
            $nisn = $this->normN($r['nisn'] ?? '');
            $siswa = null;
            if ($nisn) {
                $siswa = SiswaBiodata::where('nisn', $nisn)->first();
            }
            if (!$siswa && !empty($r['nama'])) {
                $siswa = SiswaBiodata::where('nama_lengkap', $r['nama'])->first();
            }
            if (!$siswa) {
                $skipped++;
                continue;
            }

            $kStr = $mode === 'semua' ? ($r['kelas'] ?? '') : $kelasInput;
            $kStr = $this->normK($kStr);
            $kelas = $kStr ? $this->ensureKelas($kStr) : null;

            $riwayat = SiswaRiwayatKelas::firstOrCreate(
                [
                    'siswa_id'        => $siswa->id,
                    'tahun_ajaran_id' => $ta->id,
                ],
                [
                    'kelas_id'        => $kelas ? $kelas->id : ($this->ensureKelas('X-1')->id),
                    'status'          => 'Aktif',
                ]
            );

            $sem = !empty($r['sem']) ? (stripos($r['sem'], 'genap') !== false ? 'Genap' : 'Ganjil') : $semester;
            $namaKeg = $this->cleanVal($r['ekskul'] ?? $r['nama_kegiatan'] ?? 'Pramuka');
            $predikat = $this->cleanVal($r['predikat'] ?? 'A');

            if ($namaKeg) {
                SiswaEkskul::updateOrCreate(
                    [
                        'riwayat_kelas_id' => $riwayat->id,
                        'semester'         => $sem,
                        'nama_kegiatan'    => $namaKeg,
                    ],
                    [
                        'predikat'         => $predikat,
                    ]
                );
                $saved++;
            } else {
                $skipped++;
            }
        }

        return [$saved, $skipped];
    }

    private function saveGuru($rows)
    {
        $saved = 0;
        $skipped = 0;

        foreach ($rows as $r) {
            $nama = $this->cleanVal($r['nama'] ?? '');
            $nip  = $this->cleanVal($r['nip'] ?? null);
            if (!$nama && !$nip) {
                $skipped++;
                continue;
            }

            $guru = null;
            if ($nip) {
                $guru = GuruBiodata::where('nip', $nip)->first();
            }
            if (!$guru) {
                $guru = GuruBiodata::where('nama', $nama)->first();
            }

            $data = [
                'nama'                => $nama,
                'nip'                 => $nip,
                'nuptk'               => $this->cleanVal($r['nuptk'] ?? null),
                'nik'                 => $this->cleanVal($r['nik'] ?? null),
                'jenis_kelamin'       => in_array(strtoupper($r['jk'] ?? ''), ['P', 'PEREMPUAN']) ? 'P' : 'L',
                'tempat_lahir'        => $this->cleanVal($r['tmp'] ?? $r['tempat_lahir'] ?? null),
                'tanggal_lahir'       => $this->normDate($r['tgl'] ?? $r['tanggal_lahir'] ?? null),
                'pendidikan_terakhir' => $this->cleanVal($r['pendidikan'] ?? $r['pendidikan_terakhir'] ?? null),
                'status_kepegawaian'  => $this->cleanVal($r['status'] ?? $r['status_kepegawaian'] ?? 'PNS'),
                'mulai_bertugas'      => is_numeric($r['mulai_bertugas'] ?? null) ? (int)$r['mulai_bertugas'] : null,
                'nomor_hp'            => $this->cleanVal($r['hp'] ?? $r['nomor_hp'] ?? null),
                'email'               => $this->cleanVal($r['email'] ?? null),
                'alamat'              => $this->cleanVal($r['alamat'] ?? null),
            ];

            if ($guru) {
                $guru->update($data);
            } else {
                $guru = GuruBiodata::create($data);
            }

            // Buat akun login jika ada NIP
            if ($guru && $guru->nip) {
                Pengguna::firstOrCreate(
                    ['username' => $guru->nip],
                    [
                        'peran'                => 'guru',
                        'email'                => $guru->email,
                        'password'             => bcrypt('guru123'),
                        'wajib_ganti_password' => 1,
                        'aktif'                => 1,
                        'guru_id'              => $guru->id,
                    ]
                );
            }

            $saved++;
        }

        return [$saved, $skipped];
    }
}

