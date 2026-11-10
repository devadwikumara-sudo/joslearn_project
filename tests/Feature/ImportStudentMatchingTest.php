<?php

namespace Tests\Feature;

use App\Models\Auth\Pengguna;
use App\Models\Guru\GuruBiodata;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tests\TestCase;

#[RunTestsInSeparateProcesses]
class ImportStudentMatchingTest extends TestCase
{
    private Pengguna $admin;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
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

    public function test_six_semesters_resolve_to_the_students_existing_class_history_without_a_selected_year(): void
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
            'sakit' => 3,
        ]], '6')->assertOk()->assertJsonPath('count', 1);
        $this->assertSame(3, DB::table('siswa_kehadiran')
            ->where('riwayat_kelas_id', 4)
            ->where('semester', 'Genap')
            ->value('sakit'));

        $this->import('ekskul', [[
            'nisn' => '0000000002',
            'kelas' => 'XI-1',
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
            ['nisn' => '0000000002', 'kelas' => 'X-1', 'sem' => '1', 'mtk' => 81],
            ['nisn' => '0000000002', 'kelas' => 'X-1', 'sem' => 'Genap', 'mtk' => 82],
            ['nisn' => '0000000002', 'kelas' => 'XI-1', 'sem' => 'Semester 1', 'mtk' => 83],
            ['nisn' => '0000000002', 'kelas' => 'XI-1', 'sem' => '2', 'mtk' => 84],
            ['nisn' => '0000000002', 'kelas' => 'XII-1', 'sem' => 'Ganjil', 'mtk' => 85],
            ['nisn' => '0000000002', 'kelas' => 'XII-1', 'sem' => 'Semester 2', 'mtk' => 86],
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
            'sakit' => 1,
        ]], '1')->assertOk()->assertJsonPath('count', 1);
        $this->assertSame(1, DB::table('siswa_kehadiran')
            ->where('riwayat_kelas_id', 3)
            ->where('semester', 'Ganjil')
            ->count());

        $this->import('hadir', [[
            'nisn' => '0000000002',
            'kelas' => 'XII-1',
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
            ->assertJsonFragment(['reason' => 'Riwayat kelas X-1 untuk semester 1 ditemukan lebih dari satu; sertakan tahun ajaran pada baris Excel agar tidak salah memilih.'])
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
