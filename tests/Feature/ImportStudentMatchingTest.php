<?php

namespace Tests\Feature;

use App\Models\Auth\Pengguna;
use App\Models\Guru\GuruBiodata;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\IOFactory;
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
            'catatan_aktivitas',
            'sekolah_mapel',
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
        });

        Schema::create('sekolah_tahun_ajaran', function (Blueprint $table): void {
            $table->id();
            $table->string('nama')->unique();
            $table->boolean('sedang_berjalan')->default(false);
            $table->timestamps();
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
        Schema::create('guru_biodata', function (Blueprint $table): void {
            $table->id();
            $table->string('nama');
            $table->string('nip')->nullable();
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
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('siswa_nilai', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('riwayat_kelas_id');
            $table->string('semester');
            $table->unsignedBigInteger('mapel_id');
            $table->decimal('nilai_angka', 5, 2);
            $table->timestamps();
        });

        Schema::create('siswa_kehadiran', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('riwayat_kelas_id');
            $table->string('semester');
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
            'catatan_aktivitas',
            'sekolah_mapel',
            'sekolah_kelas',
            'sekolah_tahun_ajaran',
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
            ->assertJsonFragment(['reason' => 'Nama yang sama ditemukan lebih dari sekali pada kelas dan tahun ajaran ini; tambahkan NISN atau NIS.'])
            ->assertJsonFragment(['reason' => 'NISN tidak ditemukan; baris ini dilewati dan tidak dicocokkan dengan identitas lain.']);

        $this->assertSame(0, DB::table('siswa_nilai')->count());
    }

    public function test_unique_name_in_the_requested_year_can_be_matched_without_class_or_numeric_ids(): void
    {
        DB::table('siswa_riwayat_kelas')->where('siswa_id', 1)->delete();

        $response = $this->import('nilai', [[
            'nama' => 'Nama Sama',
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
            ->assertJsonFragment(['reason' => 'Kelas pada file berbeda dari riwayat siswa untuk tahun ajaran ini; data tidak dipindahkan otomatis.']);
        $this->assertSame(1, DB::table('siswa_riwayat_kelas')->where('siswa_id', 2)->value('kelas_id'));
        $this->assertSame(0, DB::table('siswa_kehadiran')->count());
    }

    public function test_import_uses_a_valid_year_per_row_and_skips_a_row_that_conflicts_with_selected_class(): void
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

        $newHistory = DB::table('siswa_riwayat_kelas')
            ->where('siswa_id', 2)
            ->where('tahun_ajaran_id', 2)
            ->first();
        $this->assertNotNull($newHistory);
        $this->assertSame(1, DB::table('siswa_nilai')
            ->where('riwayat_kelas_id', $newHistory->id)
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
            ->assertJsonFragment(['reason' => 'Kelas pada baris berbeda dari kelas yang dipilih; baris ini dilewati.']);
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

    public function test_row_year_overrides_form_year_and_creates_missing_year_and_class_history(): void
    {
        DB::table('siswa_biodata')->insert([
            'id' => 3,
            'nama_lengkap' => 'Siswa Tanpa Riwayat',
            'nisn' => '0000000003',
            'nomor_induk' => '1003',
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
        ]]);

        $response->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonPath('created_years', 1)
            ->assertJsonPath('created_histories', 1)
            ->assertJsonPath('message', 'Berhasil mengimpor 1 data nilai (termasuk 1 tahun ajaran baru dan 1 riwayat kelas baru).');

        $newYearId = DB::table('sekolah_tahun_ajaran')->where('nama', '2026/2027')->value('id');
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
            ->assertJsonPath('errors.0.reason', 'Riwayat kelas X-1 ditemukan lebih dari satu pada beberapa tahun ajaran; isi kolom tahun_ajaran agar tidak salah memilih.');

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
        ]], 'auto')
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

        $response = $this->import('nilai', [
            [
                'nisn' => '0000000002',
                'kelas' => 'X-1',
                'sem' => '1',
                'mtk' => 90,
                '__excel_row' => 21,
            ],
            [
                'nisn' => '0000000002',
                'kelas' => 'X-1',
                'sem' => '3',
                'mtk' => 91,
                '__excel_row' => 22,
            ],
        ], 'auto');

        $response->assertOk()
            ->assertJsonPath('count', 0)
            ->assertJsonPath('skipped', 2)
            ->assertJsonPath('errors.0.row', 21)
            ->assertJsonPath('errors.1.row', 22)
            ->assertJsonFragment(['reason' => 'Riwayat kelas X-1 ditemukan lebih dari satu pada beberapa tahun ajaran; isi kolom tahun_ajaran agar tidak salah memilih.'])
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

    public function test_teacher_specific_import_and_grade_routes_check_class_and_subject_assignments(): void
    {
        $guru = $this->buatAkunGuru('guru-rute');
        $this->tugaskanMapel($guru, 1, 1, 1);
        $this->tugaskanWali($guru, 1, 1);

        $this->actingAs($guru)->withoutMiddleware()
            ->get(route('guru.import-nilai'))
            ->assertOk()
            ->assertViewHas('kelasList', ['X-1']);
        $this->get(route('guru.import-ekskul'))->assertOk()->assertViewHas('kelasList', ['X-1']);
        $this->get(route('guru.import-ketidakhadiran'))->assertOk()->assertViewHas('kelasList', ['X-1']);
        $this->get(route('guru.input-nilai-mapel', ['kelas' => 'XI-1']))->assertForbidden();

        $this->post(route('guru.import-nilai.store'), ['kelas' => 'XI-1', 'mata_pelajaran' => 'Matematika'])
            ->assertForbidden();
        $this->post(route('guru.import-ekskul.store'), ['kelas' => 'XI-1'])->assertForbidden();
        $this->post(route('guru.import-ketidakhadiran.store'), ['kelas' => 'XI-1'])->assertForbidden();
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

        $this->post(route('guru.import-nilai.store'), ['kelas' => 'X-1', 'mata_pelajaran' => 'Matematika'])->assertRedirect();
        $this->post(route('guru.import-ekskul.store'), ['kelas' => 'X-1'])->assertRedirect();
        $this->post(route('guru.import-ketidakhadiran.store'), ['kelas' => 'X-1'])->assertRedirect();
        $this->post(route('guru.input-nilai-mapel.store'), [
            'kelas' => 'X-1',
            'mapel_id' => 1,
            'semester' => 'Ganjil',
        ])->assertRedirect();
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
            ->assertSee('Matematika - hanya mapel dan kelas yang Anda ajar')
            ->assertSee('Nama Sama')
            ->assertSee('0000000001')
            ->assertSee('value="83.5"', false)
            ->assertDontSee('0000000002')
            ->assertViewHas('siswa', fn ($siswa): bool => $siswa->count() === 1);
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
                'nisn' => 123456789,
                'nis' => '1.003e3',
                'nama' => '  Siswa   Masa Depan  ',
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
                'nisn' => 987654321,
                'nama' => 'Siswa Usia Tidak Wajar',
                'tgl' => '01-01-1800',
                '__excel_row' => 3,
            ],
        ]);

        $response->assertOk()->assertJsonPath('count', 2);

        $student = DB::table('siswa_biodata')->where('nisn', '0123456789')->first();
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
            'nisn' => '0987654321',
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
        $this->assertSame(1, DB::table('catatan_aktivitas')->where('objek_id', $batchId)->count());
    }

    public function test_downloaded_grade_template_can_be_filled_and_imported_through_the_batch_endpoint(): void
    {
        $download = $this->actingAs($this->admin)->get(route('admin.import.nilai.template'));
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

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function import(string $type, array $rows, string $semester = 'Ganjil')
    {
        $sixSemesterFlow = $semester === 'auto' || in_array($semester, ['1', '2', '3', '4', '5', '6'], true);

        return $this->actingAs($this->admin)->withoutMiddleware()->postJson(route('admin.import.save-batch'), [
            'type' => $type,
            'tahun_ajaran' => $sixSemesterFlow ? '' : '2025/2026',
            'semester' => $semester,
            'mode' => 'semua',
            'rows' => $rows,
        ]);
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
