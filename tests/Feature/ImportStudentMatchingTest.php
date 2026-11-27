<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\AlumniController;
use App\Http\Controllers\Admin\DataSiswaController;
use App\Models\Auth\Pengguna;
use App\Models\Guru\GuruBiodata;
use App\Models\SiswaBiodata;
use App\Services\ImportDictionaryService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\TestResponse;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tests\TestCase;

#[RunTestsInSeparateProcesses]
class ImportStudentMatchingTest extends TestCase
{
    private Pengguna $admin;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:'.base64_encode(str_repeat('j', 32))]);

        foreach ([
            'import_baris',
            'import_unggahan',
            'import_format_tersimpan',
            'import_kamus_kolom',
            'import_kamus_mapel',
            'guru_mengajar',
            'guru_wali_kelas',
            'akun_pengguna',
            'guru_biodata',
            'siswa_nilai',
            'siswa_kehadiran',
            'siswa_ekskul',
            'siswa_riwayat_kelas',
            'siswa_berkas',
            'siswa_pelacakan_alumni',
            'catatan_aktivitas',
            'sekolah_pengaturan',
            'sekolah_identitas',
            'sekolah_mapel',
            'sekolah_mapel_per_tingkat',
            'sekolah_kelas',
            'sekolah_tahun_ajaran',
            'siswa_biodata',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('siswa_biodata', function (Blueprint $table): void {
            $table->id();
            $table->string('nama_lengkap')->nullable();
            $table->string('nisn')->nullable();
            $table->string('nomor_induk')->nullable();
            $table->softDeletes();
            $table->timestamps();
            foreach ([
                'nama_panggilan', 'jenis_kelamin', 'tempat_lahir', 'tanggal_lahir', 'agama',
                'kewarganegaraan', 'nik', 'anak_ke', 'jumlah_saudara', 'status_yatim',
                'bahasa_sehari_hari', 'alamat_jalan', 'alamat_rt_rw', 'alamat_desa',
                'alamat_kecamatan', 'alamat_kabupaten', 'alamat_provinsi', 'alamat_kode_pos',
                'nomor_hp', 'tempat_tinggal', 'jarak_ke_sekolah_km', 'golongan_darah',
                'penyakit', 'kelainan_fisik', 'tinggi_badan_cm', 'berat_badan_kg',
                'sekolah_asal', 'tanggal_ijazah_smp', 'nomor_ijazah_smp', 'lama_belajar_tahun',
                'jenis_registrasi', 'pindahan_dari_sekolah', 'alasan_pindah', 'kelas_diterima',
                'rombel_diterima', 'tanggal_diterima', 'nama_ayah', 'agama_ayah',
                'kewarganegaraan_ayah', 'pendidikan_ayah', 'pekerjaan_ayah', 'penghasilan_ayah',
                'nomor_hp_ayah', 'keadaan_ayah', 'nama_ibu', 'agama_ibu', 'kewarganegaraan_ibu',
                'pendidikan_ibu', 'pekerjaan_ibu', 'penghasilan_ibu', 'nomor_hp_ibu', 'keadaan_ibu',
                'ortu_alamat_jalan', 'ortu_alamat_rt_rw', 'ortu_alamat_desa', 'ortu_alamat_kecamatan',
                'ortu_alamat_kabupaten', 'ortu_alamat_provinsi', 'ortu_alamat_kode_pos', 'nama_wali',
                'agama_wali', 'kewarganegaraan_wali', 'pendidikan_wali', 'pekerjaan_wali',
                'penghasilan_wali', 'nomor_hp_wali', 'wali_alamat_jalan', 'wali_alamat_rt_rw',
                'wali_alamat_desa', 'wali_alamat_kecamatan', 'wali_alamat_kabupaten',
                'wali_alamat_provinsi', 'wali_alamat_kode_pos', 'tanggal_keluar', 'kelas_keluar',
                'alasan_keluar', 'sekolah_tujuan', 'nomor_peserta_psaj', 'nomor_skl',
                'nomor_ijazah_sma', 'tanggal_ijazah_sma', 'nomor_transkrip', 'tahun_lulus',
                'kegiatan_setelah_lulus', 'catatan',
            ] as $column) {
                $table->text($column)->nullable();
            }
            $table->boolean('kk_ada')->default(false);
            $table->boolean('akta_ada')->default(false);
            $table->boolean('ijazah_smp_ada')->default(false);
            $table->boolean('surat_pindah_ada')->default(false);
        });

        Schema::create('sekolah_tahun_ajaran', function (Blueprint $table): void {
            $table->id();
            $table->string('nama')->unique();
            $table->boolean('sedang_berjalan')->default(false);
            $table->timestamps();
        });
        Schema::create('sekolah_pengaturan', function (Blueprint $table): void {
            $table->id();
            $table->string('kunci')->unique();
            $table->string('isi')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
        Schema::create('sekolah_identitas', function (Blueprint $table): void {
            $table->id();
        });

        Schema::create('sekolah_kelas', function (Blueprint $table): void {
            $table->id();
            $table->string('nama')->unique();
            $table->string('tingkat')->nullable();
            $table->timestamps();
        });

        Schema::create('sekolah_mapel', function (Blueprint $table): void {
            $table->id();
            $table->string('kode')->unique();
            $table->string('nama');
            $table->timestamps();
        });
        Schema::create('sekolah_mapel_per_tingkat', function (Blueprint $table): void {
            $table->id();
            $table->string('tingkat');
            $table->unsignedBigInteger('mapel_id');
            $table->string('kelompok_mapel');
            $table->unsignedInteger('urutan')->default(0);
        });
        Schema::create('guru_biodata', function (Blueprint $table): void {
            $table->id();
            $table->string('nama');
            $table->string('nip')->nullable();
            $table->string('nuptk')->nullable();
            $table->string('nik')->nullable();
            $table->string('jenis_kelamin')->nullable();
            $table->string('tempat_lahir')->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->string('pendidikan_terakhir')->nullable();
            $table->string('status_kepegawaian')->nullable();
            $table->unsignedSmallInteger('mulai_bertugas')->nullable();
            $table->string('nomor_hp')->nullable();
            $table->string('email')->nullable();
            $table->string('alamat')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('akun_pengguna', function (Blueprint $table): void {
            $table->id();
            $table->string('peran');
            $table->string('username')->unique();
            $table->string('email')->nullable();
            $table->string('password');
            $table->boolean('aktif')->default(true);
            $table->unsignedBigInteger('guru_id')->nullable();
            $table->unsignedBigInteger('siswa_id')->nullable();
            $table->boolean('wajib_ganti_password')->default(false);
            $table->timestamp('email_diverifikasi_at')->nullable();
            $table->timestamp('terakhir_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
        Schema::create('guru_mengajar', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tahun_ajaran_id');
            $table->unsignedBigInteger('guru_id');
            $table->unsignedBigInteger('kelas_id');
            $table->unsignedBigInteger('mapel_id');
            $table->timestamps();
        });
        Schema::create('guru_wali_kelas', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tahun_ajaran_id');
            $table->unsignedBigInteger('guru_id');
            $table->unsignedBigInteger('kelas_id');
            $table->timestamps();
        });

        Schema::create('siswa_riwayat_kelas', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('siswa_id');
            $table->unsignedBigInteger('tahun_ajaran_id');
            $table->unsignedBigInteger('kelas_id');
            $table->string('status');
            $table->string('keputusan_kenaikan')->nullable();
            $table->unsignedBigInteger('kelas_tujuan_id')->nullable();
            $table->date('tanggal_keputusan_kenaikan')->nullable();
            $table->unsignedBigInteger('wali_guru_id')->nullable();
            $table->string('nama_wali_kelas')->nullable();
            $table->string('nip_wali_kelas')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('siswa_berkas', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('siswa_id');
        });
        Schema::create('siswa_pelacakan_alumni', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('siswa_id');
            $table->string('jenis');
            $table->string('nama_tempat')->nullable();
            $table->string('keterangan')->nullable();
            $table->string('tahun', 4)->nullable();
            $table->timestamps();
        });

        Schema::create('siswa_nilai', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('riwayat_kelas_id');
            $table->string('semester');
            $table->unsignedBigInteger('import_unggahan_id')->nullable();
            $table->unsignedBigInteger('import_terakhir_unggahan_id')->nullable();
            $table->unsignedBigInteger('mapel_id');
            $table->decimal('nilai_angka', 5, 2);
            $table->timestamps();
        });

        Schema::create('siswa_kehadiran', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('riwayat_kelas_id');
            $table->string('semester');
            $table->unsignedBigInteger('import_unggahan_id')->nullable();
            $table->unsignedBigInteger('import_terakhir_unggahan_id')->nullable();
            $table->unsignedSmallInteger('sakit')->default(0);
            $table->unsignedSmallInteger('izin')->default(0);
            $table->unsignedSmallInteger('alpa')->default(0);
            $table->text('kokurikuler')->nullable();
            $table->timestamps();
        });

        Schema::create('siswa_ekskul', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('riwayat_kelas_id');
            $table->string('semester');
            $table->unsignedBigInteger('import_unggahan_id')->nullable();
            $table->unsignedBigInteger('import_terakhir_unggahan_id')->nullable();
            $table->string('nama_kegiatan');
            $table->string('predikat')->nullable();
            $table->timestamps();
        });

        Schema::create('catatan_aktivitas', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('pengguna_id')->nullable();
            $table->string('peran');
            $table->string('aksi');
            $table->text('keterangan');
            $table->string('objek_tipe')->nullable();
            $table->unsignedBigInteger('objek_id')->nullable();
            $table->string('alamat_ip')->nullable();
            $table->timestamp('created_at')->nullable();
        });
        Schema::create('import_format_tersimpan', function (Blueprint $table): void {
            $table->id();
            $table->string('nama', 100);
            $table->string('jenis', 20);
            $table->string('kode_struktur', 64);
            $table->string('nama_sheet', 100)->nullable();
            $table->unsignedSmallInteger('baris_judul')->nullable();
            $table->longText('pemetaan_kolom');
            $table->unsignedInteger('dipakai_kali')->default(0);
            $table->timestamp('terakhir_dipakai_at')->nullable();
            $table->unsignedBigInteger('dibuat_oleh')->nullable();
            $table->timestamps();
            $table->unique(['jenis', 'kode_struktur']);
        });
        Schema::create('import_unggahan', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('pengguna_id');
            $table->string('jenis', 20);
            $table->string('nama_file', 255);
            $table->string('lokasi', 255);
            $table->string('status', 20)->default('diunggah');
            $table->unsignedBigInteger('format_tersimpan_id')->nullable();
            $table->unsignedInteger('tahun_ajaran_id')->nullable();
            $table->string('semester', 10)->nullable();
            $table->unsignedInteger('kelas_id')->nullable();
            $table->string('jika_data_sudah_ada', 10)->default('lewati');
            $table->longText('pemetaan_kolom')->nullable();
            $table->unsignedInteger('jumlah_baris')->default(0);
            $table->unsignedInteger('jumlah_siap')->default(0);
            $table->unsignedInteger('jumlah_peringatan')->default(0);
            $table->unsignedInteger('jumlah_error')->default(0);
            $table->unsignedInteger('jumlah_tersimpan')->default(0);
            $table->string('pesan_gagal', 500)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('selesai_at')->nullable();
        });
        Schema::create('import_baris', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('unggahan_id');
            $table->string('nama_sheet', 100)->nullable();
            $table->unsignedInteger('nomor_baris_excel');
            $table->longText('data_asli')->nullable();
            $table->longText('data_bersih')->nullable();
            $table->string('status', 20)->default('siap');
            $table->longText('pesan')->nullable();
        });
        Schema::create('import_kamus_kolom', function (Blueprint $table): void {
            $table->id();
            $table->string('jenis', 20);
            $table->string('milik_siapa', 20)->default('umum');
            $table->string('judul_di_excel', 120);
            $table->string('kolom_tujuan', 60);
            $table->string('asal', 20)->default('bawaan');
            $table->boolean('aktif')->default(true);
            $table->unsignedBigInteger('dibuat_oleh')->nullable();
            $table->timestamps();
            $table->unique(['jenis', 'milik_siapa', 'judul_di_excel']);
        });
        Schema::create('import_kamus_mapel', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('mapel_id');
            $table->string('nama_di_excel', 150)->unique();
            $table->timestamp('created_at')->nullable();
        });

        DB::table('sekolah_tahun_ajaran')->insert([
            'id' => 1,
            'nama' => '2025/2026',
            'sedang_berjalan' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('sekolah_kelas')->insert([
            [
                'id' => 1,
                'nama' => 'X-1',
                'tingkat' => 'X',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'nama' => 'XI-1',
                'tingkat' => 'XI',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
        DB::table('sekolah_mapel')->insert([
            ['id' => 1, 'kode' => 'mtk', 'nama' => 'Matematika', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'kode' => 'fis', 'nama' => 'Fisika', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('siswa_biodata')->insert([
            [
                'id' => 1,
                'nama_lengkap' => 'Nama Sama',
                'nisn' => '0000000001',
                'nomor_induk' => '1001',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'nama_lengkap' => 'Nama Sama',
                'nisn' => '0000000002',
                'nomor_induk' => '1002',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
        DB::table('siswa_riwayat_kelas')->insert([
            [
                'id' => 1,
                'siswa_id' => 1,
                'tahun_ajaran_id' => 1,
                'kelas_id' => 1,
                'status' => 'Aktif',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'siswa_id' => 2,
                'tahun_ajaran_id' => 1,
                'kelas_id' => 1,
                'status' => 'Aktif',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
        $this->admin = Pengguna::create([
            'peran' => 'admin',
            'username' => 'import-admin',
            'email' => 'import-admin@example.test',
            'password' => 'unused',
            'aktif' => true,
        ]);
    }

    protected function tearDown(): void
    {
        foreach ([
            'import_baris',
            'import_unggahan',
            'import_format_tersimpan',
            'import_kamus_kolom',
            'import_kamus_mapel',
            'guru_mengajar',
            'guru_wali_kelas',
            'akun_pengguna',
            'guru_biodata',
            'siswa_nilai',
            'siswa_kehadiran',
            'siswa_ekskul',
            'siswa_riwayat_kelas',
            'siswa_berkas',
            'siswa_pelacakan_alumni',
            'catatan_aktivitas',
            'sekolah_mapel_per_tingkat',
            'sekolah_mapel',
            'sekolah_kelas',
            'sekolah_tahun_ajaran',
            'sekolah_pengaturan',
            'sekolah_identitas',
            'siswa_biodata',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_grade_import_requires_identifiers_to_agree_and_uses_the_matching_student(): void
    {
        $this->import('nilai', [[
            'nisn' => '0000000002',
            'nis' => '1002',
            'nama' => 'Nama Sama',
            'kelas' => 'X-1',
            'mtk' => 88,
            '__excel_row' => 7,
        ]])->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonPath('skipped', 0);

        $this->assertSame(0, DB::table('siswa_nilai')
            ->where('riwayat_kelas_id', 1)
            ->count());
        $this->assertSame(1, DB::table('siswa_nilai')
            ->where('riwayat_kelas_id', 2)
            ->count());
    }

    public function test_grade_import_for_graduated_student_uses_explicit_historical_year(): void
    {
        DB::table('sekolah_tahun_ajaran')->insert([
            'id' => 2,
            'nama' => '2024/2025',
            'sedang_berjalan' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('sekolah_kelas')->insert([
            'id' => 3,
            'nama' => 'XII-1',
            'tingkat' => 'XII',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('siswa_biodata')->where('id', 1)->update([
            'tahun_lulus' => '2025',
            'alasan_keluar' => 'Lulus',
        ]);
        DB::table('siswa_riwayat_kelas')->insert([
            'id' => 3,
            'siswa_id' => 1,
            'tahun_ajaran_id' => 2,
            'kelas_id' => 3,
            'status' => 'Lulus',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->import('nilai', [[
            'nisn' => '0000000001',
            'nama' => 'Nama Sama',
            'kelas' => 'XII-1',
            'mtk' => 91,
        ]], 'Ganjil', '2024/2025');

        $response->assertOk()->assertJsonPath('count', 1)->assertJsonPath('skipped', 0);
        $this->assertDatabaseHas('siswa_nilai', [
            'riwayat_kelas_id' => 3,
            'semester' => 'Ganjil',
            'mapel_id' => 1,
            'nilai_angka' => 91,
        ]);
    }

    public function test_later_import_tracks_the_latest_batch_without_changing_the_creating_batch(): void
    {
        $row = [
            'nisn' => '0000000002',
            'nis' => '1002',
            'nama' => 'Nama Sama',
            'kelas' => 'X-1',
            'mtk' => 80,
        ];

        $firstImport = $this->import('nilai', [$row])->assertOk();
        $secondImport = $this->import('nilai', [array_merge($row, ['mtk' => 90])], duplicateMode: 'timpa')->assertOk();
        $record = DB::table('siswa_nilai')->where('riwayat_kelas_id', 2)->first();

        $this->assertSame((int) $firstImport->json('batch_id'), (int) $record->import_unggahan_id);
        $this->assertSame((int) $secondImport->json('batch_id'), (int) $record->import_terakhir_unggahan_id);
        $this->assertSame(90.0, (float) $record->nilai_angka);
    }

    public function test_conflicting_identifiers_and_ambiguous_names_are_skipped_with_row_errors(): void
    {
        $response = $this->import('nilai', [
            [
                'nisn' => '0000000001',
                'nis' => '1002',
                'nama' => 'Nama Sama',
                'kelas' => 'X-1',
                'mtk' => 91,
                '__excel_row' => 8,
            ],
            [
                'nama' => 'Nama Sama',
                'kelas' => 'X-1',
                'mtk' => 92,
                '__excel_row' => 9,
            ],
            [
                'nisn' => '0000000099',
                'nama' => 'Nama Sama',
                'kelas' => 'X-1',
                'mtk' => 93,
                '__excel_row' => 10,
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('count', 0)
            ->assertJsonPath('skipped', 3)
            ->assertJsonPath('errors.0.row', 8)
            ->assertJsonPath('errors.1.row', 9)
            ->assertJsonPath('errors.2.row', 10)
            ->assertJsonFragment(['reason' => 'NISN dan NIS mengarah ke siswa berbeda; baris ini dilewati.'])
            ->assertJsonFragment(['reason' => 'Nama ambigu; lebih dari satu siswa memiliki nama yang sama. Tambahkan NISN atau NIS.'])
            ->assertJsonFragment(['reason' => 'NISN tidak ditemukan; baris ini dilewati dan tidak dicocokkan dengan identitas lain.']);

        $this->assertSame(0, DB::table('siswa_nilai')->count());
    }

    public function test_preview_matches_by_nisn_nis_and_normalized_unique_name_without_writing(): void
    {
        DB::table('siswa_biodata')->where('id', 1)->update(['nama_lengkap' => 'Citra Ayu, S.Pd.']);

        $response = $this->actingAs($this->admin)->withoutMiddleware()->postJson(route('admin.import.preview-match'), [
            'type' => 'nilai',
            'tahun_ajaran' => '2025/2026',
            'semester' => 'Ganjil',
            'kelas' => 'X-1',
            'rows' => [
                ['nisn' => '0000000002', 'kelas' => 'X-1'],
                ['nis' => '1002', 'kelas' => 'X-1'],
                ['nama' => '  citra   ayu! ', 'kelas' => 'X-1'],
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('results.0.student.id', 2)
            ->assertJsonPath('results.0.metode', 'nisn')
            ->assertJsonPath('results.0.level', 'ok')
            ->assertJsonPath('results.1.student.id', 2)
            ->assertJsonPath('results.1.metode', 'nis')
            ->assertJsonPath('results.1.level', 'ok')
            ->assertJsonPath('results.2.student.id', 1)
            ->assertJsonPath('results.2.metode', 'nama')
            ->assertJsonPath('results.2.level', 'ok');

        $this->assertSame(0, DB::table('import_unggahan')->count());
        $this->assertSame(0, DB::table('siswa_nilai')->count());
    }

    public function test_preview_reports_ambiguous_names_with_candidate_nisn_and_class(): void
    {
        $response = $this->actingAs($this->admin)->withoutMiddleware()->postJson(route('admin.import.preview-match'), [
            'type' => 'nilai',
            'tahun_ajaran' => '2025/2026',
            'rows' => [['nama' => 'Nama Sama', 'kelas' => 'X-1']],
        ]);

        $response->assertOk()
            ->assertJsonPath('results.0.student', null)
            ->assertJsonPath('results.0.metode', 'nama')
            ->assertJsonPath('results.0.level', 'error')
            ->assertJsonPath('results.0.kandidat.0.nisn', '0000000001')
            ->assertJsonPath('results.0.kandidat.0.kelas', 'X-1')
            ->assertJsonPath('results.0.kandidat.1.nisn', '0000000002');
    }

    public function test_preview_name_matching_uses_the_selected_class_not_an_academic_year(): void
    {
        DB::table('siswa_biodata')->where('id', 1)->update(['nama_lengkap' => 'Nama Berbeda']);
        DB::table('sekolah_tahun_ajaran')->insert([
            'id' => 2,
            'nama' => '2026/2027',
            'sedang_berjalan' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->withoutMiddleware()->postJson(route('admin.import.preview-match'), [
            'type' => 'nilai',
            'tahun_ajaran' => '2026/2027',
            'semester' => '1',
            'kelas' => 'X-1',
            'rows' => [['nama' => 'Nama Sama', 'kelas' => 'X-1']],
        ]);

        $response->assertOk()
            ->assertJsonPath('results.0.student.id', 2)
            ->assertJsonPath('results.0.student.nama_lengkap', 'Nama Sama')
            ->assertJsonPath('results.0.level', 'ok');
        $this->assertDatabaseHas('sekolah_tahun_ajaran', ['nama' => '2026/2027']);
    }

    public function test_preview_and_save_use_historical_class_year_and_keep_semesters_separate(): void
    {
        DB::table('sekolah_tahun_ajaran')->insert([
            'id' => 2,
            'nama' => '2024/2025',
            'sedang_berjalan' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('siswa_biodata')->insert([
            'id' => 3,
            'nama_lengkap' => 'Siswa Riwayat Lama',
            'nisn' => '0000000003',
            'nomor_induk' => '1003',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('siswa_riwayat_kelas')->insert([
            'id' => 3,
            'siswa_id' => 3,
            'tahun_ajaran_id' => 2,
            'kelas_id' => 1,
            'status' => 'Aktif',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ([
            ['selection' => '1', 'term' => 'Ganjil', 'score' => 81],
            ['selection' => '2', 'term' => 'Genap', 'score' => 82],
        ] as $semester) {
            $row = [
                'nisn' => '0000000003',
                'nama' => 'Siswa Riwayat Lama',
                'kelas' => 'X-1',
                'mtk' => $semester['score'],
            ];
            $preview = $this->actingAs($this->admin)->withoutMiddleware()->postJson(route('admin.import.preview-match'), [
                'type' => 'nilai',
                'kelas' => 'X-1',
                'semester' => $semester['selection'],
                'rows' => [$row],
            ]);
            $preview->assertOk()
                ->assertJsonPath('results.0.student.id', 3)
                ->assertJsonPath('results.0.target.kelas', 'X-1')
                ->assertJsonPath('results.0.target.tahun_ajaran', '2024/2025')
                ->assertJsonPath('results.0.target.semester', $semester['term']);

            $this->import('nilai', [$row], $semester['selection'])
                ->assertOk()
                ->assertJsonPath('count', 1);
        }

        $this->assertSame(2, DB::table('siswa_nilai')->where('riwayat_kelas_id', 3)->count());
        $this->assertDatabaseHas('siswa_nilai', [
            'riwayat_kelas_id' => 3,
            'semester' => 'Ganjil',
            'nilai_angka' => 81,
        ]);
        $this->assertDatabaseHas('siswa_nilai', [
            'riwayat_kelas_id' => 3,
            'semester' => 'Genap',
            'nilai_angka' => 82,
        ]);
        $this->assertSame(1, DB::table('sekolah_tahun_ajaran')->where('sedang_berjalan', true)->count());
    }

    public function test_matching_name_similarity_warns_at_or_above_threshold_and_errors_below_it(): void
    {
        DB::table('siswa_biodata')->where('id', 2)->update(['nama_lengkap' => 'Siti Aminah']);
        DB::table('siswa_biodata')->where('id', 1)->update(['nama_lengkap' => 'abcdefghijklmnopqrst']);

        $preview = $this->actingAs($this->admin)->withoutMiddleware()->postJson(route('admin.import.preview-match'), [
            'type' => 'nilai',
            'tahun_ajaran' => '2025/2026',
            'semester' => '1',
            'kelas' => 'X-1',
            'rows' => [
                ['nisn' => '0000000002', 'nama' => 'Siti Aminahh', 'kelas' => 'X-1'],
                ['nisn' => '0000000002', 'nama' => 'Budi Santoso', 'kelas' => 'X-1'],
                ['nisn' => '0000000001', 'nama' => 'abcdefghijklmnopqXYZ', 'kelas' => 'X-1'],
            ],
        ]);

        $preview->assertOk()
            ->assertJsonPath('results.0.student.id', 2)
            ->assertJsonPath('results.0.level', 'peringatan')
            ->assertJsonPath('results.1.student', null)
            ->assertJsonPath('results.1.level', 'error')
            ->assertJsonPath('results.2.level', 'peringatan')
            ->assertJsonFragment(['pesan' => 'Nama tidak sesuai NISN/NIS; periksa kembali identitas pada file.']);

        $saved = $this->import('nilai', [
            ['nisn' => '0000000002', 'nama' => 'Siti Aminahh', 'kelas' => 'X-1', 'mtk' => 88],
            ['nisn' => '0000000002', 'nama' => 'Budi Santoso', 'kelas' => 'X-1', 'mtk' => 77],
        ]);

        $saved->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonPath('skipped', 1)
            ->assertJsonFragment(['warning' => true, 'reason' => 'Nama berbeda dari data siswa, tetapi kemiripannya minimal 85%. Periksa kembali sebelum melanjutkan.'])
            ->assertJsonFragment(['reason' => 'Nama tidak sesuai NISN/NIS; periksa kembali identitas pada file.']);
    }

    public function test_teacher_cannot_preview_matches_for_an_unassigned_class(): void
    {
        $teacher = $this->buatAkunGuru('preview-math-teacher');
        $this->tugaskanMapel($teacher, 1, 1, 1);

        $response = $this->actingAs($teacher)->withoutMiddleware()->postJson(route('guru.import.preview-match'), [
            'type' => 'nilai',
            'tahun_ajaran' => '2025/2026',
            'semester' => '1',
            'kelas' => 'auto',
            'rows' => [[
                'nisn' => '0000000002',
                'nama' => 'Nama Sama',
                'kelas' => 'XI-1',
                'mtk' => 88,
            ]],
        ]);

        $response->assertOk()
            ->assertJsonPath('results.0.student', null)
            ->assertJsonPath('results.0.level', 'error')
            ->assertJsonPath('results.0.pesan', 'Anda bukan guru mapel Matematika di kelas XI-1.');
        $this->assertSame(0, DB::table('siswa_nilai')->count());
        $this->assertSame(0, DB::table('import_unggahan')->count());
    }

    public function test_teacher_imports_are_limited_to_active_students_in_the_assigned_class_and_current_year(): void
    {
        DB::table('sekolah_tahun_ajaran')->insert([
            'id' => 2,
            'nama' => '2024/2025',
            'sedang_berjalan' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('sekolah_kelas')->insert([
            'id' => 3,
            'nama' => 'XII-1',
            'tingkat' => 'XII',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('siswa_biodata')->insert([
            'id' => 3,
            'nama_lengkap' => 'Alumni Contoh',
            'nisn' => '0000000003',
            'nomor_induk' => '1003',
            'tahun_lulus' => '2025',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('siswa_riwayat_kelas')->insert([
            'id' => 3,
            'siswa_id' => 3,
            'tahun_ajaran_id' => 2,
            'kelas_id' => 3,
            'status' => 'Lulus',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $teacher = $this->buatAkunGuru('historical-import-teacher');
        $this->tugaskanMapel($teacher, 1, 1, 1);
        $row = [
            'nisn' => '0000000003',
            'nama' => 'Alumni Contoh',
            'kelas' => 'X-1',
            'mtk' => 88,
        ];

        $this->actingAs($teacher)->withoutMiddleware()
            ->postJson(route('guru.import.preview-match'), [
                'type' => 'nilai',
                'semester' => '1',
                'kelas' => 'X-1',
                'rows' => [$row],
            ])
            ->assertOk()
            ->assertJsonPath('results.0.level', 'error')
            ->assertJsonPath('results.0.pesan', 'bukan siswa kelas Anda.');

        $this->postJson(route('guru.import.save-batch'), [
            'type' => 'nilai',
            'semester' => '1',
            'kelas' => 'X-1',
            'rows' => [$row],
        ])
            ->assertOk()
            ->assertJsonPath('count', 0)
            ->assertJsonPath('errors.0.reason', 'bukan siswa kelas Anda.');
        $this->assertSame(0, DB::table('siswa_nilai')->count());

        $historicalRow = array_merge($row, [
            'nisn' => '0000000001',
            'nama' => 'Nama Sama',
            'tahun_ajaran' => '2024/2025',
        ]);
        $this->postJson(route('guru.import.preview-match'), [
            'type' => 'nilai',
            'semester' => '1',
            'kelas' => 'X-1',
            'rows' => [$historicalRow],
        ])
            ->assertOk()
            ->assertJsonPath('results.0.level', 'error')
            ->assertJsonPath('results.0.pesan', 'Data tahun ajaran selain tahun berjalan hanya dapat diisi oleh admin.');
        $this->postJson(route('guru.import.save-batch'), [
            'type' => 'nilai',
            'semester' => '1',
            'kelas' => 'X-1',
            'rows' => [$historicalRow],
        ])
            ->assertOk()
            ->assertJsonPath('count', 0)
            ->assertJsonPath('errors.0.reason', 'Data tahun ajaran selain tahun berjalan hanya dapat diisi oleh admin.');
        $this->assertSame(0, DB::table('siswa_nilai')->count());
    }

    public function test_teacher_cannot_select_an_old_year_for_preview_or_batch_import(): void
    {
        DB::table('sekolah_tahun_ajaran')->insert([
            'id' => 2,
            'nama' => '2024/2025',
            'sedang_berjalan' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $teacher = $this->buatAkunGuru('old-year-selection-teacher');
        $this->tugaskanMapel($teacher, 1, 1, 1);
        $payload = [
            'type' => 'nilai',
            'tahun_ajaran' => '2024/2025',
            'semester' => '1',
            'kelas' => 'X-1',
            'rows' => [[
                'nisn' => '0000000001',
                'nama' => 'Nama Sama',
                'kelas' => 'X-1',
                'mtk' => 88,
            ]],
        ];

        $this->actingAs($teacher)->withoutMiddleware()
            ->postJson(route('guru.import.preview-match'), $payload)
            ->assertForbidden()
            ->assertJsonPath('message', 'Guru hanya dapat mengisi data akademik pada tahun ajaran berjalan.');
        $this->postJson(route('guru.import.save-batch'), $payload)
            ->assertForbidden()
            ->assertJsonPath('message', 'Guru hanya dapat mengisi data akademik pada tahun ajaran berjalan.');
        $this->assertSame(0, DB::table('import_unggahan')->count());
        $this->assertSame(0, DB::table('siswa_nilai')->count());
    }

    public function test_teacher_cannot_create_history_during_preview(): void
    {
        $teacher = $this->buatAkunGuru('preview-history-teacher');
        $this->tugaskanMapel($teacher, 1, 1, 1);

        $response = $this->actingAs($teacher)->withoutMiddleware()->postJson(route('guru.import.preview-match'), [
            'type' => 'nilai',
            'semester' => '1',
            'kelas' => 'X-1',
            'create_riwayat' => true,
            'create_tahun_ajaran_id' => 1,
            'create_kelas_id' => 1,
            'rows' => [[
                'nisn' => '0000000001',
                'nama' => 'Nama Sama',
                'kelas' => 'X-1',
                'mtk' => 90,
            ]],
        ]);

        $response->assertForbidden()
            ->assertJsonPath('message', 'Hanya admin yang dapat membuat riwayat kelas saat import.');
        $this->assertSame(0, DB::table('siswa_nilai')->count());
    }

    public function test_unique_name_in_the_requested_year_can_be_matched_without_class_or_numeric_ids(): void
    {
        DB::table('siswa_riwayat_kelas')->where('siswa_id', 1)->delete();

        $response = $this->import('nilai', [[
            'nama' => 'Nama Sama',
            'kelas' => 'X-1',
            'sem' => '1',
            'tahun_ajaran' => '2025/2026',
            'mtk' => 82,
            '__excel_row' => 11,
        ]]);

        $response->assertOk()->assertJsonPath('count', 1)->assertJsonPath('skipped', 0);
        $this->assertSame(1, DB::table('siswa_nilai')
            ->where('riwayat_kelas_id', 2)
            ->count());
    }

    public function test_import_does_not_move_a_student_to_a_different_class(): void
    {
        $response = $this->import('hadir', [[
            'nisn' => '0000000002',
            'nama' => 'Nama Sama',
            'kelas' => 'XI-1',
            'sakit' => 1,
            '__excel_row' => 12,
        ]]);

        $response->assertOk()
            ->assertJsonPath('count', 0)
            ->assertJsonPath('skipped', 1)
            ->assertJsonFragment(['reason' => 'Siswa belum punya riwayat kelas XI.']);
        $this->assertSame(1, DB::table('siswa_riwayat_kelas')->where('siswa_id', 2)->value('kelas_id'));
        $this->assertSame(0, DB::table('siswa_kehadiran')->count());
    }

    public function test_academic_import_uses_the_matching_class_history_year_and_skips_a_class_conflict(): void
    {
        $newYearImport = $this->import('nilai', [[
            'nisn' => '0000000002',
            'nama' => 'Nama Sama',
            'kelas' => 'X-1',
            'tahun_ajaran' => '2026/2027',
            'mtk' => 85,
            '__excel_row' => 14,
        ]]);
        $newYearImport->assertOk()->assertJsonPath('count', 1);

        $this->assertSame(1, DB::table('siswa_riwayat_kelas')->where('siswa_id', 2)->count());
        $this->assertSame(1, DB::table('siswa_nilai')
            ->where('riwayat_kelas_id', 2)
            ->count());

        $classConflict = $this->withoutMiddleware()->postJson(route('admin.import.save-batch'), [
            'type' => 'hadir',
            'tahun_ajaran' => '2025/2026',
            'semester' => 'Ganjil',
            'mode' => 'kelas',
            'kelas' => 'X-1',
            'rows' => [[
                'nisn' => '0000000002',
                'nama' => 'Nama Sama',
                'kelas' => 'XI-1',
                'sakit' => 1,
                '__excel_row' => 15,
            ]],
        ]);
        $classConflict->assertOk()
            ->assertJsonPath('count', 0)
            ->assertJsonFragment(['reason' => 'Tingkat kelas pada baris (XI) berbeda dari pilihan (X).']);
        $this->assertSame(0, DB::table('siswa_kehadiran')->count());
    }

    public function test_attendance_and_extracurricular_imports_use_the_same_verified_student_match(): void
    {
        $attendance = $this->import('hadir', [[
            'nis' => '1002',
            'nama' => 'Nama Sama',
            'kelas' => 'X-1',
            'sakit' => 2,
            '__excel_row' => 4,
        ]]);
        $attendance->assertOk()->assertJsonPath('count', 1);
        $this->assertSame(2, DB::table('siswa_kehadiran')
            ->where('riwayat_kelas_id', 2)
            ->value('sakit'));

        $extracurricular = $this->import('ekskul', [[
            'nisn' => '0000000002',
            'nama' => 'Nama Sama',
            'kelas' => 'X-1',
            'ekskul' => 'Pramuka',
            'predikat' => 'A',
            '__excel_row' => 5,
        ]]);
        $extracurricular->assertOk()->assertJsonPath('count', 1);
        $this->assertSame(1, DB::table('siswa_ekskul')
            ->where('riwayat_kelas_id', 2)
            ->count());
        $this->assertSame(0, DB::table('siswa_ekskul')
            ->where('riwayat_kelas_id', 1)
            ->count());
    }

    public function test_global_semesters_resolve_to_existing_histories_using_explicit_row_years(): void
    {
        DB::table('sekolah_tahun_ajaran')->insert([
            [
                'id' => 2,
                'nama' => '2026/2027',
                'sedang_berjalan' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'nama' => '2027/2028',
                'sedang_berjalan' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
        DB::table('sekolah_kelas')->insert([
            [
                'id' => 3,
                'nama' => 'XII-1',
                'tingkat' => 'XII',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
        DB::table('siswa_riwayat_kelas')->insert([
            [
                'id' => 3,
                'siswa_id' => 2,
                'tahun_ajaran_id' => 2,
                'kelas_id' => 2,
                'status' => 'Aktif',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 4,
                'siswa_id' => 2,
                'tahun_ajaran_id' => 3,
                'kelas_id' => 3,
                'status' => 'Lulus',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $rows = [];
        foreach ([
            1 => 'X-1',
            2 => 'X-1',
            3 => 'XI-1',
            4 => 'XI-1',
            5 => 'XII-1',
            6 => 'XII-1',
        ] as $semester => $className) {
            $rows[] = [
                'nisn' => '0000000002',
                'nama' => 'Nama Sama',
                'kelas' => $className,
                'tahun_ajaran' => match (true) {
                    str_starts_with($className, 'XII') => '2027/2028',
                    str_starts_with($className, 'XI') => '2026/2027',
                    default => '2025/2026',
                },
                'sem' => (string) $semester,
                'mtk' => 80 + $semester,
            ];
        }

        $this->import('nilai', $rows, 'auto')
            ->assertOk()
            ->assertJsonPath('count', 6)
            ->assertJsonPath('skipped', 0);

        $this->assertSame(6, DB::table('siswa_nilai')->count());
        foreach ([1 => 2, 2 => 2, 3 => 3, 4 => 3, 5 => 4, 6 => 4] as $semester => $historyId) {
            $term = $semester % 2 === 1 ? 'Ganjil' : 'Genap';
            $this->assertSame(1, DB::table('siswa_nilai')
                ->where('riwayat_kelas_id', $historyId)
                ->where('semester', $term)
                ->count());
        }

        $this->import('hadir', [[
            'nisn' => '0000000002',
            'kelas' => 'XII-1',
            'tahun_ajaran' => '2027/2028',
            'sakit' => 3,
        ]], '6')->assertOk()->assertJsonPath('count', 1);
        $this->assertSame(3, DB::table('siswa_kehadiran')
            ->where('riwayat_kelas_id', 4)
            ->where('semester', 'Genap')
            ->value('sakit'));

        $this->import('ekskul', [[
            'nisn' => '0000000002',
            'kelas' => 'XI-1',
            'tahun_ajaran' => '2026/2027',
            'sem' => '3',
            'ekskul' => 'Pramuka',
            'predikat' => 'A',
        ]], 'auto')->assertOk()->assertJsonPath('count', 1);
        $this->assertSame(1, DB::table('siswa_ekskul')
            ->where('riwayat_kelas_id', 3)
            ->where('semester', 'Ganjil')
            ->count());
        $this->assertSame(3, DB::table('sekolah_tahun_ajaran')->count());
    }

    public function test_optional_admin_history_creation_uses_selected_year_and_class(): void
    {
        DB::table('siswa_biodata')->insert([
            'id' => 3,
            'nama_lengkap' => 'Siswa Tanpa Riwayat',
            'nisn' => '0000000003',
            'nomor_induk' => '1003',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('sekolah_tahun_ajaran')->insert([
            'id' => 2,
            'nama' => '2026/2027',
            'sedang_berjalan' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->import('nilai', [[
            'nisn' => '0000000003',
            'nama' => 'Siswa Tanpa Riwayat',
            'kelas' => 'X-1',
            'tahun_ajaran' => '2026/2027',
            'sem' => 'Ganjil',
            'mtk' => 91,
        ]], targetOptions: [
            'create_riwayat' => true,
            'create_tahun_ajaran_id' => 2,
            'create_kelas_id' => 1,
        ]);

        $response->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonPath('created_years', 0)
            ->assertJsonPath('created_histories', 1)
            ->assertJsonPath('message', 'Berhasil mengimpor 1 data nilai (termasuk 1 riwayat kelas baru).');

        $newYearId = 2;
        $newHistoryId = DB::table('siswa_riwayat_kelas')
            ->where('siswa_id', 3)
            ->where('tahun_ajaran_id', $newYearId)
            ->where('kelas_id', 1)
            ->value('id');

        $this->assertNotNull($newHistoryId);
        $this->assertDatabaseHas('siswa_nilai', [
            'riwayat_kelas_id' => $newHistoryId,
            'semester' => 'Ganjil',
            'mapel_id' => 1,
            'nilai_angka' => 91,
        ]);
    }

    public function test_ambiguous_stay_in_same_class_without_explicit_year_is_rejected(): void
    {
        DB::table('sekolah_tahun_ajaran')->insert([
            'id' => 2,
            'nama' => '2026/2027',
            'sedang_berjalan' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('siswa_riwayat_kelas')->insert([
            'siswa_id' => 2,
            'tahun_ajaran_id' => 2,
            'kelas_id' => 1,
            'status' => 'Aktif',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->import('nilai', [[
            'nisn' => '0000000002',
            'kelas' => 'X-1',
            'sem' => '1',
            'mtk' => 90,
        ]], 'auto')
            ->assertOk()
            ->assertJsonPath('count', 0)
            ->assertJsonPath('errors.0.reason', 'Riwayat kelas X-1 ditemukan pada beberapa tahun ajaran; isi tahun_ajaran di file untuk menentukan target.');

        $this->assertSame(0, DB::table('siswa_nilai')->count());
    }

    public function test_global_semester_three_is_saved_as_xi_ganjil(): void
    {
        DB::table('siswa_biodata')->insert([
            'id' => 3,
            'nama_lengkap' => 'Siswa XI',
            'nisn' => '0000000003',
            'nomor_induk' => '1003',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->import('nilai', [[
            'nisn' => '0000000003',
            'kelas' => 'XI-1',
            'tahun_ajaran' => '2025/2026',
            'sem' => '3',
            'mtk' => 87,
        ]], 'auto', targetOptions: [
            'create_riwayat' => true,
            'create_tahun_ajaran_id' => 1,
            'create_kelas_id' => 2,
        ])
            ->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonPath('created_histories', 1);

        $historyId = DB::table('siswa_riwayat_kelas')
            ->where('siswa_id', 3)
            ->where('tahun_ajaran_id', 1)
            ->where('kelas_id', 2)
            ->value('id');
        $this->assertNotNull($historyId);
        $this->assertDatabaseHas('siswa_nilai', [
            'riwayat_kelas_id' => $historyId,
            'semester' => 'Ganjil',
            'nilai_angka' => 87,
        ]);
    }

    public function test_local_semester_numbers_and_ganjil_genap_are_mapped_by_class_level(): void
    {
        DB::table('sekolah_tahun_ajaran')->insert([
            [
                'id' => 2,
                'nama' => '2026/2027',
                'sedang_berjalan' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'nama' => '2027/2028',
                'sedang_berjalan' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
        DB::table('sekolah_kelas')->insert([
            ['id' => 3, 'nama' => 'XII-1', 'tingkat' => 'XII', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('siswa_riwayat_kelas')->insert([
            ['id' => 3, 'siswa_id' => 2, 'tahun_ajaran_id' => 2, 'kelas_id' => 2, 'status' => 'Aktif', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'siswa_id' => 2, 'tahun_ajaran_id' => 3, 'kelas_id' => 3, 'status' => 'Lulus', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $response = $this->import('nilai', [
            ['nisn' => '0000000002', 'kelas' => 'X-1', 'tahun_ajaran' => '2025/2026', 'sem' => '1', 'mtk' => 81],
            ['nisn' => '0000000002', 'kelas' => 'X-1', 'tahun_ajaran' => '2025/2026', 'sem' => 'Genap', 'mtk' => 82],
            ['nisn' => '0000000002', 'kelas' => 'XI-1', 'tahun_ajaran' => '2026/2027', 'sem' => 'Semester 1', 'mtk' => 83],
            ['nisn' => '0000000002', 'kelas' => 'XI-1', 'tahun_ajaran' => '2026/2027', 'sem' => '2', 'mtk' => 84],
            ['nisn' => '0000000002', 'kelas' => 'XII-1', 'tahun_ajaran' => '2027/2028', 'sem' => 'Ganjil', 'mtk' => 85],
            ['nisn' => '0000000002', 'kelas' => 'XII-1', 'tahun_ajaran' => '2027/2028', 'sem' => 'Semester 2', 'mtk' => 86],
        ], 'auto');

        $response->assertOk()->assertJsonPath('count', 6)->assertJsonPath('skipped', 0);
        $this->assertSame(6, DB::table('siswa_nilai')->count());
        foreach ([1 => 2, 2 => 2, 3 => 3, 4 => 3, 5 => 4, 6 => 4] as $index => $historyId) {
            $term = $index % 2 === 1 ? 'Ganjil' : 'Genap';
            $this->assertSame(1, DB::table('siswa_nilai')
                ->where('riwayat_kelas_id', $historyId)
                ->where('semester', $term)
                ->count());
        }

        $this->import('hadir', [[
            'nisn' => '0000000002',
            'kelas' => 'XI-1',
            'tahun_ajaran' => '2026/2027',
            'sakit' => 1,
        ]], '1')->assertOk()->assertJsonPath('count', 1);
        $this->assertSame(1, DB::table('siswa_kehadiran')
            ->where('riwayat_kelas_id', 3)
            ->where('semester', 'Ganjil')
            ->count());

        $this->import('hadir', [[
            'nisn' => '0000000002',
            'kelas' => 'XII-1',
            'tahun_ajaran' => '2027/2028',
            'sakit' => 2,
        ]], 'local-genap')->assertOk()->assertJsonPath('count', 1);
        $this->assertSame(1, DB::table('siswa_kehadiran')
            ->where('riwayat_kelas_id', 4)
            ->where('semester', 'Genap')
            ->count());
    }

    public function test_six_semester_import_skips_wrong_grade_or_ambiguous_repeated_class_history(): void
    {
        DB::table('sekolah_tahun_ajaran')->insert([
            'id' => 2,
            'nama' => '2026/2027',
            'sedang_berjalan' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('siswa_riwayat_kelas')->insert([
            'id' => 3,
            'siswa_id' => 2,
            'tahun_ajaran_id' => 2,
            'kelas_id' => 1,
            'status' => 'Aktif',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $ambiguousHistory = $this->import('nilai', [[
            'nisn' => '0000000002',
            'kelas' => 'X-1',
            'sem' => '1',
            'mtk' => 90,
            '__excel_row' => 21,
        ]], 'auto');

        $ambiguousHistory->assertOk()
            ->assertJsonPath('count', 0)
            ->assertJsonPath('skipped', 1)
            ->assertJsonPath('errors.0.row', 21)
            ->assertJsonFragment(['reason' => 'Riwayat kelas X-1 ditemukan pada beberapa tahun ajaran; isi tahun_ajaran di file untuk menentukan target.']);

        DB::table('siswa_riwayat_kelas')->where('id', 3)->delete();
        $wrongSemesterGrade = $this->import('nilai', [[
            'nisn' => '0000000002',
            'kelas' => 'X-1',
            'sem' => '3',
            'mtk' => 91,
            '__excel_row' => 22,
        ]], 'auto');

        $wrongSemesterGrade->assertOk()
            ->assertJsonPath('count', 0)
            ->assertJsonPath('skipped', 1)
            ->assertJsonPath('errors.0.row', 22)
            ->assertJsonFragment(['reason' => 'Kelas X-1 tidak sesuai dengan semester 3 yang dipilih (semester 3 harus kelas XI).']);

        $this->assertSame(0, DB::table('siswa_nilai')->count());
    }

    public function test_teacher_imports_assigned_grades_and_skips_unassigned_subjects_and_classes_per_row(): void
    {
        $guru = $this->buatAkunGuru('guru-mapel');
        $this->tugaskanMapel($guru, 1, 1, 1);

        $response = $this->actingAs($guru)->withoutMiddleware()->postJson(route('guru.import.save-batch'), [
            'type' => 'nilai',
            'tahun_ajaran' => '2025/2026',
            'semester' => 'Ganjil',
            'mode' => 'semua',
            'rows' => [
                ['nisn' => '0000000001', 'nama' => 'Nama Sama', 'kelas' => 'X-1', 'mtk' => 88, '__excel_row' => 4],
                ['nisn' => '0000000001', 'nama' => 'Nama Sama', 'kelas' => 'X-1', 'fis' => 75, '__excel_row' => 5],
                ['nisn' => '0000000001', 'nama' => 'Nama Sama', 'kelas' => 'XI-1', 'mtk' => 82, '__excel_row' => 6],
                ['nisn' => '0000000001', 'nama' => 'Nama Sama', 'kelas' => 'X-1', 'bio' => 70, '__excel_row' => 7],
            ],
            'new_mapel' => [['kode' => 'bio', 'nama' => 'Biologi']],
        ]);

        $response->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonPath('skipped', 3)
            ->assertJsonPath('errors.0.row', 5)
            ->assertJsonPath('errors.0.reason', 'Anda bukan guru mapel Fisika di kelas X-1.')
            ->assertJsonPath('errors.1.row', 6)
            ->assertJsonPath('errors.1.reason', 'Anda bukan guru mapel Matematika di kelas XI-1.')
            ->assertJsonPath('errors.2.row', 7)
            ->assertJsonPath('errors.2.reason', 'Mapel bio tidak ditemukan pada data mapel sekolah; baris ini dilewati.');

        $this->assertSame(1, DB::table('siswa_nilai')->count());
        $this->assertSame(1, DB::table('siswa_nilai')->value('mapel_id'));
        $this->assertSame(2, DB::table('sekolah_mapel')->count());
    }

    public function test_attendance_and_extracurricular_imports_allow_only_assigned_homeroom_class_per_row(): void
    {
        $wali = $this->buatAkunGuru('guru-wali');
        $this->tugaskanWali($wali, 1, 1);

        foreach ([
            ['type' => 'hadir', 'value' => ['sakit' => 2]],
            ['type' => 'ekskul', 'value' => ['ekskul' => 'Pramuka', 'predikat' => 'A']],
        ] as $import) {
            $response = $this->actingAs($wali)->withoutMiddleware()->postJson(route('guru.import.save-batch'), [
                'type' => $import['type'],
                'tahun_ajaran' => '2025/2026',
                'semester' => 'Ganjil',
                'mode' => 'semua',
                'rows' => [
                    array_merge(['nisn' => '0000000001', 'nama' => 'Nama Sama', 'kelas' => 'X-1'], $import['value']),
                    array_merge(['nisn' => '0000000001', 'nama' => 'Nama Sama', 'kelas' => 'XI-1'], $import['value']),
                ],
            ]);

            $response->assertOk()
                ->assertJsonPath('count', 1)
                ->assertJsonPath('skipped', 1)
                ->assertJsonPath('errors.0.reason', 'Anda bukan wali kelas XI-1.');
        }

        $this->assertSame(1, DB::table('siswa_kehadiran')->count());
        $this->assertSame(1, DB::table('siswa_ekskul')->count());
    }

    public function test_admin_can_import_unassigned_subject_attendance_and_extracurricular_data(): void
    {
        $grade = $this->import('nilai', [[
            'nisn' => '0000000001',
            'nama' => 'Nama Sama',
            'kelas' => 'X-1',
            'fis' => 77,
        ]]);
        $grade->assertOk()->assertJsonPath('count', 1);

        $this->import('hadir', [[
            'nisn' => '0000000001',
            'nama' => 'Nama Sama',
            'kelas' => 'X-1',
            'sakit' => 1,
        ]])->assertOk()->assertJsonPath('count', 1);

        $this->import('ekskul', [[
            'nisn' => '0000000001',
            'nama' => 'Nama Sama',
            'kelas' => 'X-1',
            'ekskul' => 'Pramuka',
        ]])->assertOk()->assertJsonPath('count', 1);

        $guru = $this->buatAkunGuru('guru-tanpa-akses');
        $this->actingAs($guru)->withoutMiddleware()->postJson(route('guru.import.save-batch'), [
            'type' => 'siswa',
            'rows' => [['nama' => 'Nama Baru']],
        ])->assertForbidden();
    }

    public function test_legacy_teacher_import_routes_redirect_and_grade_routes_check_assignments(): void
    {
        $guru = $this->buatAkunGuru('guru-rute');
        $this->tugaskanMapel($guru, 1, 1, 1);
        $this->tugaskanWali($guru, 1, 1);

        $this->actingAs($guru)->withoutMiddleware()
            ->get(route('guru.import-nilai'))
            ->assertRedirect(route('guru.import-data', ['tab' => 'nilai']));
        $this->get(route('guru.import-ekskul'))
            ->assertRedirect(route('guru.import-data', ['tab' => 'ekskul']));
        $this->get(route('guru.import-ketidakhadiran'))
            ->assertRedirect(route('guru.import-data', ['tab' => 'kehadiran']));
        $this->get(route('guru.input-nilai-mapel', ['kelas' => 'XI-1']))->assertForbidden();

        $this->post(route('guru.import-nilai.store'), ['kelas' => 'XI-1', 'mata_pelajaran' => 'Matematika'])
            ->assertRedirect(route('guru.import-data', ['tab' => 'nilai']));
        $this->post(route('guru.import-ekskul.store'), ['kelas' => 'XI-1'])
            ->assertRedirect(route('guru.import-data', ['tab' => 'ekskul']));
        $this->post(route('guru.import-ketidakhadiran.store'), ['kelas' => 'XI-1'])
            ->assertRedirect(route('guru.import-data', ['tab' => 'kehadiran']));
        $this->post(route('guru.input-nilai-mapel.store'), [
            'kelas' => 'XI-1',
            'mapel_id' => 1,
            'semester' => 'Ganjil',
        ])->assertForbidden();
        $this->post(route('guru.input-nilai-mapel.store'), [
            'kelas' => 'X-1',
            'mapel_id' => 2,
            'semester' => 'Ganjil',
        ])
            ->assertForbidden();

        $this->post(route('guru.input-nilai-mapel.store'), [
            'kelas' => 'X-1',
            'mapel_id' => 1,
            'semester' => 'Ganjil',
        ])->assertRedirect();

        $this->postJson(route('guru.import.metadata'), ['type' => 'siswa'])->assertForbidden();
        $this->postJson(route('guru.import.metadata'), ['type' => 'guru'])->assertForbidden();
    }

    public function test_teacher_without_assignments_cannot_open_or_start_any_import(): void
    {
        $guru = $this->buatAkunGuru('guru-tanpa-penugasan');
        $this->actingAs($guru)->withoutMiddleware();

        $this->get(route('guru.import-data'))->assertForbidden();
        $this->get(route('guru.input-nilai-mapel'))->assertForbidden();
        foreach (['guru.import-nilai', 'guru.import-ekskul', 'guru.import-ketidakhadiran'] as $routeName) {
            $this->followingRedirects()->get(route($routeName))->assertForbidden();
        }

        foreach (['nilai', 'kehadiran', 'ekskul', 'siswa', 'guru'] as $type) {
            $this->postJson(route('guru.import.metadata'), ['type' => $type])->assertForbidden();
            $this->postJson(route('guru.import.start-batch'), [
                'type' => $type,
                'total_rows' => 1,
            ])->assertForbidden();
            $this->postJson(route('guru.import.save-batch'), [
                'type' => $type,
                'rows' => [['kelas' => 'X-1']],
            ])->assertForbidden();
        }
    }

    public function test_admin_import_page_offers_all_import_types(): void
    {
        $response = $this->actingAs($this->admin)->withoutMiddleware()->get(route('admin.import'));

        $response->assertOk()
            ->assertViewHas('allowedTypes', ['siswa', 'guru', 'nilai', 'kehadiran', 'ekskul'])
            ->assertViewHas('type', 'siswa')
            ->assertViewHas('tab', 'siswa')
            ->assertSee('Import Data')
            ->assertSee('Dashboard')
            ->assertSee('Data Siswa')
            ->assertDontSee('Form Pendataan')
            ->assertSee('Guru &amp; Master', false)
            ->assertSee('Kenaikan &amp; Mutasi', false)
            ->assertSee('Alumni')
            ->assertSee('Laporan')
            ->assertDontSee('Cek Kualitas Data')
            ->assertSee('Setting')
            ->assertDontSee('Log Aktivitas')
            ->assertDontSee('Tempat Sampah')
            ->assertSee(route('admin.import', ['tab' => 'nilai']), false)
            ->assertSee(route('admin.import.template', ['jenis' => 'siswa']), false);
        $this->assertSame(8, substr_count($response->getContent(), 'data-menu="'));
        preg_match_all('/data-menu="([^"]+)"/', $response->getContent(), $menuMatches);
        $this->assertSame([
            'dashboard',
            'siswa',
            'import-data',
            'kenaikan-mutasi',
            'alumni',
            'guru-master',
            'laporan',
            'setting',
        ], $menuMatches[1]);
        $this->assertSame(1, substr_count($response->getContent(), 'data-menu="guru-master"'));
        $this->assertStringNotContainsString('data-menu="data-guru"', $response->getContent());
        $this->assertStringNotContainsString('data-menu="master"', $response->getContent());
        $this->assertStringNotContainsString('data-menu="cek"', $response->getContent());
        $this->assertSame(1, substr_count($response->getContent(), 'data-menu="alumni"'));
        $this->assertSame(1, substr_count($response->getContent(), 'data-menu="kenaikan-mutasi"'));
        $this->assertStringNotContainsString('data-menu="mutasi"', $response->getContent());
        $this->assertStringNotContainsString('data-menu="pelacakan"', $response->getContent());
        $this->assertSame(1, substr_count($response->getContent(), 'data-menu="setting"'));
        $this->assertStringNotContainsString('data-menu-group=', $response->getContent());
        $this->assertStringNotContainsString('josbook-sidebar-subnav', $response->getContent());
        foreach ([
            'admin.dashboard',
            'admin.data-siswa',
            'admin.guru-master',
            'admin.kenaikan-mutasi',
            'admin.alumni',
            'admin.laporan',
            'admin.setting',
        ] as $routeName) {
            $this->assertStringContainsString(route($routeName), $response->getContent());
        }

        $academicImport = $this->get(route('admin.import', ['tab' => 'nilai']))
            ->assertOk()
            ->assertViewHas('type', 'nilai')
            ->assertViewHas('tab', 'nilai')
            ->assertSee('Data hasil impor 2025/2026')
            ->assertSee('name="tab" value="nilai"', false)
            ->assertSee('loadDemoNilai')
            ->assertDontSee('loadDemoHadir')
            ->assertDontSee('Jumlah Siswa');
        $this->assertMatchesRegularExpression('/class="[^"]*\bon\b[^"]*" data-menu="import-data"/', $academicImport->getContent());
    }

    public function test_importing_teacher_biodata_does_not_create_an_account_with_a_default_password(): void
    {
        $response = $this->import('guru', [[
            'nama' => 'Guru Import Uji',
            'nip' => '1987000001',
            'email' => 'guru-import@example.test',
        ]]);

        $response->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonPath('message', 'Berhasil mengimpor 1 data guru. Akun login dapat dibuat terpisah melalui menu Guru & Master.');

        $this->assertDatabaseHas('guru_biodata', [
            'nama' => 'Guru Import Uji',
            'nip' => '1987000001',
        ]);
        $this->assertDatabaseMissing('akun_pengguna', [
            'username' => '1987000001',
        ]);
    }

    public function test_legacy_admin_import_url_redirects_to_the_selected_tab(): void
    {
        $this->actingAs($this->admin)->withoutMiddleware();

        foreach (['siswa', 'guru', 'nilai', 'kehadiran', 'ekskul'] as $type) {
            $this->get(route('admin.import.'.$type))
                ->assertRedirect(route('admin.import', ['tab' => $type]));
        }
    }

    public function test_legacy_import_data_query_parameter_redirects_to_tab(): void
    {
        $this->actingAs($this->admin)->withoutMiddleware()
            ->get(route('admin.import-data', ['jenis' => 'nilai']))
            ->assertRedirect(route('admin.import', ['tab' => 'nilai']));

        $this->get(route('admin.import', ['jenis' => 'nilai']))
            ->assertRedirect(route('admin.import', ['tab' => 'nilai']));
    }

    public function test_legacy_teacher_import_urls_redirect_to_their_shared_tabs(): void
    {
        $guru = $this->buatAkunGuru('guru-legacy-import');
        $this->tugaskanMapel($guru, 1, 1, 1);
        $this->tugaskanWali($guru, 1, 1);

        $this->actingAs($guru)->withoutMiddleware();

        foreach ([
            'guru.import-nilai' => 'nilai',
            'guru.import-ketidakhadiran' => 'kehadiran',
            'guru.import-ekskul' => 'ekskul',
        ] as $routeName => $tab) {
            $this->get(route($routeName))
                ->assertRedirect(route('guru.import-data', ['tab' => $tab]));
        }
    }

    public function test_teacher_import_page_exposes_only_assigned_import_types(): void
    {
        $guruMapel = $this->buatAkunGuru('guru-tab-mapel');
        $this->tugaskanMapel($guruMapel, 1, 1, 1);
        $guruWali = $this->buatAkunGuru('guru-tab-wali');
        $this->tugaskanWali($guruWali, 1, 1);
        $guruRangkap = $this->buatAkunGuru('guru-tab-rangkap');
        $this->tugaskanMapel($guruRangkap, 1, 1, 1);
        $this->tugaskanWali($guruRangkap, 1, 1);

        $this->actingAs($guruMapel)->withoutMiddleware()
            ->get(route('guru.import-data'))
            ->assertOk()
            ->assertViewHas('allowedTypes', ['nilai'])
            ->assertViewHas('kelasList', fn ($kelasList): bool => $kelasList->all() === ['X-1'])
            ->assertSee('Import Nilai')
            ->assertSee('Riwayat Import Nilai Saya')
            ->assertDontSee('Import Kehadiran')
            ->assertDontSee('Import Ekskul')
            ->assertDontSee('Kelas X (tingkat)');

        $this->actingAs($guruWali)
            ->get(route('guru.import-data'))
            ->assertOk()
            ->assertViewHas('allowedTypes', ['kehadiran', 'ekskul'])
            ->assertViewHas('kelasList', fn ($kelasList): bool => $kelasList->all() === ['X-1'])
            ->assertDontSee('Import Nilai')
            ->assertSee('Import Kehadiran')
            ->assertSee('Import Ekskul');

        $this->actingAs($guruRangkap)
            ->get(route('guru.import-data'))
            ->assertOk()
            ->assertViewHas('allowedTypes', ['nilai', 'kehadiran', 'ekskul'])
            ->assertSee('Input Nilai Mapel');
    }

    public function test_teacher_cannot_open_or_download_unassigned_import_type(): void
    {
        $guru = $this->buatAkunGuru('guru-tab-terbatas');
        $this->tugaskanMapel($guru, 1, 1, 1);
        $this->actingAs($guru)->withoutMiddleware();

        $this->get(route('guru.import-data', ['tab' => 'ekskul']))->assertForbidden();
        $this->get(route('guru.import-data', ['jenis' => 'ekskul']))->assertForbidden();
        $this->get(route('guru.import-data.template', ['jenis' => 'siswa']))->assertForbidden();
        $this->get(route('guru.import-data.template', ['jenis' => 'nilai']))->assertOk();
    }

    public function test_subject_teacher_cannot_use_homeroom_imports_and_homeroom_teacher_cannot_use_grade_imports(): void
    {
        $guruMapel = $this->buatAkunGuru('guru-mapel-saja');
        $this->tugaskanMapel($guruMapel, 1, 1, 1);
        $guruWali = $this->buatAkunGuru('guru-wali-saja');
        $this->tugaskanWali($guruWali, 1, 1);

        $this->actingAs($guruMapel)->withoutMiddleware();
        $this->get(route('guru.input-nilai-mapel'))->assertOk();
        $this->get(route('guru.import-nilai'))
            ->assertRedirect(route('guru.import-data', ['tab' => 'nilai']));
        $this->followingRedirects()->get(route('guru.import-ekskul'))->assertForbidden();
        $this->followingRedirects()->get(route('guru.import-ketidakhadiran'))->assertForbidden();
        $this->followingRedirects()->post(route('guru.import-ekskul.store'), ['kelas' => 'X-1'])->assertForbidden();
        $this->followingRedirects()->post(route('guru.import-ketidakhadiran.store'), ['kelas' => 'X-1'])->assertForbidden();

        $this->actingAs($guruWali);
        $this->get(route('guru.input-nilai-mapel'))->assertForbidden();
        $this->followingRedirects()->get(route('guru.import-nilai'))->assertForbidden();
        $this->get(route('guru.import-ekskul'))
            ->assertRedirect(route('guru.import-data', ['tab' => 'ekskul']));
        $this->get(route('guru.import-ketidakhadiran'))
            ->assertRedirect(route('guru.import-data', ['tab' => 'kehadiran']));
        $this->followingRedirects()->post(route('guru.import-nilai.store'), [
            'kelas' => 'X-1',
            'mata_pelajaran' => 'Matematika',
        ])->assertForbidden();
    }

    #[DataProvider('unauthorizedTeacherImportTypes')]
    public function test_teacher_cannot_save_batch_for_import_types_outside_role(string $assignment, string $type): void
    {
        $guru = $this->buatAkunGuru('guru-tolak-'.$type);

        if ($assignment === 'wali') {
            $this->tugaskanWali($guru, 1, 1);
        } else {
            $this->tugaskanMapel($guru, 1, 1, 1);
        }

        $this->actingAs($guru)->withoutMiddleware()
            ->postJson(route('guru.import.save-batch'), [
                'type' => $type,
                'rows' => [['kelas' => 'X-1']],
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('import_unggahan', 0);
        $this->assertDatabaseCount('siswa_nilai', 0);
        $this->assertDatabaseCount('siswa_kehadiran', 0);
        $this->assertDatabaseCount('siswa_ekskul', 0);
    }

    public static function unauthorizedTeacherImportTypes(): array
    {
        return [
            'wali kelas cannot import grades' => ['wali', 'nilai'],
            'subject teacher cannot import student biodata' => ['mapel', 'siswa'],
            'subject teacher cannot import attendance' => ['mapel', 'kehadiran'],
            'subject teacher cannot import extracurriculars' => ['mapel', 'ekskul'],
        ];
    }

    public function test_teacher_can_input_grade_for_active_student_and_activity_is_logged(): void
    {
        $guru = $this->buatAkunGuru('guru-input-nilai');
        $this->tugaskanMapel($guru, 1, 1, 1);

        $response = $this->actingAs($guru)->withoutMiddleware()
            ->post(route('guru.input-nilai-mapel.store'), [
                'kelas' => 'X-1',
                'mapel_id' => 1,
                'semester' => 'Ganjil',
                'nilai' => [1 => '87.5'],
            ]);

        $response->assertRedirect(route('guru.input-nilai-mapel', [
            'kelas' => 'X-1',
            'mapel_id' => 1,
            'semester' => 'Ganjil',
        ]));
        $this->assertSame(1, DB::table('siswa_nilai')->count());
        $this->assertSame(87.5, (float) DB::table('siswa_nilai')->value('nilai_angka'));
        $this->assertSame('input_nilai_mapel', DB::table('catatan_aktivitas')->value('aksi'));
        $this->assertSame($guru->id, DB::table('catatan_aktivitas')->value('pengguna_id'));
    }

    public function test_teacher_grade_input_rejects_out_of_range_values_and_unassigned_class_or_subject(): void
    {
        $guru = $this->buatAkunGuru('guru-nilai-terbatas');
        $this->tugaskanMapel($guru, 1, 1, 1);
        $this->actingAs($guru);

        $outOfRangeResponse = $this->from(route('guru.input-nilai-mapel', ['kelas' => 'X-1', 'mapel_id' => 1]))
            ->post(route('guru.input-nilai-mapel.store'), [
                'kelas' => 'X-1',
                'mapel_id' => 1,
                'semester' => 'Ganjil',
                'nilai' => [1 => '101'],
            ]);
        $outOfRangeResponse->assertSessionHasErrors(['nilai.1']);

        $this->post(route('guru.input-nilai-mapel.store'), [
            'kelas' => 'X-1',
            'mapel_id' => 2,
            'semester' => 'Ganjil',
            'nilai' => [1 => '80'],
        ])->assertForbidden();

        $this->post(route('guru.input-nilai-mapel.store'), [
            'kelas' => 'XI-1',
            'mapel_id' => 1,
            'semester' => 'Ganjil',
            'nilai' => [1 => '80'],
        ])->assertForbidden();

        $this->assertSame(0, DB::table('siswa_nilai')->count());
        $this->assertSame(0, DB::table('catatan_aktivitas')->count());
    }

    public function test_teacher_cannot_submit_old_year_or_historical_student_to_grade_input(): void
    {
        DB::table('sekolah_tahun_ajaran')->insert([
            'id' => 2,
            'nama' => '2024/2025',
            'sedang_berjalan' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('siswa_riwayat_kelas')->insert([
            'id' => 3,
            'siswa_id' => 1,
            'tahun_ajaran_id' => 2,
            'kelas_id' => 2,
            'status' => 'Lulus',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $teacher = $this->buatAkunGuru('old-year-grade-teacher');
        $this->tugaskanMapel($teacher, 1, 1, 1);
        $this->actingAs($teacher)->withoutMiddleware()
            ->post(route('guru.input-nilai-mapel.store'), [
                'kelas' => 'X-1',
                'mapel_id' => 1,
                'semester' => 'Ganjil',
                'tahun_ajaran_id' => 2,
                'nilai' => [1 => '87'],
            ])
            ->assertForbidden();

        $this->post(route('guru.input-nilai-mapel.store'), [
            'kelas' => 'X-1',
            'mapel_id' => 1,
            'semester' => 'Ganjil',
            'nilai' => [3 => '87'],
        ])->assertForbidden();

        $this->assertSame(0, DB::table('siswa_nilai')->count());
        $this->assertSame(0, DB::table('catatan_aktivitas')->count());
    }

    public function test_saving_grade_again_updates_existing_row_and_empty_input_does_not_delete_it(): void
    {
        $guru = $this->buatAkunGuru('guru-update-nilai');
        $this->tugaskanMapel($guru, 1, 1, 1);
        $this->actingAs($guru)->withoutMiddleware();

        $dataNilai = [
            'kelas' => 'X-1',
            'mapel_id' => 1,
            'semester' => 'Genap',
            'nilai' => [1 => '76'],
        ];
        $this->post(route('guru.input-nilai-mapel.store'), $dataNilai)->assertRedirect();
        $this->post(route('guru.input-nilai-mapel.store'), array_replace_recursive($dataNilai, [
            'nilai' => [1 => '91.25'],
        ]))->assertRedirect();

        $this->assertSame(1, DB::table('siswa_nilai')->count());
        $this->assertSame(91.25, (float) DB::table('siswa_nilai')->value('nilai_angka'));

        $this->post(route('guru.input-nilai-mapel.store'), array_replace_recursive($dataNilai, [
            'nilai' => [1 => ''],
        ]))->assertRedirect();
        $this->assertSame(1, DB::table('siswa_nilai')->count());
        $this->assertSame(91.25, (float) DB::table('siswa_nilai')->value('nilai_angka'));
    }

    public function test_input_grade_page_loads_only_current_active_class_students_and_existing_grade(): void
    {
        $guru = $this->buatAkunGuru('guru-daftar-nilai');
        $this->tugaskanMapel($guru, 1, 1, 1);
        DB::table('siswa_riwayat_kelas')->where('id', 2)->update(['status' => 'Lulus']);
        DB::table('siswa_nilai')->insert([
            'riwayat_kelas_id' => 1,
            'semester' => 'Ganjil',
            'mapel_id' => 1,
            'nilai_angka' => 83.5,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($guru)->withoutMiddleware()->get(route('guru.input-nilai-mapel', [
            'kelas' => 'X-1',
            'semester' => 'Ganjil',
        ]));

        $response->assertOk()
            ->assertSee('Matematika')
            ->assertSee('1 Siswa Aktif')
            ->assertSee('Nama Sama')
            ->assertSee('0000000001')
            ->assertSee('value="83.5"', false)
            ->assertDontSee('0000000002')
            ->assertViewHas('siswa', fn ($siswa): bool => $siswa->count() === 1);
    }

    public function test_input_grade_page_excludes_a_student_marked_as_graduated_even_if_history_is_active(): void
    {
        $guru = $this->buatAkunGuru('guru-lulus-nilai');
        $this->tugaskanMapel($guru, 1, 1, 1);
        DB::table('siswa_biodata')->where('id', 1)->update(['tahun_lulus' => '2026']);
        DB::table('siswa_riwayat_kelas')->where('id', 2)->update(['status' => 'Lulus']);

        $response = $this->actingAs($guru)->withoutMiddleware()->get(route('guru.input-nilai-mapel', [
            'kelas' => 'X-1',
            'semester' => 'Ganjil',
        ]));

        $response->assertOk()
            ->assertViewHas('siswa', fn ($siswa): bool => $siswa->isEmpty());
    }

    public function test_admin_column_correction_is_learned_and_reused_for_the_next_import(): void
    {
        $correction = [
            'name' => 'Nilai',
            'header_row' => 1,
            'headers' => ['NISN', 'Nama Lengkap', 'Kelas', 'Nilai Tambahan'],
            'mapping' => [0 => 'nisn', 1 => 'nama', 2 => 'kelas', 3 => 'mtk'],
            'corrected' => [3 => 'mtk'],
        ];
        $start = $this->actingAs($this->admin)->withoutMiddleware()->postJson(route('admin.import.start-batch'), [
            'type' => 'nilai',
            'file_name' => 'nilai-tambahan.xlsx',
            'total_rows' => 1,
            'tahun_ajaran' => '2025/2026',
            'semester' => 'Ganjil',
            'kelas' => 'X-1',
            'mapping_corrections' => [$correction],
        ]);
        $start->assertCreated()->assertJsonPath('total_rows', 1);
        $batchId = $start->json('batch_id');

        $this->postJson(route('admin.import.save-batch'), [
            'batch_id' => $batchId,
            'type' => 'nilai',
            'tahun_ajaran' => '2025/2026',
            'semester' => 'Ganjil',
            'mode' => 'semua',
            'rows' => [[
                'nisn' => '0000000001',
                'nama' => 'Nama Sama',
                'kelas' => 'X-1',
                'mtk' => 87,
                '__excel_row' => 2,
            ]],
            'final_chunk' => true,
        ])->assertOk()->assertJsonPath('count', 1);

        $metadata = $this->postJson(route('admin.import.metadata'), [
            'type' => 'nilai',
            'sheets' => [[
                'name' => 'Nilai',
                'headers' => $correction['headers'],
            ]],
        ]);

        $metadata->assertOk()
            ->assertJsonPath('dictionaries.columns.nilai tambahan', 'mtk')
            ->assertJsonPath('formats.0.mapping.3', 'mtk');
        $this->assertDatabaseHas('import_kamus_kolom', [
            'jenis' => 'nilai',
            'judul_di_excel' => 'nilai tambahan',
            'kolom_tujuan' => 'mtk',
            'asal' => 'admin',
            'dibuat_oleh' => $this->admin->id,
        ]);
        $this->assertDatabaseHas('import_unggahan', [
            'id' => $batchId,
            'format_tersimpan_id' => 1,
            'jumlah_baris' => 1,
            'jumlah_tersimpan' => 1,
            'status' => 'selesai',
        ]);
    }

    public function test_student_import_normalizes_excel_values_and_records_date_warnings(): void
    {
        $excelDate = (int) ((strtotime('2008-05-12 00:00:00 UTC') - strtotime('1899-12-30 00:00:00 UTC')) / 86400);
        $response = $this->import('siswa', [
            [
                'nisn' => 1234567890,
                'nis' => '1.003e3',
                'nama' => '  Siswa   Masa Depan  ',
                'kelas' => 'X-1',
                'tgl' => '01/01/2999',
                'tgl_masuk' => '12 Mei 2008',
                'tgl_ijazah_smp' => $excelDate,
                'tgl_keluar' => '01-01-2024',
                'alamat' => '-',
                'tmp' => 'X',
                'agama' => 'N/A',
                'kewarganegaraan' => '   ',
                '__excel_row' => 2,
            ],
            [
                'nisn' => 9876543210,
                'nama' => 'Siswa Usia Tidak Wajar',
                'kelas' => 'X-1',
                'tgl' => '01-01-1800',
                '__excel_row' => 3,
            ],
        ]);

        $response->assertOk()->assertJsonPath('count', 2);

        $student = DB::table('siswa_biodata')->where('nisn', '1234567890')->first();
        $this->assertNotNull($student);
        $this->assertSame('1003', $student->nomor_induk);
        $this->assertSame('Siswa Masa Depan', $student->nama_lengkap);
        $this->assertSame('2999-01-01', $student->tanggal_lahir);
        $this->assertSame('2008-05-12', $student->tanggal_diterima);
        $this->assertSame('2008-05-12', $student->tanggal_ijazah_smp);
        $this->assertSame('2024-01-01', $student->tanggal_keluar);
        $this->assertNull($student->alamat_jalan);
        $this->assertNull($student->tempat_lahir);
        $this->assertNull($student->agama);
        $this->assertNull($student->kewarganegaraan);

        $this->assertDatabaseHas('siswa_biodata', [
            'nisn' => '9876543210',
            'tanggal_lahir' => '1800-01-01',
        ]);
        $this->assertDatabaseHas('import_unggahan', [
            'jumlah_baris' => 2,
            'jumlah_tersimpan' => 2,
            'jumlah_peringatan' => 2,
            'status' => 'selesai',
        ]);
        $this->assertSame(2, DB::table('import_baris')->where('status', 'peringatan')->count());
    }

    public function test_student_import_rejects_nisn_that_is_not_exactly_ten_digits(): void
    {
        $response = $this->import('siswa', [[
            'nisn' => '123456789',
            'nama' => 'Siswa NISN Tidak Valid',
            'kelas' => 'X-1',
        ]]);

        $response->assertOk()
            ->assertJsonPath('count', 0)
            ->assertJsonPath('skipped', 1)
            ->assertJsonFragment(['reason' => 'NISN wajib terdiri dari tepat 10 digit.']);
        $this->assertDatabaseMissing('siswa_biodata', ['nama_lengkap' => 'Siswa NISN Tidak Valid']);
    }

    public function test_existing_student_duplicate_modes_preserve_history_and_apply_requested_biodata_policy(): void
    {
        $skippedUpdate = $this->import('siswa', [[
            'nisn' => '0000000001',
            'nis' => '1001',
            'nama' => 'Nama Sama',
            'alamat' => 'Alamat Diabaikan',
            'kelas' => 'XI-1',
        ]], 'Ganjil', '2024/2025', 'skip');

        $skippedUpdate->assertOk()->assertJsonPath('count', 1)->assertJsonPath('skipped', 0);
        $this->assertDatabaseHas('siswa_biodata', [
            'id' => 1,
            'nama_lengkap' => 'Nama Sama',
            'alamat_jalan' => null,
        ]);
        $this->assertDatabaseHas('siswa_riwayat_kelas', [
            'siswa_id' => 1,
            'tahun_ajaran_id' => DB::table('sekolah_tahun_ajaran')->where('nama', '2024/2025')->value('id'),
            'kelas_id' => 2,
        ]);

        $completedUpdate = $this->import('siswa', [[
            'nisn' => '0000000002',
            'nis' => '1002',
            'nama' => 'Nama Sama',
            'alamat' => 'Alamat Baru',
            'kelas' => 'XI-1',
        ]], 'Ganjil', '2024/2025', 'lengkapi');

        $completedUpdate->assertOk()->assertJsonPath('count', 1);
        $this->assertDatabaseHas('siswa_biodata', [
            'id' => 2,
            'nama_lengkap' => 'Nama Sama',
            'alamat_jalan' => 'Alamat Baru',
        ]);

        $overwrittenUpdate = $this->import('siswa', [[
            'nisn' => '0000000001',
            'nis' => '1001',
            'nama' => 'Nama Sama',
            'alamat' => 'Alamat Ditimpa',
            'kelas' => 'XI-1',
        ]], 'Ganjil', '2024/2025', 'timpa');

        $overwrittenUpdate->assertOk()->assertJsonPath('count', 1);
        $this->assertDatabaseHas('siswa_biodata', [
            'id' => 1,
            'nama_lengkap' => 'Nama Sama',
            'alamat_jalan' => 'Alamat Ditimpa',
        ]);
    }

    public function test_student_duplicate_mode_defaults_to_filling_empty_biodata_fields(): void
    {
        $this->import('siswa', [[
            'nisn' => '0000000001',
            'nis' => '1001',
            'nama' => 'Nama Sama',
            'alamat' => 'Alamat Pelengkap',
            'kelas' => 'X-1',
        ]])->assertOk()->assertJsonPath('count', 1);

        $this->assertDatabaseHas('siswa_biodata', [
            'id' => 1,
            'nama_lengkap' => 'Nama Sama',
            'alamat_jalan' => 'Alamat Pelengkap',
        ]);
        $this->actingAs($this->admin)->withoutMiddleware()
            ->get(route('admin.import', ['tab' => 'siswa']))
            ->assertOk()
            ->assertSee('<option value="lengkapi" selected>', false);
    }

    public function test_student_import_rejects_unsupported_duplicate_mode(): void
    {
        $this->import('siswa', [[
            'nisn' => '0000000360',
            'nama' => 'Siswa Uji Mode',
            'kelas' => 'X-1',
        ]], 'Ganjil', '2025/2026', 'replace')
            ->assertUnprocessable();

        $this->assertDatabaseCount('import_unggahan', 0);
    }

    public function test_student_import_matches_by_nis_and_rejects_conflicting_identifiers(): void
    {
        $response = $this->import('siswa', [[
            'nisn' => '0000000001',
            'nis' => '1002',
            'nama' => 'Nama Sama',
            'kelas' => 'X-1',
        ]]);

        $response->assertOk()
            ->assertJsonPath('count', 0)
            ->assertJsonPath('skipped', 1)
            ->assertJsonFragment(['reason' => 'NISN dan NIS mengarah ke siswa berbeda; baris ini dilewati.']);
        $this->assertDatabaseCount('siswa_biodata', 2);
    }

    public function test_student_import_uses_academic_year_inference_before_current_year(): void
    {
        $response = $this->import('siswa', [[
            'nisn' => '0000000320',
            'nama' => 'Siswa Masuk Lama',
            'kelas' => 'XI-1',
            'tgl_masuk' => '2022-05-01',
        ]], 'Ganjil', '');

        $response->assertOk()->assertJsonPath('count', 1);
        $history = DB::table('siswa_riwayat_kelas')
            ->where('siswa_id', DB::table('siswa_biodata')->where('nisn', '0000000320')->value('id'))
            ->first();
        $this->assertSame('2023/2024', DB::table('sekolah_tahun_ajaran')->where('id', $history->tahun_ajaran_id)->value('nama'));
        $this->assertFalse((bool) DB::table('sekolah_tahun_ajaran')->where('id', $history->tahun_ajaran_id)->value('sedang_berjalan'));
    }

    public function test_student_import_adds_multiple_year_histories_and_rejects_conflicts_or_a_fourth_history(): void
    {
        $studentRow = [
            'nisn' => '0000000330',
            'nama' => 'Siswa Tiga Tahun',
            'kelas' => 'X-1',
        ];
        $this->import('siswa', [$studentRow], 'Ganjil', '2023/2024')
            ->assertOk()->assertJsonPath('count', 1);

        $duplicateYear = $this->import('siswa', [array_merge($studentRow, ['kelas' => 'XI-1'])], 'Ganjil', '2023/2024');
        $duplicateYear->assertOk()
            ->assertJsonPath('count', 0)
            ->assertJsonFragment(['reason' => 'Riwayat untuk tahun ajaran ini sudah ada pada kelas berbeda; kelas lama tidak diubah.']);

        $this->import('siswa', [array_merge($studentRow, ['kelas' => 'X-1'])], 'Ganjil', '2024/2025')
            ->assertOk()->assertJsonPath('count', 1);
        $this->import('siswa', [array_merge($studentRow, ['kelas' => 'XI-1'])], 'Ganjil', '2025/2026')
            ->assertOk()->assertJsonPath('count', 1);

        $fourthHistory = $this->import('siswa', [array_merge($studentRow, ['kelas' => 'XII-1'])], 'Ganjil', '2026/2027');
        $fourthHistory->assertOk()
            ->assertJsonPath('count', 0)
            ->assertJsonFragment(['reason' => 'Siswa sudah memiliki 3 riwayat kelas; riwayat ke-4 tidak dapat dibuat.']);

        $studentId = DB::table('siswa_biodata')->where('nisn', '0000000330')->value('id');
        $this->assertSame(3, DB::table('siswa_riwayat_kelas')->where('siswa_id', $studentId)->count());

        $createYearId = DB::table('sekolah_tahun_ajaran')->where('nama', '2026/2027')->value('id');
        if ($createYearId === null) {
            $createYearId = DB::table('sekolah_tahun_ajaran')->insertGetId([
                'nama' => '2026/2027',
                'sedang_berjalan' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $createClassId = DB::table('sekolah_kelas')->where('nama', 'XII-1')->value('id');
        if ($createClassId === null) {
            $createClassId = DB::table('sekolah_kelas')->insertGetId([
                'nama' => 'XII-1',
                'tingkat' => 'XII',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $fourthAcademicHistory = $this->import('nilai', [[
            'nisn' => '0000000330',
            'nama' => 'Siswa Tiga Tahun',
            'kelas' => 'XII-1',
            'mtk' => 88,
        ]], 'Ganjil', '2026/2027', targetOptions: [
            'create_riwayat' => true,
            'create_tahun_ajaran_id' => $createYearId,
            'create_kelas_id' => $createClassId,
        ]);
        $fourthAcademicHistory->assertOk()
            ->assertJsonPath('count', 0)
            ->assertJsonFragment(['reason' => 'Siswa sudah memiliki 3 riwayat kelas; riwayat ke-4 tidak dapat dibuat.']);
        $this->assertSame(3, DB::table('siswa_riwayat_kelas')->where('siswa_id', $studentId)->count());
    }

    public function test_graduated_student_import_uses_final_grade_year_and_routes_to_alumni(): void
    {
        $response = $this->import('siswa', [[
            'nisn' => '0000000340',
            'nama' => 'Siswa Alumni Tahun',
            'kelas' => 'XII-1',
            'status' => 'Lulus',
            'tahun_lulus' => '2025',
        ]], 'Ganjil', '');

        $response->assertOk()->assertJsonPath('count', 1)->assertJsonPath('graduated_count', 1);
        $studentId = (int) DB::table('siswa_biodata')->where('nisn', '0000000340')->value('id');
        $history = DB::table('siswa_riwayat_kelas')->where('siswa_id', $studentId)->first();
        $this->assertNotNull($history);
        $this->assertSame('2024/2025', DB::table('sekolah_tahun_ajaran')->where('id', $history->tahun_ajaran_id)->value('nama'));
        $this->assertSame('Lulus', $history->status);
        $this->assertDatabaseHas('siswa_biodata', [
            'id' => $studentId,
            'tahun_lulus' => '2025',
            'alasan_keluar' => 'Lulus',
        ]);

        $alumni = app(AlumniController::class)->index(Request::create('/admin/alumni'));
        $activeStudents = app(DataSiswaController::class)->index(Request::create('/admin/data-siswa'));
        $this->assertContains($studentId, $alumni->getData()['alumni']->getCollection()->pluck('id')->all());
        $this->assertNotContains($studentId, $activeStudents->getData()['siswaList']->getCollection()->pluck('id')->all());
    }

    public function test_active_student_without_class_is_not_saved_as_invisible_biodata(): void
    {
        $response = $this->import('siswa', [[
            'nisn' => '0000000341',
            'nama' => 'Siswa Aktif Tanpa Kelas',
        ]], 'Ganjil', '');

        $response->assertOk()
            ->assertJsonPath('count', 0)
            ->assertJsonPath('skipped', 1)
            ->assertJsonPath('errors.0.reason', 'Siswa aktif wajib memiliki kelas dan tahun ajaran agar tampil di Data Siswa.');
        $this->assertDatabaseMissing('siswa_biodata', ['nisn' => '0000000341']);
    }

    public function test_graduated_student_without_class_is_still_saved_and_listed_as_alumni(): void
    {
        $response = $this->import('siswa', [[
            'nisn' => '0000000342',
            'nama' => 'Siswa Alumni Tanpa Kelas',
            'status' => 'Lulus',
            'tahun_lulus' => '2025',
        ]], 'Ganjil', '2024/2025');

        $response->assertOk()->assertJsonPath('count', 1)->assertJsonPath('graduated_count', 1);
        $studentId = (int) DB::table('siswa_biodata')->where('nisn', '0000000342')->value('id');
        $alumni = app(AlumniController::class)->index(Request::create('/admin/alumni'));
        $activeStudents = app(DataSiswaController::class)->index(Request::create('/admin/data-siswa'));

        $this->assertNotNull($studentId);
        $this->assertContains($studentId, $alumni->getData()['alumni']->getCollection()->pluck('id')->all());
        $this->assertNotContains($studentId, $activeStudents->getData()['siswaList']->getCollection()->pluck('id')->all());
    }

    public function test_alumni_name_search_matches_only_the_start_of_the_first_name(): void
    {
        DB::table('siswa_biodata')->insert([
            [
                'nisn' => '0000000343',
                'nomor_induk' => '10343',
                'nama_lengkap' => 'Ayu Anggraini',
                'tahun_lulus' => '2025',
                'alasan_keluar' => 'Lulus',
            ],
            [
                'nisn' => '0000000344',
                'nomor_induk' => '10344',
                'nama_lengkap' => 'Dewi Anggraini',
                'tahun_lulus' => '2025',
                'alasan_keluar' => 'Lulus',
            ],
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.alumni', ['aq' => 'A']))
            ->assertOk()
            ->assertSee('Ayu Anggraini')
            ->assertDontSee('Dewi Anggraini');
    }

    public function test_three_year_student_import_renders_a_complete_six_semester_alumni_record(): void
    {
        DB::table('sekolah_mapel_per_tingkat')->insert([
            ['tingkat' => 'X', 'mapel_id' => 1, 'kelompok_mapel' => 'Wajib X', 'urutan' => 1],
            ['tingkat' => 'XI', 'mapel_id' => 1, 'kelompok_mapel' => 'Wajib XI', 'urutan' => 1],
            ['tingkat' => 'XII', 'mapel_id' => 1, 'kelompok_mapel' => 'Wajib XII', 'urutan' => 1],
        ]);

        $studentRows = [
            [
                'tahun_ajaran' => '2023/2024',
                'kelas' => 'X-1',
                'status' => 'Aktif',
            ],
            [
                'tahun_ajaran' => '2024/2025',
                'kelas' => 'XI-1',
                'status' => 'Aktif',
            ],
            [
                'tahun_ajaran' => '2025/2026',
                'kelas' => 'XII-1',
                'status' => 'Lulus',
                'tahun_lulus' => '2026',
                'tgl_keluar' => '2026-05-01',
                'no_skl' => 'SKL-UJI-2026',
                'no_ijazah_sma' => 'IJAZAH-UJI-2026',
                'tgl_ijazah_sma' => '2026-05-15',
                'no_transkrip' => 'TRANSKRIP-UJI-2026',
                'nopes' => 'PSAJ-UJI-2026',
                'lanjut' => 'Kuliah',
            ],
        ];

        foreach ($studentRows as $studentRow) {
            $this->import('siswa', [array_merge([
                'nisn' => '1234567890',
                'nis' => '9001',
                'nama' => 'Siswa Uji Buku Induk',
                'jk' => 'P',
                'tmp' => 'Nganjuk',
                'tgl' => '2010-03-14',
                'agama' => 'Islam',
                'alamat' => 'Jalan Uji Nomor 1',
                'ayah' => 'Ayah Uji',
                'ibu' => 'Ibu Uji',
            ], $studentRow)], 'Ganjil', $studentRow['tahun_ajaran'])
                ->assertOk()
                ->assertJsonPath('count', 1);
        }

        $studentId = (int) DB::table('siswa_biodata')->where('nisn', '1234567890')->value('id');
        $histories = DB::table('siswa_riwayat_kelas')
            ->join('sekolah_tahun_ajaran as tahun_ajaran', 'tahun_ajaran.id', '=', 'siswa_riwayat_kelas.tahun_ajaran_id')
            ->where('siswa_riwayat_kelas.siswa_id', $studentId)
            ->orderBy('tahun_ajaran.nama')
            ->get(['siswa_riwayat_kelas.*', 'tahun_ajaran.nama as tahun_ajaran_nama']);
        $this->assertCount(3, $histories);
        $this->assertSame(['Aktif', 'Aktif', 'Lulus'], $histories->pluck('status')->all());
        $this->assertSame(['2023/2024', '2024/2025', '2025/2026'], $histories->pluck('tahun_ajaran_nama')->all());

        $semesterData = [
            [1, '2023/2024', 'X-1', 'Ganjil'],
            [2, '2023/2024', 'X-1', 'Genap'],
            [3, '2024/2025', 'XI-1', 'Ganjil'],
            [4, '2024/2025', 'XI-1', 'Genap'],
            [5, '2025/2026', 'XII-1', 'Ganjil'],
            [6, '2025/2026', 'XII-1', 'Genap'],
        ];
        foreach ($semesterData as [$semesterNumber, $academicYear, $className, $term]) {
            $baseRow = [
                'nisn' => '1234567890',
                'nama' => 'Siswa Uji Buku Induk',
                'kelas' => $className,
                'sem' => $term,
                '__excel_row' => $semesterNumber + 1,
            ];

            $this->import('nilai', [array_merge($baseRow, ['mtk' => 70 + $semesterNumber])], $term, $academicYear)
                ->assertOk()
                ->assertJsonPath('count', 1);
            $this->import('kehadiran', [array_merge($baseRow, [
                'sakit' => $semesterNumber,
                'izin' => $semesterNumber + 1,
                'alpa' => 0,
                'kokur' => "Kokurikuler Uji Semester {$semesterNumber}",
            ])], $term, $academicYear)
                ->assertOk()
                ->assertJsonPath('count', 1);
            $this->import('ekskul', [array_merge($baseRow, [
                'ekskul' => "Ekstrakurikuler Uji Semester {$semesterNumber}",
                'predikat' => 'A',
            ])], $term, $academicYear)
                ->assertOk()
                ->assertJsonPath('count', 1);
        }

        $this->assertDatabaseCount('siswa_nilai', 6);
        $this->assertDatabaseCount('siswa_kehadiran', 6);
        $this->assertDatabaseCount('siswa_ekskul', 6);
        $this->assertDatabaseHas('siswa_biodata', [
            'id' => $studentId,
            'tahun_lulus' => '2026',
            'alasan_keluar' => 'Lulus',
            'nomor_skl' => 'SKL-UJI-2026',
            'nomor_ijazah_sma' => 'IJAZAH-UJI-2026',
            'nomor_transkrip' => 'TRANSKRIP-UJI-2026',
            'nomor_peserta_psaj' => 'PSAJ-UJI-2026',
        ]);

        $alumni = app(AlumniController::class)->index(Request::create('/admin/alumni'));
        $activeStudents = app(DataSiswaController::class)->index(Request::create('/admin/data-siswa'));
        $this->assertContains($studentId, $alumni->getData()['alumni']->getCollection()->pluck('id')->all());
        $this->assertNotContains($studentId, $activeStudents->getData()['siswaList']->getCollection()->pluck('id')->all());

        $response = $this->actingAs($this->admin)->withoutMiddleware()
            ->get(route('admin.data-siswa.pdf', [
                'id' => $studentId,
                'paper' => 'A4',
            ]));

        $response->assertOk()
            ->assertSee('Siswa Uji Buku Induk')
            ->assertSee('Jalan Uji Nomor 1')
            ->assertSee('SMA NEGERI 1 REJOSO NGANJUK')
            ->assertSee('2023/2024')
            ->assertSee('2024/2025')
            ->assertSee('2025/2026')
            ->assertSee('Kelas X-1')
            ->assertSee('Kelas XI-1')
            ->assertSee('Kelas XII-1')
            ->assertSee('Semester 1')
            ->assertSee('Semester 2')
            ->assertSee('Semester 3')
            ->assertSee('Semester 4')
            ->assertSee('Semester 5')
            ->assertSee('Semester 6')
            ->assertSee('71')
            ->assertSee('72')
            ->assertSee('73')
            ->assertSee('74')
            ->assertSee('75')
            ->assertSee('76')
            ->assertSee('Kokurikuler Uji Semester 1')
            ->assertSee('Kokurikuler Uji Semester 6')
            ->assertSee('Ekstrakurikuler Uji Semester 1')
            ->assertSee('Ekstrakurikuler Uji Semester 6')
            ->assertSee('SKL-UJI-2026')
            ->assertSee('IJAZAH-UJI-2026')
            ->assertSee('TRANSKRIP-UJI-2026')
            ->assertSee('PSAJ-UJI-2026')
            ->assertSee('2026')
            ->assertSee('Kuliah')
            ->assertSee('210mm 297mm');
    }

    public function test_updating_existing_student_to_graduated_updates_import_totals_and_alumni_membership(): void
    {
        $response = $this->import('siswa', [[
            'nisn' => '0000000001',
            'nis' => '1001',
            'nama' => 'Nama Sama',
            'kelas' => 'XII-1',
            'status' => 'Lulus',
            'tahun_lulus' => '2025',
        ]], 'Ganjil', '2024/2025', 'lengkapi');

        $response->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonPath('active_count', 0)
            ->assertJsonPath('graduated_count', 1);
        $this->assertDatabaseHas('siswa_biodata', [
            'id' => 1,
            'tahun_lulus' => '2025',
            'alasan_keluar' => 'Lulus',
        ]);
        $this->assertDatabaseHas('siswa_riwayat_kelas', [
            'siswa_id' => 1,
            'status' => 'Lulus',
            'kelas_id' => DB::table('sekolah_kelas')->where('nama', 'XII-1')->value('id'),
        ]);

        $alumni = app(AlumniController::class)->index(Request::create('/admin/alumni'));
        $activeStudents = app(DataSiswaController::class)->index(Request::create('/admin/data-siswa'));
        $this->assertContains(1, $alumni->getData()['alumni']->getCollection()->pluck('id')->all());
        $this->assertNotContains(1, $activeStudents->getData()['siswaList']->getCollection()->pluck('id')->all());
    }

    public function test_import_batch_error_response_does_not_expose_exception_details(): void
    {
        $dictionary = \Mockery::mock(ImportDictionaryService::class);
        $dictionary->shouldReceive('learnSubjectAlias')
            ->once()
            ->andThrow(new \RuntimeException('private SQL detail'));
        $this->app->instance(ImportDictionaryService::class, $dictionary);

        $response = $this->actingAs($this->admin)->withoutMiddleware()
            ->postJson(route('admin.import.save-batch'), [
                'type' => 'siswa',
                'rows' => [[
                    'nisn' => '0000000350',
                    'nama' => 'Siswa Uji Error',
                    'kelas' => 'X-1',
                ]],
                'new_mapel' => [['kode' => 'tst', 'nama' => 'Mapel Uji']],
            ]);

        $response->assertStatus(500)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Terjadi kesalahan saat menyimpan data. Silakan coba kembali dan sampaikan ID batch kepada administrator.')
            ->assertDontSee('private SQL detail');
        $batchId = $response->json('batch_id');
        $this->assertIsInt($batchId);
        $this->assertDatabaseHas('import_unggahan', ['id' => $batchId, 'status' => 'gagal']);
    }

    public function test_student_import_reports_saved_active_and_graduated_counts(): void
    {
        $response = $this->import('siswa', [
            [
                'nisn' => '0000000101',
                'nama' => 'Siswa Aktif',
                'kelas' => 'X-1',
            ],
            [
                'nisn' => '0000000102',
                'nama' => 'Siswa Lulus',
                'kelas' => 'XII-1',
                'status' => 'Lulus',
                'tahun_lulus' => '2025',
            ],
            [
                'nisn' => '0000000103',
                'nama' => 'Baris Tidak Valid',
                'kelas' => 'X-1',
                'status' => 'Lulus',
                'tahun_lulus' => '25',
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('count', 2)
            ->assertJsonPath('skipped', 1)
            ->assertJsonPath('active_count', 1)
            ->assertJsonPath('graduated_count', 1);
        $activeStudentId = DB::table('siswa_biodata')->where('nisn', '0000000101')->value('id');
        $graduatedStudentId = DB::table('siswa_biodata')->where('nisn', '0000000102')->value('id');
        $this->assertDatabaseHas('siswa_riwayat_kelas', [
            'siswa_id' => $activeStudentId,
            'status' => 'Aktif',
        ]);
        $this->assertDatabaseHas('siswa_riwayat_kelas', [
            'siswa_id' => $graduatedStudentId,
            'status' => 'Lulus',
        ]);
    }

    public function test_student_import_results_route_graduates_to_alumni_and_active_students_to_data_siswa(): void
    {
        $this->import('siswa', [
            [
                'nisn' => '0000000104',
                'nama' => 'Siswa Lulus',
                'kelas' => 'XII-1',
                'status' => 'Lulus',
                'tahun_lulus' => '2026',
            ],
            [
                'nisn' => '0000000105',
                'nama' => 'Siswa Aktif Tanpa Status',
                'kelas' => 'XII-1',
            ],
        ], 'Ganjil', '2025/2026')->assertOk();

        $alumniUrl = route('admin.alumni', [
            'aj' => 'Alumni (Lulus)',
            'kelas' => 'XII-1',
        ]);
        $activeStudentsUrl = route('admin.data-siswa', ['kelas' => 'XII-1']);

        $this->actingAs($this->admin)
            ->withoutMiddleware()
            ->get(route('admin.import', ['tab' => 'siswa', 'tahun_ajaran_id' => 1]))
            ->assertOk()
            ->assertSee('Lulus')
            ->assertSee('Aktif')
            ->assertSee('href="'.e($alumniUrl).'"', false)
            ->assertSee('href="'.e($activeStudentsUrl).'"', false)
            ->assertSee('Lihat di Alumni')
            ->assertSee('Lihat di Data Siswa');
    }

    public function test_student_import_routes_alumni_to_alumni_and_belum_lulus_to_active_students(): void
    {
        $response = $this->import('siswa', [
            [
                'nisn' => '0000000111',
                'nama' => 'Siswa Alumni',
                'kelas' => 'XII-1',
                'status' => 'Alumni',
            ],
            [
                'nisn' => '0000000112',
                'nama' => 'Siswa Belum Lulus',
                'kelas' => 'XII-1',
                'status' => 'Belum Lulus',
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('count', 2)
            ->assertJsonPath('active_count', 1)
            ->assertJsonPath('graduated_count', 1);
        $alumniId = DB::table('siswa_biodata')->where('nisn', '0000000111')->value('id');
        $activeId = DB::table('siswa_biodata')->where('nisn', '0000000112')->value('id');
        $alumni = app(AlumniController::class)->index(Request::create('/admin/alumni'));
        $activeStudents = app(DataSiswaController::class)->index(Request::create('/admin/data-siswa'));
        $alumniIds = $alumni->getData()['alumni']->getCollection()->pluck('id')->all();
        $activeIds = $activeStudents->getData()['siswaList']->getCollection()->pluck('id')->all();

        $this->assertContains($alumniId, $alumniIds);
        $this->assertContains($activeId, $activeIds);
        $this->assertNotContains($alumniId, $activeIds);
    }

    public function test_imported_students_are_partitioned_between_active_and_alumni_lists(): void
    {
        DB::table('siswa_riwayat_kelas')->delete();
        DB::table('siswa_biodata')->delete();

        $response = $this->import('siswa', [
            ['nisn' => '0000000201', 'nama' => 'Siswa Aktif Satu', 'kelas' => 'X-1'],
            ['nisn' => '0000000202', 'nama' => 'Siswa Aktif Dua', 'kelas' => 'X-1'],
            ['nisn' => '0000000203', 'nama' => 'Siswa Aktif Tiga', 'kelas' => 'XI-1'],
            ['nisn' => '0000000204', 'nama' => 'Siswa Lulus Satu', 'kelas' => 'XII-1', 'status' => 'Lulus', 'tahun_lulus' => '2025'],
            ['nisn' => '0000000205', 'nama' => 'Siswa Lulus Dua', 'kelas' => 'XII-1', 'status' => 'Lulus', 'tahun_lulus' => '2025'],
        ]);

        $response->assertOk()
            ->assertJsonPath('count', 5)
            ->assertJsonPath('active_count', 3)
            ->assertJsonPath('graduated_count', 2);

        $dataSiswa = app(DataSiswaController::class)->index(Request::create('/admin/data-siswa'));
        $alumni = app(AlumniController::class)->index(Request::create('/admin/alumni'));
        $activeIds = $dataSiswa->getData()['siswaList']->getCollection()->pluck('id')->all();
        $alumniIds = $alumni->getData()['alumni']->getCollection()->pluck('id')->all();

        $this->assertSame(3, $dataSiswa->getData()['siswaList']->total());
        $this->assertSame(2, $alumni->getData()['alumni']->total());
        $this->assertSame([], array_values(array_intersect($activeIds, $alumniIds)));
    }

    public function test_graduated_alumni_class_filter_only_offers_and_matches_grade_twelve_classes(): void
    {
        DB::table('siswa_biodata')->whereIn('id', [1, 2])->update(['tahun_lulus' => '2025']);
        DB::table('siswa_riwayat_kelas')->whereIn('siswa_id', [1, 2])->update(['status' => 'Lulus']);
        DB::table('siswa_riwayat_kelas')->where('siswa_id', 2)->update(['kelas_id' => 2]);
        DB::table('siswa_biodata')->where('id', 1)->update([
            'kelas_diterima' => null,
            'rombel_diterima' => '1',
            'kelas_keluar' => 'XII-2',
        ]);
        DB::table('siswa_biodata')->where('id', 2)->update(['kelas_keluar' => 'XI-1']);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.alumni', ['kelas' => 'XII-2']));

        $response->assertOk()
            ->assertViewHas('alumni', function ($alumni): bool {
                return $alumni->total() === 1
                    && $alumni->getCollection()->pluck('id')->all() === [1];
            })
            ->assertSee('name="kelas"', false)
            ->assertSee('<option value="XII-2" selected>XII-2</option>', false)
            ->assertDontSee('<option value="X-1"', false)
            ->assertDontSee('<option value="XI-1"', false)
            ->assertSee('X-1')
            ->assertDontSee('Hapus per kelas')
            ->assertSee('href="'.e(route('admin.alumni.export', [
                'aj' => 'Alumni (Lulus)',
                'ay' => '',
                'kelas' => 'XII-2',
                'aq' => '',
            ])).'"', false);

        $this->actingAs($this->admin)
            ->get(route('admin.alumni', ['kelas' => 'XI-1']))
            ->assertOk()
            ->assertViewHas('alumni', fn ($alumni): bool => $alumni->total() === 0);
    }

    public function test_exit_alumni_class_filter_keeps_non_graduating_classes_available(): void
    {
        DB::table('siswa_biodata')->where('id', 2)->update([
            'alasan_keluar' => 'Pindah',
            'tanggal_keluar' => '2025-07-01',
            'kelas_keluar' => 'XI-1',
        ]);
        DB::table('siswa_riwayat_kelas')->where('siswa_id', 2)->update(['status' => 'Pindah keluar']);

        $this->actingAs($this->admin)
            ->get(route('admin.alumni', [
                'aj' => 'Pindah / Keluar',
                'kelas' => 'XI-1',
            ]))
            ->assertOk()
            ->assertViewHas('alumni', function ($alumni): bool {
                return $alumni->total() === 1
                    && $alumni->getCollection()->pluck('id')->all() === [2];
            })
            ->assertSee('<option value="XI-1" selected>XI-1</option>', false)
            ->assertSee('<option value="X-1"', false);
    }

    public function test_graduated_import_without_class_or_academic_year_is_listed_as_alumni(): void
    {
        $response = $this->import('siswa', [[
            'nisn' => '0000000301',
            'nama' => 'Siswa Lulus Tanpa Riwayat',
            'status' => 'Lulus',
        ]], 'Ganjil', '');

        $response->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonPath('graduated_count', 1);
        $studentId = (int) DB::table('siswa_biodata')->where('nisn', '0000000301')->value('id');
        $alumni = app(AlumniController::class)->index(Request::create('/admin/alumni'));
        $alumniIds = $alumni->getData()['alumni']->getCollection()->pluck('id')->all();

        $this->assertDatabaseMissing('siswa_riwayat_kelas', ['siswa_id' => $studentId]);
        $this->assertContains($studentId, $alumniIds);
    }

    public function test_exit_statuses_are_alumni_without_overlapping_active_students(): void
    {
        DB::table('siswa_riwayat_kelas')->delete();
        DB::table('siswa_biodata')->delete();

        $this->import('siswa', [
            [
                'nisn' => '0000000401',
                'nama' => 'Siswa Pindah',
                'kelas' => 'X-1',
                'status' => 'Pindah keluar',
            ],
            [
                'nisn' => '0000000402',
                'nama' => 'Siswa Keluar',
                'kelas' => 'X-1',
                'status' => 'Keluar',
            ],
            [
                'nisn' => '0000000403',
                'nama' => 'Siswa Aktif',
                'kelas' => 'X-1',
                'status' => 'Aktif',
                'alasan_keluar' => 'Pindah keluar',
            ],
        ])->assertOk()->assertJsonPath('count', 3);

        $dataSiswa = app(DataSiswaController::class)->index(Request::create('/admin/data-siswa'));
        $alumni = app(AlumniController::class)->index(Request::create('/admin/alumni', 'GET', [
            'aj' => 'Pindah / Keluar',
        ]));
        $activeIds = $dataSiswa->getData()['siswaList']->getCollection()->pluck('id')->all();
        $alumniIds = $alumni->getData()['alumni']->getCollection()->pluck('id')->all();

        $this->assertSame(1, $dataSiswa->getData()['siswaList']->total());
        $this->assertSame(2, $alumni->getData()['alumni']->total());
        $this->assertSame([], array_values(array_intersect($activeIds, $alumniIds)));
    }

    public function test_alumni_export_downloads_a_formatted_workbook_with_text_identifiers(): void
    {
        $this->makeGraduatedAlumni(1, '2025', 1);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.alumni.export', ['aj' => 'Alumni (Lulus)']));

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $workbook = IOFactory::load($response->baseResponse->getFile()->getPathname());
        $sheet = $workbook->getActiveSheet();

        $this->assertSame('Kontrol Induk Alumni', $sheet->getTitle());
        $this->assertSame('Nama Sama', $sheet->getCell('C2')->getValue());
        $this->assertSame('0000000001', $sheet->getCell('D2')->getValue());
        $this->assertSame('s', $sheet->getCell('D2')->getDataType());
        $this->assertSame('A1:V2', $sheet->getAutoFilter()->getRange());
        $workbook->disconnectWorksheets();
    }

    public function test_alumni_page_combines_graduates_and_exits_with_control_and_tracking_statuses(): void
    {
        DB::table('siswa_biodata')->where('id', 1)->update([
            'tahun_lulus' => '2025',
            'alasan_keluar' => 'Lulus',
            'nomor_hp' => '081234567890',
            'kegiatan_setelah_lulus' => 'Kuliah · Kampus Contoh',
        ]);
        DB::table('siswa_riwayat_kelas')->where('siswa_id', 1)->update(['status' => 'Lulus']);
        DB::table('siswa_biodata')->where('id', 2)->update([
            'alasan_keluar' => 'Pindah keluar',
            'tanggal_keluar' => '2025-06-30',
            'sekolah_tujuan' => 'SMA Contoh',
        ]);
        DB::table('siswa_pelacakan_alumni')->insert([
            'siswa_id' => 1,
            'jenis' => 'Kuliah',
            'nama_tempat' => 'Kampus Contoh',
            'keterangan' => 'Jurusan Informatika',
            'tahun' => '2026',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $graduates = $this->actingAs($this->admin)
            ->get(route('admin.alumni', [
                'aj' => 'Alumni (Lulus)',
                'tab' => 'pelacakan',
                'siswa' => 1,
            ]));

        $graduates->assertOk()
            ->assertViewHas('alumni', fn ($alumni): bool => $alumni->total() === 1)
            ->assertSee('Nama Sama')
            ->assertSee('Edit Kontrol Induk')
            ->assertSee('Status Kontrol Induk')
            ->assertSee('Status Pelacakan')
            ->assertSee('Sudah dilacak')
            ->assertSee('id="alumni-side-panel"', false)
            ->assertSee('onclick="return closeAlumniSidePanel(event)"', false)
            ->assertSee('🔍 Cari</button>', false)
            ->assertSee('Kegiatan Setelah Lulus')
            ->assertSee('Riwayat Pelacakan')
            ->assertSee('Jurusan Informatika')
            ->assertDontSee('<<<<<<< HEAD')
            ->assertDontSee('>>>>>>>');

        $exits = $this->get(route('admin.alumni', ['aj' => 'Pindah / Keluar']));

        $exits->assertOk()
            ->assertViewHas('alumni', fn ($alumni): bool => $alumni->total() === 1)
            ->assertSee('Belum dilacak')
            ->assertDontSee('Kampus Contoh');
    }

    public function test_alumni_can_edit_full_biodata_without_changing_class_history(): void
    {
        DB::table('siswa_biodata')->where('id', 1)->update(['tahun_lulus' => '2025']);
        DB::table('siswa_riwayat_kelas')->where('siswa_id', 1)->update(['status' => 'Lulus']);
        DB::table('siswa_biodata')->where('id', 1)->update([
            'kegiatan_setelah_lulus' => 'Kuliah · Kampus Contoh',
        ]);
        $trackingId = DB::table('siswa_pelacakan_alumni')->insertGetId([
            'siswa_id' => 1,
            'jenis' => 'Kuliah',
            'nama_tempat' => 'Kampus Contoh',
            'tahun' => '2025',
            'keterangan' => 'Teknik Informatika',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('siswa_biodata')->where('id', 1)->update(['kelas_keluar' => 'XII-1']);
        $historyBefore = DB::table('siswa_riwayat_kelas')->where('siswa_id', 1)->first();
        $filters = [
            'aj' => 'Alumni (Lulus)',
            'ay' => '2025',
            'kelas' => 'XII-1',
            'aq' => 'Nama',
            'page' => 1,
        ];

        $panelUrl = route('admin.alumni', [
            'tab' => 'kontrol-induk',
            'aj' => 'Alumni (Lulus)',
            'ay' => '2025',
            'aq' => 'Nama',
            'kelas' => 'XII-1',
            'page' => 1,
            'siswa' => 1,
        ]);
        $this->actingAs($this->admin)
            ->get(route('admin.alumni', array_merge($filters, ['siswa' => 1])))
            ->assertOk()
            ->assertSee('href="'.e($panelUrl).'" class="btn"', false)
            ->assertSee('onclick="event.stopPropagation();openAlumniEdit(this)"', false)
            ->assertSee('name="nomor_induk"', false)
            ->assertSee('name="nama_lengkap"', false)
            ->assertSee('name="nisn"', false)
            ->assertSee('name="nik"', false)
            ->assertSee('name="alamat_jalan"', false)
            ->assertSee('name="golongan_darah"', false)
            ->assertSee('name="nama_ayah"', false)
            ->assertSee('name="nama_ibu"', false)
            ->assertSee('name="nama_wali"', false)
            ->assertSee('name="foto_file"', false)
            ->assertSee('name="berkas[kk]"', false)
            ->assertSee('name="catatan"', false)
            ->assertSee('name="alumni_edit" value="1"', false)
            ->assertSee('name="nomor_peserta_psaj"', false)
            ->assertSee('name="nomor_transkrip"', false)
            ->assertSee('data-edit-field="status_alumni"', false)
            ->assertSee('data-edit-field="status_kontrol_induk"', false)
            ->assertSee('data-edit-field="status_pelacakan"', false)
            ->assertSee('name="tracking_jenis"', false)
            ->assertDontSee('alumni-detail-modal', false);

        $this->actingAs($this->admin)
            ->get(route('admin.alumni.edit-data', 1))
            ->assertOk()
            ->assertJsonPath('id', 1)
            ->assertJsonPath('nama_lengkap', 'Nama Sama')
            ->assertJsonStructure([
                'nik',
                'alamat_jalan',
                'golongan_darah',
                'nama_ayah',
                'nama_ibu',
                'nama_wali',
                'catatan',
                'kk_ada',
                'attachments',
                'tracking_history',
            ]);

        $this->actingAs($this->admin)
            ->get(route('admin.alumni.biodata.edit', array_merge($filters, ['id' => 1])))
            ->assertOk()
            ->assertSee('Edit Biodata Alumni')
            ->assertSee('name="alumni_edit" value="1"', false)
            ->assertSee('name="return_kelas" value="XII-1"', false)
            ->assertSee('name="tracking_jenis"', false)
            ->assertSee('value="Kampus Contoh"', false)
            ->assertSee('value="2025"', false)
            ->assertSee('Kegiatan Setelah Lulus (ringkasan otomatis)');

        $this->from(route('admin.alumni.biodata.edit', array_merge($filters, ['id' => 1])))
            ->put(route('admin.data-siswa.update', 1), [
                'alumni_edit' => '1',
                'nama_lengkap' => 'Nama Alumni Diperbarui',
                'nisn' => '0000000001',
                'nik' => '1234567890123456',
                'alamat_jalan' => 'Jalan Uji 1',
                'golongan_darah' => 'O',
                'nama_ayah' => 'Ayah Uji',
                'nama_ibu' => 'Ibu Uji',
                'nama_wali' => 'Wali Uji',
                'catatan' => 'Catatan alumni uji',
                'tracking_jenis' => 'Bekerja',
            ])
            ->assertSessionHasErrors(['tracking_nama_tempat', 'tracking_tahun']);

        $this->assertDatabaseHas('siswa_pelacakan_alumni', [
            'id' => $trackingId,
            'jenis' => 'Kuliah',
            'nama_tempat' => 'Kampus Contoh',
        ]);

        $this->put(route('admin.data-siswa.update', 1), [
            'alumni_edit' => '1',
            'nama_lengkap' => 'Nama Alumni Diperbarui',
            'nisn' => '0000000001',
            'nik' => '1234567890123456',
            'alamat_jalan' => 'Jalan Uji 1',
            'golongan_darah' => 'O',
            'nama_ayah' => 'Ayah Uji',
            'nama_ibu' => 'Ibu Uji',
            'nama_wali' => 'Wali Uji',
            'catatan' => 'Catatan alumni uji',
            'kk_ada' => '1',
            'tracking_jenis' => 'Bekerja',
            'tracking_nama_tempat' => 'Perusahaan Contoh',
            'tracking_tahun' => '2026',
            'tracking_keterangan' => 'Staf',
            'return_tab' => 'kontrol-induk',
            'return_aj' => 'Alumni (Lulus)',
            'return_ay' => '2025',
            'return_kelas' => 'XII-1',
            'return_aq' => 'Nama',
            'return_page' => '1',
        ])->assertRedirect(route('admin.alumni', [
            'tab' => 'kontrol-induk',
            'aj' => 'Alumni (Lulus)',
            'ay' => '2025',
            'kelas' => 'XII-1',
            'aq' => 'Nama',
            'page' => 1,
        ]));

        $this->get(route('admin.alumni', $filters))
            ->assertOk()
            ->assertDontSee('id="alumni-side-panel"', false)
            ->assertSee('Biodata lengkap Nama Alumni Diperbarui berhasil diperbarui.');

        $this->assertDatabaseHas('siswa_biodata', [
            'id' => 1,
            'nama_lengkap' => 'Nama Alumni Diperbarui',
            'tahun_lulus' => '2025',
            'nik' => '1234567890123456',
            'alamat_jalan' => 'Jalan Uji 1',
            'golongan_darah' => 'O',
            'nama_ayah' => 'Ayah Uji',
            'nama_ibu' => 'Ibu Uji',
            'nama_wali' => 'Wali Uji',
            'catatan' => 'Catatan alumni uji',
            'kk_ada' => 1,
        ]);
        $this->assertDatabaseHas('siswa_riwayat_kelas', [
            'id' => $historyBefore->id,
            'siswa_id' => 1,
            'tahun_ajaran_id' => $historyBefore->tahun_ajaran_id,
            'kelas_id' => $historyBefore->kelas_id,
            'status' => 'Lulus',
        ]);
        $this->assertDatabaseHas('catatan_aktivitas', [
            'aksi' => 'edit_biodata_alumni',
            'objek_id' => 1,
        ]);
        $this->assertDatabaseCount('siswa_pelacakan_alumni', 1);
        $this->assertDatabaseHas('siswa_pelacakan_alumni', [
            'id' => $trackingId,
            'jenis' => 'Bekerja',
            'nama_tempat' => 'Perusahaan Contoh',
            'tahun' => '2026',
            'keterangan' => 'Staf',
        ]);
        $this->assertDatabaseHas('siswa_biodata', [
            'id' => 1,
            'kegiatan_setelah_lulus' => 'Bekerja · Perusahaan Contoh',
        ]);
        $this->assertDatabaseHas('catatan_aktivitas', [
            'aksi' => 'lacak_alumni',
            'objek_id' => 1,
        ]);
    }

    public function test_alumni_list_uses_one_detail_panel_and_keeps_tracking_action_visible(): void
    {
        DB::table('siswa_biodata')->whereIn('id', [1, 2])->update(['tahun_lulus' => '2025']);
        DB::table('siswa_riwayat_kelas')->whereIn('siswa_id', [1, 2])->update(['status' => 'Lulus']);
        DB::table('siswa_pelacakan_alumni')->insert([
            'siswa_id' => 1,
            'jenis' => 'Kuliah',
            'nama_tempat' => 'Kampus Contoh',
            'tahun' => '2025',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.alumni'));

        $response->assertOk()
            ->assertSee('onclick="event.stopPropagation();openAlumniEdit(this)"', false)
            ->assertSee('id="alumni-edit-modal"', false)
            ->assertSee('>Edit Kontrol Induk</h3>', false)
            ->assertSee('id="alumni-select-all"', false)
            ->assertSee('name="siswa_ids[]"', false)
            ->assertSee('Hapus semua hasil filter')
            ->assertSee('Hapus terpilih')
            ->assertDontSee('Hapus per kelas')
            ->assertSee('data-edit-tab="control"', false)
            ->assertSee('data-edit-tab="tracking"', false)
            ->assertSee('class="btn alumni-tracking-action tracked"', false)
            ->assertSee('class="btn alumni-tracking-action pending"', false)
            ->assertSee('openAlumniEdit(this, \'tracking\')', false)
            ->assertSee('>Sudah dilacak</button>', false)
            ->assertSee('>Lacak</button>', false)
            ->assertDontSee('alumni-detail-modal', false);
    }

    public function test_alumni_bulk_delete_trashes_all_matching_filter_results_and_logs_activity(): void
    {
        $this->makeGraduatedAlumni(1, '2025', 1);
        $this->makeGraduatedAlumni(2, '2024', 1);

        $response = $this->actingAs($this->admin)->delete(route('admin.alumni.bulk-delete'), [
            'mode' => 'all',
            'aj' => 'Alumni (Lulus)',
            'ay' => '2025',
            'kelas' => '',
            'aq' => '',
            'konfirmasi' => 'hapus',
        ]);

        $response->assertRedirect(route('admin.alumni', [
            'aj' => 'Alumni (Lulus)',
            'ay' => '2025',
        ]))->assertSessionHas('success', '1 data alumni berhasil dipindahkan ke Tempat Sampah.');
        $this->assertSoftDeleted('siswa_biodata', ['id' => 1]);
        $this->assertNotSoftDeleted('siswa_biodata', ['id' => 2]);
        $this->assertDatabaseHas('catatan_aktivitas', [
            'pengguna_id' => $this->admin->id,
            'aksi' => 'hapus_alumni_massal',
            'objek_tipe' => 'alumni',
        ]);
    }

    public function test_alumni_bulk_delete_only_trashes_selected_ids_that_match_the_filter(): void
    {
        $this->makeGraduatedAlumni(1, '2025', 1);
        $this->makeGraduatedAlumni(2, '2025', 2);

        $response = $this->actingAs($this->admin)->delete(route('admin.alumni.bulk-delete'), [
            'mode' => 'selected',
            'aj' => 'Alumni (Lulus)',
            'ay' => '2025',
            'kelas' => 'XII-1',
            'aq' => '',
            'siswa_ids' => [1, 2],
            'konfirmasi' => 'hapus',
        ]);

        $response->assertRedirect(route('admin.alumni', [
            'aj' => 'Alumni (Lulus)',
            'ay' => '2025',
            'kelas' => 'XII-1',
        ]))->assertSessionHas('success', '1 data alumni berhasil dipindahkan ke Tempat Sampah.');
        $this->assertSoftDeleted('siswa_biodata', ['id' => 1]);
        $this->assertNotSoftDeleted('siswa_biodata', ['id' => 2]);
    }

    public function test_alumni_bulk_delete_by_class_only_trashes_alumni_in_the_selected_class(): void
    {
        $this->makeGraduatedAlumni(1, '2025', 1);
        $this->makeGraduatedAlumni(2, '2025', 2);

        $response = $this->actingAs($this->admin)->delete(route('admin.alumni.bulk-delete'), [
            'mode' => 'class',
            'aj' => 'Alumni (Lulus)',
            'ay' => '2025',
            'kelas' => 'XII-1',
            'aq' => '',
            'konfirmasi' => 'hapus',
        ]);

        $response->assertRedirect(route('admin.alumni', [
            'aj' => 'Alumni (Lulus)',
            'ay' => '2025',
            'kelas' => 'XII-1',
        ]))->assertSessionHas('success', '1 data alumni berhasil dipindahkan ke Tempat Sampah.');
        $this->assertSoftDeleted('siswa_biodata', ['id' => 1]);
        $this->assertNotSoftDeleted('siswa_biodata', ['id' => 2]);
    }

    public function test_alumni_bulk_delete_requires_confirmation_and_does_not_change_records(): void
    {
        $this->makeGraduatedAlumni(1, '2025', 1);

        $response = $this->actingAs($this->admin)->delete(route('admin.alumni.bulk-delete'), [
            'mode' => 'all',
            'aj' => 'Alumni (Lulus)',
        ]);

        $response->assertSessionHasErrors(['konfirmasi']);
        $this->assertNotSoftDeleted('siswa_biodata', ['id' => 1]);
    }

    public function test_teacher_cannot_use_alumni_bulk_delete_endpoint(): void
    {
        $this->makeGraduatedAlumni(1, '2025', 1);
        $teacher = Pengguna::create([
            'peran' => 'guru',
            'username' => 'alumni-delete-teacher',
            'email' => 'alumni-delete-teacher@example.test',
            'password' => 'unused',
            'aktif' => true,
        ]);

        $this->actingAs($teacher)->delete(route('admin.alumni.bulk-delete'), [
            'mode' => 'all',
            'aj' => 'Alumni (Lulus)',
            'konfirmasi' => 'hapus',
        ])->assertForbidden();

        $this->assertNotSoftDeleted('siswa_biodata', ['id' => 1]);
    }

    public function test_alumni_control_edit_modal_updates_latest_tracking_without_opening_detail_panel(): void
    {
        DB::table('siswa_biodata')->where('id', 1)->update([
            'tahun_lulus' => '2025',
            'alasan_keluar' => 'Lulus',
        ]);
        DB::table('siswa_riwayat_kelas')->where('siswa_id', 1)->update(['status' => 'Lulus']);
        $trackingId = DB::table('siswa_pelacakan_alumni')->insertGetId([
            'siswa_id' => 1,
            'jenis' => 'Kuliah',
            'nama_tempat' => 'Kampus Contoh',
            'tahun' => '2025',
            'keterangan' => 'Teknik Informatika',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.alumni'))
            ->assertOk()
            ->assertSee('name="tracking_jenis"', false)
            ->assertSee('name="nomor_induk"', false)
            ->assertSee('name="nama_lengkap"', false)
            ->assertSee('name="nisn"', false)
            ->assertSee('Kampus Contoh')
            ->assertSee('data-edit-tab="tracking"', false)
            ->assertDontSee('id="alumni-side-panel"', false);

        $this->actingAs($this->admin)
            ->get(route('admin.alumni.edit-data', 1))
            ->assertOk()
            ->assertJsonPath('tracking_keterangan', 'Teknik Informatika')
            ->assertJsonPath('tracking_history.0.keterangan', 'Teknik Informatika');

        $duplicateNisn = DB::table('siswa_biodata')
            ->where('id', '!=', 1)
            ->value('nisn');

        $this->put(route('admin.alumni.update', 1), [
            'nama_lengkap' => 'Nama Alumni',
            'nisn' => $duplicateNisn,
            'tracking_jenis' => 'Kuliah',
            'tracking_nama_tempat' => 'Kampus Contoh',
            'tracking_tahun' => '2025',
            'tracking_keterangan' => 'Teknik Informatika',
        ])->assertSessionHasErrors('nisn');

        $this->put(route('admin.alumni.update', 1), [
            'nomor_induk' => 'INDUK-ALUMNI-2025',
            'nama_lengkap' => 'Nama Alumni Diperbarui',
            'nisn' => '0000000001',
            'jenis_registrasi' => 'Siswa Baru',
            'tanggal_diterima' => '2023-07-17',
            'kelas_diterima' => 'X',
            'rombel_diterima' => '1',
            'sekolah_asal' => 'SMPN Uji',
            'alasan_keluar' => 'Lulus',
            'tanggal_keluar' => '2025-05-04',
            'kelas_keluar' => 'XII-1',
            'sekolah_tujuan' => '',
            'tahun_lulus' => '2025',
            'nomor_peserta_psaj' => 'PSAJ-2025',
            'nomor_skl' => 'SKL-2025',
            'nomor_ijazah_sma' => 'IJZ-2025',
            'tanggal_ijazah_sma' => '2025-07-04',
            'nomor_transkrip' => 'TR-2025',
            'tracking_jenis' => 'Bekerja',
            'tracking_nama_tempat' => 'Perusahaan Contoh',
            'tracking_tahun' => '2026',
            'tracking_keterangan' => 'Staf',
            'aj' => 'Alumni (Lulus)',
        ])->assertRedirect(route('admin.alumni', [
            'tab' => 'kontrol-induk',
            'aj' => 'Alumni (Lulus)',
        ]));

        $this->assertDatabaseCount('siswa_pelacakan_alumni', 1);
        $this->assertDatabaseHas('siswa_pelacakan_alumni', [
            'id' => $trackingId,
            'jenis' => 'Bekerja',
            'nama_tempat' => 'Perusahaan Contoh',
            'tahun' => '2026',
            'keterangan' => 'Staf',
        ]);
        $this->assertDatabaseHas('siswa_biodata', [
            'id' => 1,
            'nomor_induk' => 'INDUK-ALUMNI-2025',
            'nama_lengkap' => 'Nama Alumni Diperbarui',
            'nisn' => '0000000001',
            'jenis_registrasi' => 'Siswa Baru',
            'tanggal_diterima' => '2023-07-17',
            'kelas_diterima' => 'X',
            'rombel_diterima' => '1',
            'sekolah_asal' => 'SMPN Uji',
            'alasan_keluar' => 'Lulus',
            'tanggal_keluar' => '2025-05-04',
            'kelas_keluar' => 'XII-1',
            'tahun_lulus' => '2025',
            'nomor_peserta_psaj' => 'PSAJ-2025',
            'nomor_skl' => 'SKL-2025',
            'nomor_ijazah_sma' => 'IJZ-2025',
            'tanggal_ijazah_sma' => '2025-07-04',
            'nomor_transkrip' => 'TR-2025',
            'kegiatan_setelah_lulus' => 'Bekerja · Perusahaan Contoh',
        ]);
        $this->assertDatabaseHas('catatan_aktivitas', [
            'aksi' => 'edit_kontrol_induk',
            'objek_id' => 1,
        ]);
        $this->assertDatabaseHas('catatan_aktivitas', [
            'aksi' => 'lacak_alumni',
            'objek_id' => 1,
        ]);
    }

    public function test_active_student_cannot_open_alumni_biodata_editor(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.alumni.biodata.edit', 2))
            ->assertNotFound();
    }

    public function test_graduation_cancellation_is_explained_when_active_year_history_is_missing(): void
    {
        DB::table('siswa_biodata')->where('id', 1)->update(['tahun_lulus' => '2025']);
        DB::table('siswa_riwayat_kelas')->where('siswa_id', 1)->delete();

        $this->actingAs($this->admin)
            ->get(route('admin.alumni', [
                'aj' => 'Alumni (Lulus)',
                'siswa' => 1,
            ]))
            ->assertOk()
            ->assertSee('disabled>Batalkan kelulusan', false)
            ->assertSee('Tidak dapat dibatalkan karena belum ada riwayat pada tahun ajaran aktif.');
    }

    public function test_cancelling_transfer_restores_student_to_active_data_and_records_activity(): void
    {
        DB::table('siswa_biodata')->where('id', 2)->update([
            'alasan_keluar' => 'Pindah keluar',
            'tanggal_keluar' => '2025-10-01',
            'kelas_keluar' => 'X-1',
            'sekolah_tujuan' => 'SMA Tujuan',
        ]);
        DB::table('siswa_riwayat_kelas')->where('siswa_id', 2)->update(['status' => 'Pindah keluar']);
        $alumniUrl = route('admin.alumni', [
            'aj' => 'Pindah / Keluar',
            'siswa' => 2,
        ]);

        $this->actingAs($this->admin)
            ->get($alumniUrl)
            ->assertOk()
            ->assertSee('Batalkan pindah keluar')
            ->assertSee('action="'.e(route('admin.alumni.cancel-transfer', 2)).'"', false);

        $this->from($alumniUrl)
            ->actingAs($this->admin)
            ->post(route('admin.alumni.cancel-transfer', 2), [
                'aj' => 'Pindah / Keluar',
            ])
            ->assertRedirect(route('admin.alumni', [
                'tab' => 'kontrol-induk',
                'aj' => 'Pindah / Keluar',
            ]));

        $this->assertDatabaseHas('siswa_biodata', [
            'id' => 2,
            'alasan_keluar' => null,
            'tanggal_keluar' => null,
            'kelas_keluar' => null,
            'sekolah_tujuan' => null,
        ]);
        $this->assertDatabaseHas('siswa_riwayat_kelas', [
            'siswa_id' => 2,
            'tahun_ajaran_id' => 1,
            'status' => 'Aktif',
        ]);
        $this->assertDatabaseHas('catatan_aktivitas', [
            'pengguna_id' => $this->admin->id,
            'aksi' => 'batalkan_mutasi_siswa',
            'objek_id' => 2,
        ]);
        $this->actingAs($this->admin)
            ->get(route('admin.data-siswa'))
            ->assertOk()
            ->assertViewHas('siswaList', fn ($students): bool => $students->getCollection()->contains('id', 2));
    }

    public function test_cancelling_exit_restores_student_to_active_data(): void
    {
        DB::table('siswa_biodata')->where('id', 2)->update([
            'alasan_keluar' => 'Keluar',
            'tanggal_keluar' => '2025-10-01',
            'kelas_keluar' => 'X-1',
        ]);
        DB::table('siswa_riwayat_kelas')->where('siswa_id', 2)->update(['status' => 'Keluar']);

        $this->actingAs($this->admin)
            ->get(route('admin.alumni', [
                'aj' => 'Pindah / Keluar',
                'siswa' => 2,
            ]))
            ->assertOk()
            ->assertSee('Batalkan keluar');

        $this->actingAs($this->admin)
            ->post(route('admin.alumni.cancel-transfer', 2), [
                'aj' => 'Pindah / Keluar',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('siswa_biodata', [
            'id' => 2,
            'alasan_keluar' => null,
            'tanggal_keluar' => null,
            'kelas_keluar' => null,
        ]);
        $this->assertDatabaseHas('siswa_riwayat_kelas', [
            'siswa_id' => 2,
            'tahun_ajaran_id' => 1,
            'status' => 'Aktif',
        ]);
    }

    public function test_transfer_cancellation_is_unavailable_without_an_active_year_transfer_history(): void
    {
        DB::table('siswa_biodata')->where('id', 2)->update([
            'alasan_keluar' => 'Pindah keluar',
            'tanggal_keluar' => '2025-10-01',
        ]);
        DB::table('siswa_riwayat_kelas')->where('siswa_id', 2)->update(['status' => 'Aktif']);

        $this->actingAs($this->admin)
            ->get(route('admin.alumni', [
                'aj' => 'Pindah / Keluar',
                'siswa' => 2,
            ]))
            ->assertOk()
            ->assertSee('disabled>Batalkan pindah keluar', false)
            ->assertSee('Tidak dapat dibatalkan karena tidak ada riwayat pindah keluar pada tahun ajaran aktif.');
    }

    public function test_transfer_cancellation_rejects_students_without_an_active_year_transfer_history(): void
    {
        DB::table('siswa_biodata')->where('id', 2)->update([
            'alasan_keluar' => 'Pindah keluar',
            'tanggal_keluar' => '2025-10-01',
        ]);
        DB::table('siswa_riwayat_kelas')->where('siswa_id', 2)->update(['status' => 'Aktif']);

        $this->actingAs($this->admin)
            ->post(route('admin.alumni.cancel-transfer', 2), [
                'aj' => 'Pindah / Keluar',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('mutasi');

        $this->assertDatabaseHas('siswa_biodata', [
            'id' => 2,
            'alasan_keluar' => 'Pindah keluar',
            'tanggal_keluar' => '2025-10-01',
        ]);
        $this->assertDatabaseHas('siswa_riwayat_kelas', [
            'siswa_id' => 2,
            'tahun_ajaran_id' => 1,
            'status' => 'Aktif',
        ]);
        $this->assertDatabaseMissing('catatan_aktivitas', [
            'aksi' => 'batalkan_mutasi_siswa',
            'objek_id' => 2,
        ]);
    }

    public function test_legacy_tracking_page_redirects_to_alumni_with_tracking_context(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.pelacakan-alumni', ['lcy' => '2025', 'page' => 2]))
            ->assertRedirect(route('admin.alumni', [
                'tab' => 'pelacakan',
                'aj' => 'Alumni (Lulus)',
                'ay' => '2025',
                'page' => 2,
            ]));
    }

    public function test_tracking_panel_actions_save_and_delete_history_and_update_activity_log(): void
    {
        DB::table('siswa_biodata')->where('id', 1)->update([
            'tahun_lulus' => '2025',
            'alasan_keluar' => 'Lulus',
        ]);

        $alumniUrl = route('admin.alumni', [
            'tab' => 'pelacakan',
            'aj' => 'Alumni (Lulus)',
            'siswa' => 1,
        ]);
        $storeResponse = $this->actingAs($this->admin)
            ->from($alumniUrl)
            ->post(route('admin.pelacakan-alumni.store'), [
                'siswa_id' => 1,
                'jenis' => 'Kuliah',
                'nama_tempat' => 'Kampus Contoh',
                'tahun' => '2026',
                'keterangan' => 'Jurusan Informatika',
            ]);

        $storeResponse->assertRedirect($alumniUrl);
        $this->assertDatabaseHas('siswa_pelacakan_alumni', [
            'siswa_id' => 1,
            'jenis' => 'Kuliah',
            'nama_tempat' => 'Kampus Contoh',
            'tahun' => '2026',
            'keterangan' => 'Jurusan Informatika',
        ]);
        $this->assertDatabaseHas('siswa_biodata', [
            'id' => 1,
            'kegiatan_setelah_lulus' => 'Kuliah · Kampus Contoh',
        ]);
        $this->assertDatabaseHas('catatan_aktivitas', [
            'aksi' => 'lacak_alumni',
            'objek_id' => 1,
        ]);

        $trackingId = (int) DB::table('siswa_pelacakan_alumni')->value('id');
        $deleteResponse = $this->from($alumniUrl)
            ->delete(route('admin.pelacakan-alumni.destroy', $trackingId));

        $deleteResponse->assertRedirect($alumniUrl);
        $this->assertDatabaseMissing('siswa_pelacakan_alumni', ['id' => $trackingId]);
        $this->assertDatabaseHas('siswa_biodata', [
            'id' => 1,
            'kegiatan_setelah_lulus' => null,
        ]);
        $this->assertDatabaseHas('catatan_aktivitas', [
            'aksi' => 'hapus_pelacakan_alumni',
            'objek_id' => 1,
        ]);
    }

    public function test_tracking_cannot_be_added_for_an_active_student(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.pelacakan-alumni.store'), [
                'siswa_id' => 2,
                'jenis' => 'Kuliah',
                'nama_tempat' => 'Kampus Contoh',
                'tahun' => '2026',
            ])
            ->assertNotFound();

        $this->assertDatabaseMissing('siswa_pelacakan_alumni', ['siswa_id' => 2]);
    }

    public function test_cancelling_graduation_restores_active_status_and_records_activity(): void
    {
        DB::table('siswa_biodata')->where('id', 1)->update([
            'tahun_lulus' => '2025',
            'alasan_keluar' => 'Lulus',
            'tanggal_keluar' => '2025-06-30',
            'kelas_keluar' => 'XII-1',
        ]);
        DB::table('siswa_riwayat_kelas')->where('siswa_id', 1)->update(['status' => 'Lulus']);

        $this->actingAs($this->admin)
            ->post(route('admin.alumni.cancel-graduation', 1), [
                'aj' => 'Alumni (Lulus)',
                'ay' => '',
                'aq' => '',
            ])
            ->assertRedirect(route('admin.alumni', [
                'tab' => 'kontrol-induk',
                'aj' => 'Alumni (Lulus)',
            ]));

        $this->assertDatabaseHas('siswa_biodata', [
            'id' => 1,
            'tahun_lulus' => null,
            'alasan_keluar' => null,
            'tanggal_keluar' => null,
            'kelas_keluar' => null,
        ]);
        $this->assertDatabaseHas('siswa_riwayat_kelas', [
            'siswa_id' => 1,
            'tahun_ajaran_id' => 1,
            'status' => 'Aktif',
        ]);
        $this->assertDatabaseHas('catatan_aktivitas', [
            'aksi' => 'batalkan_kelulusan',
            'objek_id' => 1,
        ]);
        $this->assertSame(2, SiswaBiodata::aktif()->count());
        $this->assertDatabaseMissing('siswa_biodata', [
            'id' => 1,
            'tahun_lulus' => '2025',
        ]);
    }

    public function test_server_accepts_comma_decimal_long_scores_and_rejects_out_of_range_values(): void
    {
        $response = $this->import('nilai', [
            [
                'nisn' => '0000000001',
                'nama' => 'Nama Sama',
                'kelas' => 'X-1',
                'mapelcol' => 'Matematika',
                'nilaicol' => '85,5',
                '__excel_row' => 2,
            ],
            [
                'nisn' => '0000000002',
                'nama' => 'Nama Sama',
                'kelas' => 'X-1',
                'mtk' => '100,1',
                '__excel_row' => 3,
            ],
        ]);

        $response->assertOk()->assertJsonPath('count', 1)->assertJsonPath('skipped', 1);
        $this->assertSame(85.5, (float) DB::table('siswa_nilai')->value('nilai_angka'));
        $this->assertDatabaseHas('import_baris', [
            'nomor_baris_excel' => 3,
            'status' => 'error',
        ]);
    }

    public function test_450_rows_in_three_chunks_are_recorded_in_one_completed_batch(): void
    {
        $start = $this->actingAs($this->admin)->withoutMiddleware()->postJson(route('admin.import.start-batch'), [
            'type' => 'nilai',
            'file_name' => 'nilai-450.xlsx',
            'total_rows' => 450,
            'tahun_ajaran' => '2025/2026',
            'semester' => 'Ganjil',
            'kelas' => 'X-1',
        ]);
        $start->assertCreated()->assertJsonPath('total_rows', 450);
        $batchId = $start->json('batch_id');

        foreach ([200, 200, 50] as $chunkIndex => $chunkSize) {
            $rows = [];
            for ($offset = 0; $offset < $chunkSize; $offset++) {
                $rows[] = [
                    'nisn' => '0000000001',
                    'nama' => 'Nama Sama',
                    'kelas' => 'X-1',
                    'mtk' => 80,
                    '__excel_row' => $chunkIndex * 200 + $offset + 2,
                ];
            }

            $response = $this->postJson(route('admin.import.save-batch'), [
                'batch_id' => $batchId,
                'type' => 'nilai',
                'tahun_ajaran' => '2025/2026',
                'semester' => 'Ganjil',
                'mode' => 'semua',
                'dup' => 'timpa',
                'rows' => $rows,
                'final_chunk' => $chunkIndex === 2,
            ]);

            $response->assertOk()
                ->assertJsonPath('count', $chunkSize)
                ->assertJsonPath('batch_id', $batchId)
                ->assertJsonPath('completed', $chunkIndex === 2);
        }

        $this->assertDatabaseHas('import_unggahan', [
            'id' => $batchId,
            'jumlah_baris' => 450,
            'jumlah_siap' => 450,
            'jumlah_tersimpan' => 450,
            'status' => 'selesai',
        ]);
        $this->assertSame(450, DB::table('import_baris')->where('unggahan_id', $batchId)->count());
        $this->assertSame(1, DB::table('import_unggahan')->where('id', $batchId)->count());
        $this->assertSame(1, DB::table('siswa_nilai')->where('import_unggahan_id', $batchId)->count());
        $this->assertSame(1, DB::table('siswa_nilai')->where('import_terakhir_unggahan_id', $batchId)->count());
        $this->assertSame(1, DB::table('catatan_aktivitas')->where('objek_id', $batchId)->count());
    }

    public function test_downloaded_grade_template_can_be_filled_and_imported_through_the_batch_endpoint(): void
    {
        $download = $this->actingAs($this->admin)->get(route('admin.import.template', ['jenis' => 'nilai']));
        $download->assertOk();

        $path = tempnam(sys_get_temp_dir(), 'josbook-template-');
        $this->assertNotFalse($path);

        try {
            $workbook = IOFactory::load($download->baseResponse->getFile()->getPathname());
            $worksheet = $workbook->getSheetByName('Data');
            $worksheet->fromArray([['0000000002', 'Nama Sama', 'X-1', 'Ganjil', 'Matematika', 88]], null, 'A2');
            IOFactory::createWriter($workbook, 'Xlsx')->save($path);
            $workbook->disconnectWorksheets();

            $filledWorkbook = IOFactory::load($path);
            $row = $filledWorkbook->getSheetByName('Data')->rangeToArray('A2:F2')[0];
            $filledWorkbook->disconnectWorksheets();
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }

        $response = $this->import('nilai', [array_combine(
            ['nisn', 'nama', 'kelas', 'sem', 'mapelcol', 'nilaicol'],
            $row
        )]);

        $response->assertOk()->assertJsonPath('count', 1)->assertJsonPath('skipped', 0);
        $this->assertDatabaseHas('siswa_nilai', [
            'riwayat_kelas_id' => 2,
            'semester' => 'Ganjil',
            'mapel_id' => 1,
            'nilai_angka' => 88,
        ]);
    }

    private function makeGraduatedAlumni(int $studentId, string $graduationYear, int $classId): void
    {
        $exitClass = 'XII-'.$classId;
        if (! DB::table('sekolah_kelas')->where('nama', $exitClass)->exists()) {
            DB::table('sekolah_kelas')->insert([
                'nama' => $exitClass,
                'tingkat' => 'XII',
            ]);
        }

        DB::table('siswa_biodata')->where('id', $studentId)->update([
            'tahun_lulus' => $graduationYear,
            'alasan_keluar' => 'Lulus',
            'kelas_keluar' => $exitClass,
        ]);
        DB::table('siswa_riwayat_kelas')->where('siswa_id', $studentId)->update([
            'kelas_id' => $classId,
            'status' => 'Lulus',
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function import(
        string $type,
        array $rows,
        string $semester = 'Ganjil',
        ?string $academicYear = null,
        ?string $duplicateMode = null,
        array $targetOptions = []
    ): TestResponse {
        $academicImport = in_array($type, ['nilai', 'hadir', 'kehadiran', 'ekskul'], true);

        $payload = [
            'type' => $type,
            'tahun_ajaran' => $academicImport ? ($academicYear ?? '') : ($academicYear ?? '2025/2026'),
            'semester' => $semester,
            'mode' => 'semua',
            'kelas' => $academicImport ? 'auto' : '',
            'rows' => $rows,
        ];
        if ($duplicateMode !== null) {
            $payload['dup'] = $duplicateMode;
        }
        $payload = array_replace($payload, $targetOptions);

        return $this->actingAs($this->admin)->withoutMiddleware()->postJson(route('admin.import.save-batch'), $payload);
    }

    private function buatAkunGuru(string $username): Pengguna
    {
        $guru = GuruBiodata::create([
            'nama' => $username,
            'nip' => $username,
        ]);

        return Pengguna::create([
            'peran' => 'guru',
            'username' => $username,
            'password' => 'unused',
            'aktif' => true,
            'guru_id' => $guru->id,
        ]);
    }

    private function tugaskanMapel(Pengguna $pengguna, int $tahunAjaranId, int $kelasId, int $mapelId): void
    {
        DB::table('guru_mengajar')->insert([
            'tahun_ajaran_id' => $tahunAjaranId,
            'guru_id' => $pengguna->guru_id,
            'kelas_id' => $kelasId,
            'mapel_id' => $mapelId,
        ]);
    }

    private function tugaskanWali(Pengguna $pengguna, int $tahunAjaranId, int $kelasId): void
    {
        DB::table('guru_wali_kelas')->insert([
            'tahun_ajaran_id' => $tahunAjaranId,
            'guru_id' => $pengguna->guru_id,
            'kelas_id' => $kelasId,
        ]);
    }
}
