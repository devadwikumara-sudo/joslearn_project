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
use App\Models\Guru\GuruBiodata;
use App\Models\Auth\Pengguna;
use App\Models\Admin\CatatanAktivitas;

class ImportBatchController extends Controller
{
    /**
     * Endpoint untuk menerima data hasil parsing Excel fleksibel (dari SheetJS client-side).
     */
    public function saveBatch(Request $request)
    {
        $type       = $request->input('type', 'siswa'); // siswa, nilai, hadir, ekskul, guru
        $requestedTaName = trim((string) $request->input('tahun_ajaran', ''));
        $taName = $requestedTaName === '' ? '' : $this->normAcademicYear($requestedTaName);
        $semester   = $request->input('semester', 'Genap');
        $mode       = $request->input('mode', 'semua');
        $kelasInput = $request->input('kelas', '');
        $dup        = $request->input('dup', 'skip');
        $rows       = $request->input('rows', []);
        $newClasses = $request->input('new_classes', []);
        $newMapel   = $request->input('new_mapel', []);
        $usesSixSemesterFlow = in_array($type, ['nilai', 'hadir', 'ekskul'], true)
            && $this->isSixSemesterSelection((string) $semester);
        if ($usesSixSemesterFlow) {
            $taName = '';
        }

        // Pemeriksaan hak peran: Guru tidak boleh import biodata siswa atau guru
        $user = auth()->user();
        if ($user && $user->peran === 'guru' && in_array($type, ['siswa', 'guru'])) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak: Hanya admin yang diizinkan mengimpor data biodata siswa atau guru.'
            ], 403);
        }

        if (empty($rows) || !is_array($rows)) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada data baris yang diterima untuk diimpor.'
            ], 422);
        }

        if (! $usesSixSemesterFlow && $requestedTaName !== '' && $taName === null) {
            return response()->json([
                'success' => false,
                'message' => 'Format tahun ajaran tidak valid. Gunakan format seperti 2024/2025.',
            ], 422);
        }

        DB::beginTransaction();
        try {
            // 1. Tahun Ajaran
            if ($usesSixSemesterFlow) {
                $ta = null;
            } elseif ($taName !== '') {
                $ta = SekolahTahunAjaran::firstOrCreate(
                    ['nama' => $taName],
                    ['sedang_berjalan' => 0]
                );
            } else {
                $ta = SekolahTahunAjaran::where('sedang_berjalan', 1)->first()
                    ?? SekolahTahunAjaran::latest('id')->first()
                    ?? SekolahTahunAjaran::create(['nama' => '2024/2025', 'sedang_berjalan' => 1]);
            }

            // 2. Buat kelas baru untuk tipe impor selain biodata siswa.
            if ($type !== 'siswa' && ! $usesSixSemesterFlow) {
                $allClassNames = collect($rows)->pluck('kelas')->merge($newClasses)->unique()->filter()->values();
                foreach ($allClassNames as $cName) {
                    $cName = $this->normK($cName);
                    if ($cName) {
                        $this->ensureKelas($cName);
                    }
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
            $rowErrors = [];
            $unlinkedCount = 0;

            // 4. Proses berdasarkan jenis data
            if ($type === 'siswa') {
                list($savedCount, $skippedCount, $rowErrors, $unlinkedCount) = $this->saveSiswa($rows, $ta, $mode, $kelasInput);
            } elseif ($type === 'nilai') {
                list($savedCount, $skippedCount, $rowErrors) = $this->saveNilai($rows, $ta, $semester, $mode, $kelasInput);
            } elseif ($type === 'hadir') {
                list($savedCount, $skippedCount, $rowErrors) = $this->saveKehadiran($rows, $ta, $semester, $mode, $kelasInput);
            } elseif ($type === 'ekskul') {
                list($savedCount, $skippedCount, $rowErrors) = $this->saveEkskul($rows, $ta, $semester, $mode, $kelasInput);
            } elseif ($type === 'guru') {
                list($savedCount, $skippedCount) = $this->saveGuru($rows);
            }

            // 5. Catat Log Aktivitas
            CatatanAktivitas::create([
                'pengguna_id' => auth()->user()?->id ?? (is_numeric(auth()->id()) ? (int)auth()->id() : null),
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
                'message' => "Berhasil mengimpor {$savedCount} data {$type}" . ($skippedCount > 0 ? " ({$skippedCount} baris gagal/dilewati)" : "") . ".",
                'errors' => $rowErrors,
                'class_warning' => $type === 'siswa' && $unlinkedCount > 0
                    ? (!$ta
                        ? 'Biodata tersimpan, tetapi riwayat kelas tidak dibuat karena tahun ajaran yang dipilih belum terdaftar.'
                        : "{$unlinkedCount} biodata tersimpan tanpa riwayat kelas karena kelas tidak tersedia.")
                    : null,
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

    private function normAcademicYear(string $year): ?string
    {
        if (!preg_match('/^((?:19|20)\d{2})\s*[\/-]\s*((?:19|20)\d{2}|\d{2})$/', trim($year), $matches)) {
            return null;
        }

        $startYear = (int) $matches[1];
        $endYear = (int) $matches[2];
        if (strlen($matches[2]) === 2) {
            $endYear += intdiv($startYear, 100) * 100;
            if ($endYear <= $startYear) {
                $endYear += 100;
            }
        }

        if ($endYear !== $startYear + 1) {
            return null;
        }

        return $startYear . '/' . $endYear;
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

    private function getOrCreateRiwayatKelas(int $siswaId, int $taId, ?SekolahKelas $kelas): ?SiswaRiwayatKelas
    {
        $riwayat = SiswaRiwayatKelas::where('siswa_id', $siswaId)
            ->where('tahun_ajaran_id', $taId)
            ->first();

        if (!$riwayat) {
            if (!$kelas) {
                return null;
            }

            $riwayat = SiswaRiwayatKelas::create([
                'siswa_id'        => $siswaId,
                'tahun_ajaran_id' => $taId,
                'kelas_id'        => $kelas->id,
                'status'          => 'Aktif',
            ]);
        } elseif ($kelas && (int) $riwayat->kelas_id !== (int) $kelas->id) {
            return null;
        }

        return $riwayat;
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

    private function saveSiswa($rows, $ta, $mode, $kelasInput)
    {
        $saved = 0;
        $skipped = 0;
        $errors = [];
        $seenNisn = [];
        $seenNis = [];
        $unlinked = 0;

        foreach ($rows as $rowIndex => $r) {
            if (!is_array($r)) {
                $skipped++;
                $errors[] = ['row' => $rowIndex + 1, 'reason' => 'Format baris tidak valid.'];
                continue;
            }
            $rowNumber = max(1, (int) ($r['__excel_row'] ?? $rowIndex + 1));
            $rowTa = $ta;
            $rowTaName = null;
            $rawRowTaName = trim((string) ($r['tahun_ajaran'] ?? ''));
            if ($rawRowTaName !== '') {
                $rowTaName = $this->normAcademicYear($rawRowTaName);
                if ($rowTaName === null) {
                    $skipped++;
                    $errors[] = ['row' => $rowNumber, 'reason' => 'Format tahun ajaran tidak valid. Gunakan format seperti 2024/2025.'];
                    continue;
                }
            }
            $nisn = $this->normN($r['nisn'] ?? '');
            $nama = $this->cleanVal($r['nama'] ?? $r['nama_lengkap'] ?? '');
            $nis = $this->cleanVal($r['nis'] ?? $r['nomor_induk'] ?? null);
            if (!$nisn && !$nama && !$nis) {
                $skipped++;
                $errors[] = ['row' => $rowNumber, 'reason' => 'Baris kosong atau tidak memiliki identitas siswa.'];
                continue;
            }

            if (!$nama) {
                $skipped++;
                $errors[] = ['row' => $rowNumber, 'reason' => 'Nama lengkap wajib diisi.'];
                continue;
            }

            if (!preg_match('/^\d{10}$/', (string) $nisn)) {
                $skipped++;
                $errors[] = ['row' => $rowNumber, 'reason' => 'NISN wajib terdiri dari tepat 10 digit.'];
                continue;
            }

            $nik = $this->cleanVal($r['nik'] ?? null);
            if (!empty($r['__numeric_nik'])) {
                $skipped++;
                $errors[] = ['row' => $rowNumber, 'reason' => 'NIK tersimpan sebagai angka di Excel dan berisiko kehilangan digit. Ubah kolom NIK menjadi teks lalu impor ulang.'];
                continue;
            }
            if ($nik !== null && !preg_match('/^\d{16}$/', (string) $nik)) {
                $skipped++;
                $errors[] = ['row' => $rowNumber, 'reason' => 'NIK harus terdiri dari tepat 16 digit.'];
                continue;
            }

            if (isset($seenNisn[$nisn]) || ($nis !== null && isset($seenNis[$nis]))) {
                $skipped++;
                $errors[] = ['row' => $rowNumber, 'reason' => 'NISN atau NIS duplikat pada file yang diunggah.'];
                continue;
            }

            $siswaByNisn = SiswaBiodata::where('nisn', $nisn)->first();
            $siswaByNis = $nis !== null
                ? SiswaBiodata::where('nomor_induk', $nis)->first()
                : null;

            if (($siswaByNisn && $siswaByNis && $siswaByNisn->id !== $siswaByNis->id)
                || $siswaByNisn || $siswaByNis) {
                $skipped++;
                $errors[] = ['row' => $rowNumber, 'reason' => 'Siswa dengan NISN atau NIS tersebut sudah terdaftar; data lama tidak ditimpa.'];
                continue;
            }

            $jk = strtoupper(trim((string) ($r['jk'] ?? $r['jenis_kelamin'] ?? '')));
            if ($jk !== '' && !in_array($jk, ['L', 'LAKI-LAKI', 'P', 'PEREMPUAN'], true)) {
                $skipped++;
                $errors[] = ['row' => $rowNumber, 'reason' => 'Jenis kelamin harus L atau P.'];
                continue;
            }

            $kStr = $mode === 'semua' ? ($r['kelas'] ?? '') : $kelasInput;
            $kStr = $this->normK($kStr);
            if ($kStr !== null && mb_strlen($kStr) > 30) {
                $skipped++;
                $errors[] = ['row' => $rowNumber, 'reason' => 'Nama kelas melebihi batas 30 karakter.'];
                continue;
            }

            if ($rowTaName !== null && (!$rowTa || $rowTa->nama !== $rowTaName)) {
                $rowTa = SekolahTahunAjaran::firstOrCreate(
                    ['nama' => $rowTaName],
                    ['sedang_berjalan' => 0]
                );
            }

            $data = [
                    'nama_lengkap'       => $nama ?: 'Siswa ' . $nisn,
                    'nama_panggilan'     => $this->cleanVal($r['panggilan'] ?? null),
                    'nisn'               => $nisn,
                    'nomor_induk'        => $this->cleanVal($r['nis'] ?? $r['nomor_induk'] ?? null),
                    'jenis_kelamin'      => in_array($jk, ['P', 'PEREMPUAN'], true) ? 'P' : (in_array($jk, ['L', 'LAKI-LAKI'], true) ? 'L' : null),
                    'tempat_lahir'       => $this->cleanVal($r['tmp'] ?? $r['tempat_lahir'] ?? null),
                    'tanggal_lahir'      => $this->normDate($r['tgl'] ?? $r['tanggal_lahir'] ?? null),
                    'agama'              => $this->cleanVal($r['agama'] ?? null),
                    'kewarganegaraan'    => $this->cleanVal($r['kewarganegaraan'] ?? null),
                    'nik'                => $nik,
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
                    'lama_belajar_tahun' => is_numeric($r['lama'] ?? null) ? (int)$r['lama'] : null,
                    'jenis_registrasi'   => $this->cleanVal($r['masuk'] ?? $r['jenis_registrasi'] ?? null),
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
                    'wali_alamat_jalan'  => $this->cleanVal($r['wali_jalan'] ?? $r['wali_alamat_jalan'] ?? null),
                    'wali_alamat_rt_rw'  => $this->cleanVal($r['wali_rt'] ?? $r['wali_alamat_rt_rw'] ?? null),
                    'wali_alamat_desa'   => $this->cleanVal($r['wali_desa'] ?? $r['wali_alamat_desa'] ?? null),
                    'wali_alamat_kecamatan' => $this->cleanVal($r['wali_kec'] ?? $r['wali_alamat_kecamatan'] ?? null),
                    'wali_alamat_kabupaten' => $this->cleanVal($r['wali_kab'] ?? $r['wali_alamat_kabupaten'] ?? null),
                    'wali_alamat_provinsi'  => $this->cleanVal($r['wali_prov'] ?? $r['wali_alamat_provinsi'] ?? null),
                    'wali_alamat_kode_pos'  => $this->cleanVal($r['wali_pos'] ?? $r['wali_alamat_kode_pos'] ?? null),

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

            $statusSiswa = $this->importedStudentStatus($r, $data);
            if ($statusSiswa === 'Lulus') {
                $data['alasan_keluar'] = $data['alasan_keluar'] ?: 'Lulus';
                if ($data['tahun_lulus'] === null && $data['tanggal_keluar'] !== null) {
                    $data['tahun_lulus'] = substr($data['tanggal_keluar'], 0, 4);
                }
            }

            $validationError = $this->validateImportedStudent($data, $r);
            if ($validationError !== null) {
                $skipped++;
                $errors[] = ['row' => $rowNumber, 'reason' => $validationError];
                continue;
            }

            try {
                DB::transaction(function () use ($data, $kStr, $rowTa, $statusSiswa): void {
                    $siswa = SiswaBiodata::create($data);
                    $kelas = $rowTa && $kStr ? $this->ensureKelas($kStr) : null;

                    if ($kelas && $rowTa) {
                        SiswaRiwayatKelas::updateOrCreate(
                            [
                                'siswa_id'        => $siswa->id,
                                'tahun_ajaran_id' => $rowTa->id,
                            ],
                            [
                                'kelas_id'        => $kelas->id,
                                'status'          => $statusSiswa,
                            ]
                        );
                    }
                });
            } catch (\Illuminate\Database\QueryException $exception) {
                $skipped++;
                $errors[] = ['row' => $rowNumber, 'reason' => 'Database menolak data baris ini karena duplikasi atau batasan kolom/relasi.'];
                Log::warning('Student import row rejected by database.', [
                    'row' => $rowNumber,
                    'nisn' => $nisn,
                    'exception' => $exception->getMessage(),
                ]);
                continue;
            }

            $seenNisn[$nisn] = true;
            if ($nis !== null) {
                $seenNis[$nis] = true;
            }
            if (!$rowTa || !$kStr) {
                $unlinked++;
            }
            $saved++;
        }

        return [$saved, $skipped, $errors, $unlinked];
    }

    private function validateImportedStudent(array $data, array $row): ?string
    {
        $maxLengths = [
            'nama_lengkap' => 150, 'nama_panggilan' => 64, 'nomor_induk' => 64,
            'tempat_lahir' => 100, 'agama' => 64, 'kewarganegaraan' => 64,
            'jumlah_saudara' => 64, 'status_yatim' => 64, 'bahasa_sehari_hari' => 64,
            'alamat_jalan' => 255, 'alamat_rt_rw' => 64, 'alamat_desa' => 100,
            'alamat_kecamatan' => 100, 'alamat_kabupaten' => 100, 'alamat_provinsi' => 100,
            'alamat_kode_pos' => 10, 'nomor_hp' => 64, 'tempat_tinggal' => 64,
            'golongan_darah' => 5, 'penyakit' => 255, 'kelainan_fisik' => 255,
            'sekolah_asal' => 150, 'tanggal_ijazah_smp' => 64, 'nomor_ijazah_smp' => 64,
            'jenis_registrasi' => 64, 'pindahan_dari_sekolah' => 150, 'alasan_pindah' => 255,
            'kelas_diterima' => 10, 'rombel_diterima' => 10,
            'nama_ayah' => 150, 'agama_ayah' => 64, 'kewarganegaraan_ayah' => 64,
            'pendidikan_ayah' => 64, 'pekerjaan_ayah' => 100, 'penghasilan_ayah' => 64,
            'nomor_hp_ayah' => 64, 'keadaan_ayah' => 64,
            'nama_ibu' => 150, 'agama_ibu' => 64, 'kewarganegaraan_ibu' => 64,
            'pendidikan_ibu' => 64, 'pekerjaan_ibu' => 100, 'penghasilan_ibu' => 64,
            'nomor_hp_ibu' => 64, 'keadaan_ibu' => 64,
            'ortu_alamat_jalan' => 255, 'ortu_alamat_rt_rw' => 64, 'ortu_alamat_desa' => 100,
            'ortu_alamat_kecamatan' => 100, 'ortu_alamat_kabupaten' => 100,
            'ortu_alamat_provinsi' => 100, 'ortu_alamat_kode_pos' => 10,
            'nama_wali' => 150, 'agama_wali' => 64, 'kewarganegaraan_wali' => 64,
            'pendidikan_wali' => 64, 'pekerjaan_wali' => 100, 'penghasilan_wali' => 64,
            'nomor_hp_wali' => 64, 'wali_alamat_jalan' => 255, 'wali_alamat_rt_rw' => 64,
            'wali_alamat_desa' => 100, 'wali_alamat_kecamatan' => 100,
            'wali_alamat_kabupaten' => 100, 'wali_alamat_provinsi' => 100,
            'wali_alamat_kode_pos' => 10, 'kelas_keluar' => 64, 'alasan_keluar' => 255,
            'sekolah_tujuan' => 150, 'nomor_peserta_psaj' => 64, 'nomor_skl' => 64,
            'nomor_ijazah_sma' => 64, 'nomor_transkrip' => 64,
            'kegiatan_setelah_lulus' => 255,
        ];

        foreach ($maxLengths as $field => $maxLength) {
            if ($data[$field] !== null && mb_strlen((string) $data[$field]) > $maxLength) {
                return "Kolom {$field} melebihi batas {$maxLength} karakter.";
            }
        }

        foreach ([
            'tanggal_lahir' => ['tgl', 'tanggal_lahir'],
            'tanggal_diterima' => ['tgl_masuk', 'tanggal_diterima'],
            'tanggal_keluar' => ['tgl_keluar', 'tanggal_keluar'],
            'tanggal_ijazah_sma' => ['tgl_ijazah_sma', 'tanggal_ijazah_sma'],
        ] as $field => $sourceFields) {
            $sourceValue = null;
            foreach ($sourceFields as $sourceField) {
                if (!empty($row[$sourceField])) {
                    $sourceValue = $row[$sourceField];
                    break;
                }
            }
            if ($sourceValue !== null && $sourceValue !== '' && $sourceValue !== '-' && $data[$field] === null) {
                return "Format tanggal pada kolom {$field} tidak valid.";
            }
        }

        $numericRules = [
            'anak_ke' => ['anak_ke', 255, true],
            'jarak_ke_sekolah_km' => ['jarak', 9999.99, false],
            'tinggi_badan_cm' => ['tinggi', 65535, true],
            'berat_badan_kg' => ['berat', 65535, true],
            'lama_belajar_tahun' => ['lama', 255, true],
        ];
        foreach ($numericRules as $field => [$sourceField, $maximum, $integerOnly]) {
            $value = $row[$sourceField] ?? null;
            if ($value === null || $value === '' || $value === '-') {
                continue;
            }
            if (!is_numeric($value) || (float) $value < 0 || (float) $value > $maximum
                || ($integerOnly && (float) $value !== (float) (int) $value)) {
                return "Nilai pada kolom {$sourceField} tidak valid.";
            }
        }

        $tahunLulus = $data['tahun_lulus'];
        if ($tahunLulus !== null && !preg_match('/^\d{4}$/', $tahunLulus)) {
            return 'Tahun lulus harus terdiri dari 4 digit.';
        }

        return null;
    }

    private function importedStudentStatus(array $row, array $data): string
    {
        $reason = mb_strtolower(trim((string) ($data['alasan_keluar'] ?? '')));
        $status = mb_strtolower(trim((string) ($row['status'] ?? '')));
        $isGraduated = (
            preg_match('/\blulus\b/u', $reason) === 1
            && preg_match('/\b(?:belum|tidak)\s+lulus\b/u', $reason) !== 1
        ) || (
            preg_match('/\blulus\b/u', $status) === 1
            && preg_match('/\b(?:belum|tidak)\s+lulus\b/u', $status) !== 1
        ) || $data['tahun_lulus'] !== null;

        if ($isGraduated) {
            return 'Lulus';
        }

        return [
            'aktif' => 'Aktif',
            'pindah keluar' => 'Pindah keluar',
            'keluar' => 'Keluar',
        ][$status] ?? 'Aktif';
    }

    /**
     * Resolve only when every supplied identifier agrees on one student.
     *
     * @return array{student: ?SiswaBiodata, error: ?string}
     */
    private function findStudent(array $row, ?int $taId, ?SekolahKelas $kelas, ?string $grade = null): array
    {
        $nisnInput = trim((string) ($row['nisn'] ?? ''));
        $nisn = $nisnInput === '' ? null : $this->normN($nisnInput);
        $nis = $this->cleanVal($row['nis'] ?? $row['nomor_induk'] ?? null);
        $rawName = $this->cleanVal($row['nama'] ?? $row['nama_lengkap'] ?? null);

        if ($nisn !== null && ! preg_match('/^\d{10}$/', $nisn)) {
            return ['student' => null, 'error' => 'NISN tidak valid; pencocokan dibatalkan agar data tidak masuk ke siswa yang salah.'];
        }

        $identifiedStudents = collect();
        foreach (array_filter([
            'NISN' => $nisn,
            'NIS' => $nis,
        ], fn ($identifier): bool => $identifier !== null && $identifier !== '') as $label => $identifier) {
            $column = $label === 'NISN' ? 'nisn' : 'nomor_induk';
            $matches = SiswaBiodata::where($column, $identifier)->get();

            if ($matches->count() > 1) {
                return ['student' => null, 'error' => "{$label} terdaftar pada lebih dari satu siswa; baris ini perlu diperiksa."];
            }
            if ($matches->isEmpty()) {
                return ['student' => null, 'error' => "{$label} tidak ditemukan; baris ini dilewati dan tidak dicocokkan dengan identitas lain."];
            }

            $identifiedStudents->push($matches->first());
        }

        if ($identifiedStudents->isNotEmpty()) {
            $studentIds = $identifiedStudents->pluck('id')->unique();
            if ($studentIds->count() !== 1) {
                return ['student' => null, 'error' => 'NISN dan NIS mengarah ke siswa berbeda; baris ini dilewati.'];
            }

            $student = $identifiedStudents->first();
            if ($rawName !== null && ! $this->sameStudentName($student->nama_lengkap, (string) $rawName)) {
                return ['student' => null, 'error' => 'Nama tidak sesuai dengan NISN/NIS; periksa kembali identitas pada file.'];
            }

            return $this->validateStudentClass($student, $taId, $kelas);
        }

        if ($rawName === null) {
            return ['student' => null, 'error' => 'NISN, NIS, dan nama kosong; siswa tidak dapat diidentifikasi.'];
        }

        $name = preg_replace('/\s+/', ' ', trim((string) $rawName));
        $nameQuery = SiswaBiodata::whereRaw('LOWER(TRIM(nama_lengkap)) = ?', [mb_strtolower($name)]);
        $allNameMatches = (clone $nameQuery)->get();
        $contextMatches = (clone $nameQuery)->whereHas('riwayatKelas', function ($query) use ($taId, $kelas, $grade): void {
            if ($taId !== null) {
                $query->where('tahun_ajaran_id', $taId);
            }
            if ($kelas !== null) {
                $query->where('kelas_id', $kelas->id);
            }
            if ($grade !== null) {
                $query->whereHas('kelas', function ($classQuery) use ($grade): void {
                    $classQuery->where('tingkat', $grade);
                });
            }
        })->get();

        if ($contextMatches->count() === 1) {
            return ['student' => $contextMatches->first(), 'error' => null];
        }
        if ($contextMatches->count() > 1) {
            $contextLabel = $taId !== null
                ? ($kelas !== null ? 'kelas dan tahun ajaran ini' : 'tahun ajaran ini')
                : 'kelas/tingkat yang dipilih';

            return ['student' => null, 'error' => "Nama yang sama ditemukan lebih dari sekali pada {$contextLabel}; tambahkan NISN atau NIS."];
        }

        if ($allNameMatches->count() === 1) {
            return $this->validateStudentClass($allNameMatches->first(), $taId, $kelas);
        }
        if ($allNameMatches->count() > 1) {
            return ['student' => null, 'error' => 'Nama tidak unik; tambahkan NISN atau NIS agar siswa dapat dicocokkan dengan tepat.'];
        }

        return ['student' => null, 'error' => 'Siswa dengan identitas tersebut tidak ditemukan.'];
    }

    /**
     * @return array{student: ?SiswaBiodata, error: ?string}
     */
    private function validateStudentClass(SiswaBiodata $student, ?int $taId, ?SekolahKelas $kelas): array
    {
        if ($taId !== null && $kelas !== null) {
            $history = SiswaRiwayatKelas::where('siswa_id', $student->id)
                ->where('tahun_ajaran_id', $taId)
                ->first();

            if ($history !== null && (int) $history->kelas_id !== (int) $kelas->id) {
                return ['student' => null, 'error' => 'Kelas pada file berbeda dari riwayat siswa untuk tahun ajaran ini; data tidak dipindahkan otomatis.'];
            }
        }

        return ['student' => $student, 'error' => null];
    }

    private function sameStudentName(?string $storedName, string $importedName): bool
    {
        $normalize = static fn (?string $name): string => mb_strtolower(preg_replace('/\s+/', ' ', trim((string) $name)));

        return $normalize($storedName) === $normalize($importedName);
    }

    /**
     * @return array{student: ?SiswaBiodata, history: ?SiswaRiwayatKelas, error: ?array{row: int, reason: string}}
     */
    private function resolveImportTarget(array $row, int $rowIndex, ?int $taId, string $mode, string $kelasInput, string $semester): array
    {
        $rowNumber = max(1, (int) ($row['__excel_row'] ?? $rowIndex + 1));
        $rowClassName = $this->normK($row['kelas'] ?? '');
        $selectedClassName = $this->normK($kelasInput);
        if ($mode === 'kelas' && $rowClassName !== null && $selectedClassName !== null && $rowClassName !== $selectedClassName) {
            return [
                'student' => null,
                'history' => null,
                'error' => ['row' => $rowNumber, 'reason' => 'Kelas pada baris berbeda dari kelas yang dipilih; baris ini dilewati.'],
            ];
        }

        $className = $mode === 'semua' ? $rowClassName : $selectedClassName;
        $kelas = $className === null ? null : SekolahKelas::where('nama', $className)->first();

        if ($className !== null && $kelas === null) {
            return [
                'student' => null,
                'history' => null,
                'error' => ['row' => $rowNumber, 'reason' => "Kelas {$className} tidak ditemukan pada data kelas sekolah."],
            ];
        }

        if ($this->isSixSemesterSelection($semester)) {
            if ($kelas === null) {
                return [
                    'student' => null,
                    'history' => null,
                    'error' => ['row' => $rowNumber, 'reason' => 'Kelas tidak tersedia pada pilihan atau baris Excel; riwayat semester tidak dapat ditentukan.'],
                ];
            }

            $semesterInfo = $this->semesterInfoForRow($row, $semester, $className);
            if ($semesterInfo['error'] !== null) {
                return [
                    'student' => null,
                    'history' => null,
                    'error' => ['row' => $rowNumber, 'reason' => $semesterInfo['error']],
                ];
            }

            $match = $this->findStudent($row, $taId, $kelas, $semesterInfo['grade']);
            if ($match['student'] === null) {
                return [
                    'student' => null,
                    'history' => null,
                    'error' => ['row' => $rowNumber, 'reason' => $match['error'] ?? 'Siswa tidak dapat diidentifikasi.'],
                ];
            }

            $histories = SiswaRiwayatKelas::where('siswa_id', $match['student']->id)
                ->where('kelas_id', $kelas->id)
                ->when($taId !== null, fn ($query) => $query->where('tahun_ajaran_id', $taId))
                ->get();

            if ($histories->count() !== 1) {
                $reason = $histories->isEmpty()
                    ? "Riwayat kelas {$className} untuk semester {$semesterInfo['number']} tidak ditemukan; baris dilewati agar tahun ajaran tidak ditebak."
                    : "Riwayat kelas {$className} untuk semester {$semesterInfo['number']} ditemukan lebih dari satu; sertakan tahun ajaran pada baris Excel agar tidak salah memilih.";

                return [
                    'student' => null,
                    'history' => null,
                    'error' => ['row' => $rowNumber, 'reason' => $reason],
                ];
            }

            return [
                'student' => $match['student'],
                'history' => $histories->first(),
                'semester' => $semesterInfo['term'],
                'error' => null,
            ];
        }

        if ($taId === null) {
            return [
                'student' => null,
                'history' => null,
                'error' => ['row' => $rowNumber, 'reason' => 'Tahun ajaran tidak tersedia untuk format impor lama.'],
            ];
        }

        $match = $this->findStudent($row, $taId, $kelas);
        if ($match['student'] === null) {
            return [
                'student' => null,
                'history' => null,
                'error' => ['row' => $rowNumber, 'reason' => $match['error'] ?? 'Siswa tidak dapat diidentifikasi.'],
            ];
        }

        $history = $this->getOrCreateRiwayatKelas($match['student']->id, $taId, $kelas);
        if ($history === null) {
            return [
                'student' => null,
                'history' => null,
                'error' => ['row' => $rowNumber, 'reason' => 'Riwayat kelas untuk tahun ajaran ini belum ada dan kelas pada file tidak tersedia. Tambahkan kelas agar data tidak masuk ke riwayat yang salah.'],
            ];
        }

        return [
            'student' => $match['student'],
            'history' => $history,
            'semester' => ! empty($row['sem'])
                ? (stripos((string) $row['sem'], 'genap') !== false ? 'Genap' : 'Ganjil')
                : $semester,
            'error' => null,
        ];
    }

    private function isSixSemesterSelection(string $semester): bool
    {
        return $semester === 'auto'
            || in_array($semester, ['1', '2', '3', '4', '5', '6', 'local-ganjil', 'local-genap'], true);
    }

    /**
     * @return array{number: ?int, grade: ?string, term: ?string, error: ?string}
     */
    private function semesterInfoForRow(array $row, string $selection, string $className): array
    {
        preg_match('/^(XII|XI|X)(?:-|$)/i', $className, $gradeMatch);
        $grade = isset($gradeMatch[1]) ? strtoupper($gradeMatch[1]) : null;
        $gradeSemesterStart = ['X' => 1, 'XI' => 3, 'XII' => 5];
        $gradeStart = $grade !== null ? ($gradeSemesterStart[$grade] ?? null) : null;

        if ($gradeStart === null) {
            return ['number' => null, 'grade' => null, 'term' => null, 'error' => 'Tingkat kelas harus X, XI, atau XII agar dapat dipetakan ke enam semester.'];
        }

        $rawSemester = trim((string) ($row['sem'] ?? ''));
        $rowNumber = preg_match('/^(?:semester|smt|sem)?\s*([1-6])$/i', $rawSemester, $numberMatch)
            ? (int) $numberMatch[1]
            : null;
        $rowTerm = preg_match('/ganjil/i', $rawSemester)
            ? 'Ganjil'
            : (preg_match('/genap/i', $rawSemester) ? 'Genap' : null);
        $selectedNumber = ctype_digit($selection) ? (int) $selection : null;
        $selectedTerm = match ($selection) {
            'local-ganjil' => 'Ganjil',
            'local-genap' => 'Genap',
            default => null,
        };
        $rowGlobalNumber = $rowNumber === null
            ? null
            : ($rowNumber <= 2 ? $gradeStart + $rowNumber - 1 : $rowNumber);
        $selectedGlobalNumber = $selectedNumber === null
            ? null
            : ($selectedNumber <= 2 ? $gradeStart + $selectedNumber - 1 : $selectedNumber);

        if ($rowGlobalNumber !== null && $selectedGlobalNumber !== null && $rowGlobalNumber !== $selectedGlobalNumber) {
            return ['number' => null, 'grade' => $grade, 'term' => null, 'error' => "Semester pada baris Excel ({$rowNumber}) berbeda dari semester yang dipilih ({$selectedNumber}) untuk kelas {$grade}."];
        }

        if ($rowTerm !== null && $selectedTerm !== null && $rowTerm !== $selectedTerm) {
            return ['number' => null, 'grade' => $grade, 'term' => null, 'error' => "Jenis semester pada baris Excel ({$rowTerm}) berbeda dari jenis semester yang dipilih ({$selectedTerm})."];
        }

        $number = $selectedGlobalNumber ?? $rowGlobalNumber;
        $term = $selectedTerm ?? $rowTerm;
        if ($number === null && $term !== null) {
            $number = $gradeStart + ($term === 'Genap' ? 1 : 0);
        }
        if ($number === null) {
            return ['number' => null, 'grade' => $grade, 'term' => null, 'error' => 'Semester tidak ditentukan. Pilih semester 1–6 atau isi kolom sem pada file Excel.'];
        }

        $numberTerm = $number % 2 === 1 ? 'Ganjil' : 'Genap';
        if ($rowTerm !== null && $rowTerm !== $numberTerm) {
            return ['number' => $number, 'grade' => $grade, 'term' => null, 'error' => "Jenis semester pada baris Excel ({$rowTerm}) tidak sesuai dengan nomor semester {$number} ({$numberTerm})."];
        }
        if ($selectedTerm !== null && $selectedTerm !== $numberTerm) {
            return ['number' => $number, 'grade' => $grade, 'term' => null, 'error' => "Semester yang dipilih ({$selectedTerm}) tidak sesuai dengan nomor semester pada baris ({$numberTerm})."];
        }

        $numberGrade = match (true) {
            $number <= 2 => 'X',
            $number <= 4 => 'XI',
            default => 'XII',
        };
        $term = $numberTerm;

        if ($numberGrade !== $grade) {
            return ['number' => $number, 'grade' => $grade, 'term' => $term, 'error' => "Kelas {$className} tidak sesuai dengan semester {$number} yang dipilih (semester {$number} harus kelas {$numberGrade})."];
        }
        return ['number' => $number, 'grade' => $grade, 'term' => $term, 'error' => null];
    }

    /**
     * @return array{year: ?SekolahTahunAjaran, error: ?array{row: int, reason: string}}
     */
    private function importYearForRow(array $row, int $rowIndex, ?SekolahTahunAjaran $defaultYear, bool $createMissing = true): array
    {
        $rawYear = trim((string) ($row['tahun_ajaran'] ?? ''));
        if ($rawYear === '') {
            return ['year' => $defaultYear, 'error' => null];
        }

        $yearName = $this->normAcademicYear($rawYear);
        if ($yearName === null) {
            return [
                'year' => null,
                'error' => [
                    'row' => max(1, (int) ($row['__excel_row'] ?? $rowIndex + 1)),
                    'reason' => 'Format tahun ajaran pada baris tidak valid. Gunakan format seperti 2025/2026.',
                ],
            ];
        }

        $year = $createMissing
            ? SekolahTahunAjaran::firstOrCreate(['nama' => $yearName], ['sedang_berjalan' => false])
            : SekolahTahunAjaran::where('nama', $yearName)->first();

        if ($year === null) {
            return [
                'year' => null,
                'error' => [
                    'row' => max(1, (int) ($row['__excel_row'] ?? $rowIndex + 1)),
                    'reason' => "Tahun ajaran {$yearName} pada baris belum terdaftar; buat riwayat tahun ajaran terlebih dahulu.",
                ],
            ];
        }

        return ['year' => $year, 'error' => null];
    }

    private function saveNilai($rows, $ta, $semester, $mode, $kelasInput)
    {
        $saved = 0;
        $skipped = 0;
        $errors = [];
        $mapelCache = SekolahMapel::all()->keyBy('kode');

        foreach ($rows as $rowIndex => $r) {
            if (! is_array($r)) {
                $skipped++;
                $errors[] = ['row' => $rowIndex + 1, 'reason' => 'Format baris tidak valid.'];
                continue;
            }

            $usesSixSemesterFlow = $this->isSixSemesterSelection((string) $semester);
            $rowYear = $this->importYearForRow($r, (int) $rowIndex, $ta, ! $usesSixSemesterFlow);
            if ($rowYear['error'] !== null) {
                $skipped++;
                $errors[] = $rowYear['error'];
                continue;
            }

            $target = $this->resolveImportTarget($r, (int) $rowIndex, $rowYear['year']?->id, $mode, $kelasInput, (string) $semester);
            if ($target['error'] !== null) {
                $skipped++;
                $errors[] = $target['error'];
                continue;
            }

            $riwayat = $target['history'];

            $sem = $target['semester'];

            // Cari kolom-kolom nilai (contoh: mtk, ind, pnc, dsb)
            $rowSaved = false;
            foreach ($r as $key => $val) {
                $kLow = strtolower(trim($key));
                if (in_array($kLow, ['nisn', 'nis', 'nomor_induk', 'nama', 'nama_lengkap', 'kelas', 'sem', 'semester', 'mode', 'ta', 'tahun_ajaran', 'id', '__excel_row'])) continue;
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
                $errors[] = [
                    'row' => max(1, (int) ($r['__excel_row'] ?? $rowIndex + 1)),
                    'reason' => 'Tidak ada nilai mapel yang valid untuk disimpan.',
                ];
            }
        }

        return [$saved, $skipped, $errors];
    }

    private function saveKehadiran($rows, $ta, $semester, $mode, $kelasInput)
    {
        $saved = 0;
        $skipped = 0;
        $errors = [];

        foreach ($rows as $rowIndex => $r) {
            if (! is_array($r)) {
                $skipped++;
                $errors[] = ['row' => $rowIndex + 1, 'reason' => 'Format baris tidak valid.'];
                continue;
            }

            $usesSixSemesterFlow = $this->isSixSemesterSelection((string) $semester);
            $rowYear = $this->importYearForRow($r, (int) $rowIndex, $ta, ! $usesSixSemesterFlow);
            if ($rowYear['error'] !== null) {
                $skipped++;
                $errors[] = $rowYear['error'];
                continue;
            }

            $target = $this->resolveImportTarget($r, (int) $rowIndex, $rowYear['year']?->id, $mode, $kelasInput, (string) $semester);
            if ($target['error'] !== null) {
                $skipped++;
                $errors[] = $target['error'];
                continue;
            }

            $riwayat = $target['history'];

            $sem = $target['semester'];

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

        return [$saved, $skipped, $errors];
    }

    private function saveEkskul($rows, $ta, $semester, $mode, $kelasInput)
    {
        $saved = 0;
        $skipped = 0;
        $errors = [];

        foreach ($rows as $rowIndex => $r) {
            if (! is_array($r)) {
                $skipped++;
                $errors[] = ['row' => $rowIndex + 1, 'reason' => 'Format baris tidak valid.'];
                continue;
            }

            $usesSixSemesterFlow = $this->isSixSemesterSelection((string) $semester);
            $rowYear = $this->importYearForRow($r, (int) $rowIndex, $ta, ! $usesSixSemesterFlow);
            if ($rowYear['error'] !== null) {
                $skipped++;
                $errors[] = $rowYear['error'];
                continue;
            }

            $target = $this->resolveImportTarget($r, (int) $rowIndex, $rowYear['year']?->id, $mode, $kelasInput, (string) $semester);
            if ($target['error'] !== null) {
                $skipped++;
                $errors[] = $target['error'];
                continue;
            }

            $riwayat = $target['history'];

            $sem = $target['semester'];
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
                $errors[] = [
                    'row' => max(1, (int) ($r['__excel_row'] ?? $rowIndex + 1)),
                    'reason' => 'Nama kegiatan ekstrakurikuler kosong.',
                ];
            }
        }

        return [$saved, $skipped, $errors];
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
