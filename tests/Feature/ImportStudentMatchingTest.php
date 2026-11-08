<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ImportStudentMatchingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
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
            'id' => 1,
            'nama' => 'X-1',
            'tingkat' => 'X',
            'created_at' => now(),
            'updated_at' => now(),
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
    }

    protected function tearDown(): void
    {
        foreach ([
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
                'id' => 2,
                'nama' => 'XI-1',
                'tingkat' => 'XI',
                'created_at' => now(),
                'updated_at' => now(),
            ],
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
            ['id' => 2, 'nama' => 'XI-1', 'tingkat' => 'XI', 'created_at' => now(), 'updated_at' => now()],
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

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function import(string $type, array $rows, string $semester = 'Ganjil')
    {
        $sixSemesterFlow = $semester === 'auto' || in_array($semester, ['1', '2', '3', '4', '5', '6'], true);

        return $this->withoutMiddleware()->postJson(route('admin.import.save-batch'), [
            'type' => $type,
            'tahun_ajaran' => $sixSemesterFlow ? '' : '2025/2026',
            'semester' => $semester,
            'mode' => 'semua',
            'rows' => $rows,
        ]);
    }
}
