<?php

namespace Tests\Feature;

use App\Models\Auth\Pengguna;
use App\Models\Guru\GuruBiodata;
use App\Models\SiswaBiodata;
use App\Models\SiswaRiwayatKelas;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tests\TestCase;

#[RunTestsInSeparateProcesses]
class AcademicRecordControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:'.base64_encode(str_repeat('j', 32))]);

        foreach ([
            'catatan_aktivitas',
            'guru_wali_kelas',
            'guru_mengajar',
            'akun_pengguna',
            'guru_biodata',
            'siswa_ekskul',
            'siswa_kehadiran',
            'siswa_nilai',
            'siswa_riwayat_kelas',
            'siswa_biodata',
            'sekolah_mapel',
            'sekolah_kelas',
            'sekolah_tahun_ajaran',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('sekolah_tahun_ajaran', function (Blueprint $table): void {
            $table->id();
            $table->string('nama');
            $table->boolean('sedang_berjalan')->default(false);
            $table->timestamps();
        });
        Schema::create('sekolah_kelas', function (Blueprint $table): void {
            $table->id();
            $table->string('nama');
            $table->string('tingkat')->nullable();
            $table->timestamps();
        });
        Schema::create('sekolah_mapel', function (Blueprint $table): void {
            $table->id();
            $table->string('kode');
            $table->string('nama');
            $table->timestamps();
        });
        Schema::create('siswa_biodata', function (Blueprint $table): void {
            $table->id();
            $table->string('nama_lengkap');
            $table->string('nisn')->nullable();
            $table->string('nomor_induk')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('siswa_riwayat_kelas', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('siswa_id');
            $table->unsignedBigInteger('tahun_ajaran_id');
            $table->unsignedBigInteger('kelas_id');
            $table->string('status')->default('Aktif');
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
            $table->string('nama_kegiatan', 100);
            $table->string('predikat', 30)->nullable();
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
            $table->boolean('wajib_ganti_password')->default(false);
            $table->timestamp('email_diverifikasi_at')->nullable();
            $table->timestamp('terakhir_login_at')->nullable();
            $table->unsignedBigInteger('guru_id')->nullable();
            $table->unsignedBigInteger('siswa_id')->nullable();
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
        Schema::create('catatan_aktivitas', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('pengguna_id')->nullable();
            $table->string('peran')->nullable();
            $table->string('aksi', 60);
            $table->string('keterangan', 500)->nullable();
            $table->string('objek_tipe', 40)->nullable();
            $table->unsignedBigInteger('objek_id')->nullable();
            $table->string('alamat_ip', 45)->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    protected function tearDown(): void
    {
        foreach ([
            'catatan_aktivitas',
            'guru_wali_kelas',
            'guru_mengajar',
            'akun_pengguna',
            'guru_biodata',
            'siswa_ekskul',
            'siswa_kehadiran',
            'siswa_nilai',
            'siswa_riwayat_kelas',
            'siswa_biodata',
            'sekolah_mapel',
            'sekolah_kelas',
            'sekolah_tahun_ajaran',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_admin_can_create_update_and_delete_each_academic_record_type_with_activity_logs(): void
    {
        [$student, $history, $year, $class, $subjects] = $this->createAcademicContext();
        $admin = $this->createUser('admin', 'academic-admin');
        $gradeUrl = route('siswa.akademik.store', ['siswa' => $student->id, 'jenis' => 'nilai']);
        $attendanceUrl = route('siswa.akademik.store', ['siswa' => $student->id, 'jenis' => 'kehadiran']);
        $activityUrl = route('siswa.akademik.store', ['siswa' => $student->id, 'jenis' => 'ekskul']);

        $this->actingAs($admin)->post($gradeUrl, [
            'riwayat_kelas_id' => $history->id,
            'semester' => 'Ganjil',
            'mapel_id' => $subjects[0],
            'nilai_angka' => 80,
        ])->assertRedirect();
        $gradeId = (int) DB::table('siswa_nilai')->value('id');
        $this->put(route('siswa.akademik.update', ['siswa' => $student->id, 'jenis' => 'nilai', 'record' => $gradeId]), [
            'riwayat_kelas_id' => $history->id,
            'semester' => 'Ganjil',
            'mapel_id' => $subjects[0],
            'nilai_angka' => 91,
        ])->assertRedirect();

        $this->post($attendanceUrl, [
            'riwayat_kelas_id' => $history->id,
            'semester' => 'Ganjil',
            'sakit' => 1,
            'izin' => 2,
            'alpa' => 0,
            'kokurikuler' => 'Catatan sintetis',
        ])->assertRedirect();
        $attendanceId = (int) DB::table('siswa_kehadiran')->value('id');
        $this->put(route('siswa.akademik.update', ['siswa' => $student->id, 'jenis' => 'kehadiran', 'record' => $attendanceId]), [
            'riwayat_kelas_id' => $history->id,
            'semester' => 'Ganjil',
            'sakit' => 0,
            'izin' => 1,
            'alpa' => 0,
            'kokurikuler' => 'Catatan diperbarui',
        ])->assertRedirect();

        $this->post($activityUrl, [
            'riwayat_kelas_id' => $history->id,
            'semester' => 'Ganjil',
            'nama_kegiatan' => 'Pramuka',
            'predikat' => 'Baik',
        ])->assertRedirect();
        $activityId = (int) DB::table('siswa_ekskul')->value('id');
        $this->put(route('siswa.akademik.update', ['siswa' => $student->id, 'jenis' => 'ekskul', 'record' => $activityId]), [
            'riwayat_kelas_id' => $history->id,
            'semester' => 'Ganjil',
            'nama_kegiatan' => 'PMR',
            'predikat' => 'Sangat Baik',
        ])->assertRedirect();

        $this->delete(route('siswa.akademik.destroy', ['siswa' => $student->id, 'jenis' => 'nilai', 'record' => $gradeId]))->assertRedirect();
        $this->delete(route('siswa.akademik.destroy', ['siswa' => $student->id, 'jenis' => 'kehadiran', 'record' => $attendanceId]))->assertRedirect();
        $this->delete(route('siswa.akademik.destroy', ['siswa' => $student->id, 'jenis' => 'ekskul', 'record' => $activityId]))->assertRedirect();

        $this->assertDatabaseMissing('siswa_nilai', ['id' => $gradeId]);
        $this->assertDatabaseMissing('siswa_kehadiran', ['id' => $attendanceId]);
        $this->assertDatabaseMissing('siswa_ekskul', ['id' => $activityId]);
        $this->assertDatabaseHas('catatan_aktivitas', ['aksi' => 'tambah_nilai', 'objek_tipe' => 'siswa_nilai', 'objek_id' => $gradeId]);
        $this->assertDatabaseHas('catatan_aktivitas', ['aksi' => 'ubah_kehadiran', 'objek_tipe' => 'siswa_kehadiran', 'objek_id' => $attendanceId]);
        $this->assertDatabaseHas('catatan_aktivitas', ['aksi' => 'hapus_ekskul', 'objek_tipe' => 'siswa_ekskul', 'objek_id' => $activityId]);
        $this->assertStringContainsString('Sangat Baik', (string) DB::table('catatan_aktivitas')
            ->where('aksi', 'hapus_ekskul')
            ->where('objek_id', $activityId)
            ->value('keterangan'));
    }

    public function test_invalid_scores_and_attendance_counts_are_rejected_without_writing_records(): void
    {
        [$student, $history, , , $subjects] = $this->createAcademicContext();
        $admin = $this->createUser('admin', 'invalid-admin');

        $this->actingAs($admin)->from('/data-siswa')->post(route('siswa.akademik.store', [
            'siswa' => $student->id,
            'jenis' => 'nilai',
        ]), [
            'riwayat_kelas_id' => $history->id,
            'semester' => 'Ganjil',
            'mapel_id' => $subjects[0],
            'nilai_angka' => 101,
        ])->assertRedirect('/data-siswa')->assertSessionHasErrors('nilai_angka');

        $this->from('/data-siswa')->post(route('siswa.akademik.store', [
            'siswa' => $student->id,
            'jenis' => 'kehadiran',
        ]), [
            'riwayat_kelas_id' => $history->id,
            'semester' => 'Ganjil',
            'sakit' => -1,
            'izin' => 0,
            'alpa' => 0,
        ])->assertRedirect('/data-siswa')->assertSessionHasErrors('sakit');

        $this->assertDatabaseCount('siswa_nilai', 0);
        $this->assertDatabaseCount('siswa_kehadiran', 0);
    }

    public function test_duplicate_academic_rows_are_rejected_according_to_the_database_keys(): void
    {
        [$student, $history, , , $subjects] = $this->createAcademicContext();
        $admin = $this->createUser('admin', 'duplicate-admin');
        DB::table('siswa_nilai')->insert([
            'riwayat_kelas_id' => $history->id,
            'semester' => 'Ganjil',
            'mapel_id' => $subjects[0],
            'nilai_angka' => 80,
        ]);
        DB::table('siswa_kehadiran')->insert([
            'riwayat_kelas_id' => $history->id,
            'semester' => 'Ganjil',
            'sakit' => 0,
            'izin' => 0,
            'alpa' => 0,
        ]);
        DB::table('siswa_ekskul')->insert([
            'riwayat_kelas_id' => $history->id,
            'semester' => 'Ganjil',
            'nama_kegiatan' => 'Pramuka',
            'predikat' => 'Baik',
        ]);
        $this->actingAs($admin);

        $this->post(route('siswa.akademik.store', ['siswa' => $student->id, 'jenis' => 'nilai']), [
            'riwayat_kelas_id' => $history->id,
            'semester' => 'Ganjil',
            'mapel_id' => $subjects[0],
            'nilai_angka' => 91,
        ])->assertSessionHasErrors('mapel_id');
        $this->post(route('siswa.akademik.store', ['siswa' => $student->id, 'jenis' => 'kehadiran']), [
            'riwayat_kelas_id' => $history->id,
            'semester' => 'Ganjil',
            'sakit' => 1,
            'izin' => 0,
            'alpa' => 0,
        ])->assertSessionHasErrors('semester');
        $this->post(route('siswa.akademik.store', ['siswa' => $student->id, 'jenis' => 'ekskul']), [
            'riwayat_kelas_id' => $history->id,
            'semester' => 'Ganjil',
            'nama_kegiatan' => 'Pramuka',
            'predikat' => 'Sangat Baik',
        ])->assertSessionHasErrors('nama_kegiatan');

        $this->assertDatabaseCount('siswa_nilai', 1);
        $this->assertDatabaseCount('siswa_kehadiran', 1);
        $this->assertDatabaseCount('siswa_ekskul', 1);
    }

    public function test_teacher_can_edit_only_an_assigned_subject_in_a_current_class(): void
    {
        [$student, $history, $year, $class, $subjects, $otherHistory, $otherStudent] = $this->createAcademicContext(true);
        $teacher = $this->createUser('guru', 'subject-teacher');
        $this->assignSubject($teacher, $year, $class, $subjects[0]);

        $this->actingAs($teacher)->post(route('siswa.akademik.store', [
            'siswa' => $student->id,
            'jenis' => 'nilai',
        ]), [
            'riwayat_kelas_id' => $history->id,
            'semester' => 'Ganjil',
            'mapel_id' => $subjects[0],
            'nilai_angka' => 86,
        ])->assertRedirect();

        $this->post(route('siswa.akademik.store', [
            'siswa' => $student->id,
            'jenis' => 'nilai',
        ]), [
            'riwayat_kelas_id' => $history->id,
            'semester' => 'Genap',
            'mapel_id' => $subjects[1],
            'nilai_angka' => 86,
        ])->assertForbidden();

        $this->post(route('siswa.akademik.store', [
            'siswa' => $otherStudent->id,
            'jenis' => 'nilai',
        ]), [
            'riwayat_kelas_id' => $otherHistory->id,
            'semester' => 'Ganjil',
            'mapel_id' => $subjects[0],
            'nilai_angka' => 86,
        ])->assertForbidden();

        $this->post(route('siswa.akademik.store', [
            'siswa' => $student->id,
            'jenis' => 'kehadiran',
        ]), [
            'riwayat_kelas_id' => $history->id,
            'semester' => 'Ganjil',
            'sakit' => 0,
            'izin' => 0,
            'alpa' => 0,
        ])->assertForbidden();

        $this->assertDatabaseCount('siswa_nilai', 1);
        $this->assertDatabaseCount('siswa_kehadiran', 0);
    }

    public function test_teacher_cannot_manage_records_from_a_non_current_academic_year(): void
    {
        [$student, , $currentYearId, $classId, $subjects] = $this->createAcademicContext();
        $oldYearId = DB::table('sekolah_tahun_ajaran')->insertGetId([
            'nama' => '2024/2025',
            'sedang_berjalan' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $oldHistory = SiswaRiwayatKelas::create([
            'siswa_id' => $student->id,
            'tahun_ajaran_id' => $oldYearId,
            'kelas_id' => $classId,
            'status' => 'Aktif',
        ]);
        $teacher = $this->createUser('guru', 'previous-year-teacher');
        $this->assignSubject($teacher, $currentYearId, $classId, $subjects[0]);

        $this->actingAs($teacher)->post(route('siswa.akademik.store', [
            'siswa' => $student->id,
            'jenis' => 'nilai',
        ]), [
            'riwayat_kelas_id' => $oldHistory->id,
            'semester' => 'Ganjil',
            'mapel_id' => $subjects[0],
            'nilai_angka' => 86,
        ])->assertForbidden();

        $this->assertDatabaseCount('siswa_nilai', 0);
    }

    public function test_homeroom_teacher_can_manage_attendance_and_extracurricular_but_not_grades_or_other_classes(): void
    {
        [$student, $history, $year, , $subjects, $otherHistory, $otherStudent] = $this->createAcademicContext(true);
        $teacher = $this->createUser('guru', 'homeroom-teacher');
        $this->assignHomeroom($teacher, $year, (int) $history->kelas_id);

        $this->actingAs($teacher)->post(route('siswa.akademik.store', [
            'siswa' => $student->id,
            'jenis' => 'kehadiran',
        ]), [
            'riwayat_kelas_id' => $history->id,
            'semester' => 'Ganjil',
            'sakit' => 0,
            'izin' => 1,
            'alpa' => 0,
        ])->assertRedirect();
        $this->post(route('siswa.akademik.store', [
            'siswa' => $student->id,
            'jenis' => 'ekskul',
        ]), [
            'riwayat_kelas_id' => $history->id,
            'semester' => 'Ganjil',
            'nama_kegiatan' => 'Pramuka',
            'predikat' => 'Baik',
        ])->assertRedirect();

        $this->post(route('siswa.akademik.store', [
            'siswa' => $student->id,
            'jenis' => 'nilai',
        ]), [
            'riwayat_kelas_id' => $history->id,
            'semester' => 'Ganjil',
            'mapel_id' => $subjects[0],
            'nilai_angka' => 88,
        ])->assertForbidden();
        $this->post(route('siswa.akademik.store', [
            'siswa' => $otherStudent->id,
            'jenis' => 'kehadiran',
        ]), [
            'riwayat_kelas_id' => $otherHistory->id,
            'semester' => 'Ganjil',
            'sakit' => 0,
            'izin' => 0,
            'alpa' => 0,
        ])->assertForbidden();

        $this->assertDatabaseCount('siswa_kehadiran', 1);
        $this->assertDatabaseCount('siswa_ekskul', 1);
        $this->assertDatabaseCount('siswa_nilai', 0);
    }

    public function test_unassigned_teacher_cannot_change_academic_records(): void
    {
        [$student, $history, , , $subjects] = $this->createAcademicContext();
        $teacher = $this->createUser('guru', 'unassigned-teacher');

        $this->actingAs($teacher)->post(route('siswa.akademik.store', [
            'siswa' => $student->id,
            'jenis' => 'nilai',
        ]), [
            'riwayat_kelas_id' => $history->id,
            'semester' => 'Ganjil',
            'mapel_id' => $subjects[0],
            'nilai_angka' => 88,
        ])->assertForbidden();

        $this->assertDatabaseCount('siswa_nilai', 0);
        $this->assertDatabaseCount('catatan_aktivitas', 0);
    }

    public function test_teacher_cannot_update_or_delete_a_grade_for_an_unassigned_subject(): void
    {
        [$student, $history, $year, $class, $subjects] = $this->createAcademicContext();
        $recordId = DB::table('siswa_nilai')->insertGetId([
            'riwayat_kelas_id' => $history->id,
            'semester' => 'Ganjil',
            'mapel_id' => $subjects[0],
            'nilai_angka' => 75,
        ]);
        $teacher = $this->createUser('guru', 'different-subject-teacher');
        $this->assignSubject($teacher, $year, $class, $subjects[1]);

        $this->actingAs($teacher)->put(route('siswa.akademik.update', [
            'siswa' => $student->id,
            'jenis' => 'nilai',
            'record' => $recordId,
        ]), [
            'riwayat_kelas_id' => $history->id,
            'semester' => 'Ganjil',
            'mapel_id' => $subjects[0],
            'nilai_angka' => 99,
        ])->assertForbidden();

        $this->delete(route('siswa.akademik.destroy', [
            'siswa' => $student->id,
            'jenis' => 'nilai',
            'record' => $recordId,
        ]))->assertForbidden();

        $this->assertDatabaseHas('siswa_nilai', [
            'id' => $recordId,
            'nilai_angka' => 75,
        ]);
        $this->assertDatabaseCount('catatan_aktivitas', 0);
    }

    public function test_update_cannot_target_another_students_record(): void
    {
        [$student, $history, , , $subjects, $otherHistory, $otherStudent] = $this->createAcademicContext(true);
        $recordId = DB::table('siswa_nilai')->insertGetId([
            'riwayat_kelas_id' => $otherHistory->id,
            'semester' => 'Ganjil',
            'mapel_id' => $subjects[0],
            'nilai_angka' => 75,
        ]);
        $admin = $this->createUser('admin', 'scope-admin');

        $this->actingAs($admin)->put(route('siswa.akademik.update', [
            'siswa' => $student->id,
            'jenis' => 'nilai',
            'record' => $recordId,
        ]), [
            'riwayat_kelas_id' => $history->id,
            'semester' => 'Ganjil',
            'mapel_id' => $subjects[0],
            'nilai_angka' => 99,
        ])->assertNotFound();

        $this->assertDatabaseHas('siswa_nilai', ['id' => $recordId, 'riwayat_kelas_id' => $otherHistory->id, 'nilai_angka' => 75]);
        $this->assertDatabaseCount('catatan_aktivitas', 0);
    }

    /**
     * @return array{SiswaBiodata, SiswaRiwayatKelas, int, int, array{int, int}}|array{SiswaBiodata, SiswaRiwayatKelas, int, int, array{int, int}, SiswaRiwayatKelas, SiswaBiodata}
     */
    private function createAcademicContext(bool $withOtherStudent = false): array
    {
        $yearId = DB::table('sekolah_tahun_ajaran')->insertGetId([
            'nama' => '2025/2026',
            'sedang_berjalan' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $classId = DB::table('sekolah_kelas')->insertGetId([
            'nama' => 'X-1',
            'tingkat' => 'X',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $otherClassId = DB::table('sekolah_kelas')->insertGetId([
            'nama' => 'X-2',
            'tingkat' => 'X',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $subjects = [
            DB::table('sekolah_mapel')->insertGetId(['kode' => 'mtk', 'nama' => 'Matematika', 'created_at' => now(), 'updated_at' => now()]),
            DB::table('sekolah_mapel')->insertGetId(['kode' => 'fis', 'nama' => 'Fisika', 'created_at' => now(), 'updated_at' => now()]),
        ];
        $student = SiswaBiodata::create(['nama_lengkap' => 'Siswa Uji', 'nisn' => '0000000001']);
        $history = SiswaRiwayatKelas::create([
            'siswa_id' => $student->id,
            'tahun_ajaran_id' => $yearId,
            'kelas_id' => $classId,
            'status' => 'Aktif',
        ]);

        if (! $withOtherStudent) {
            return [$student, $history, $yearId, $classId, $subjects];
        }

        $otherStudent = SiswaBiodata::create(['nama_lengkap' => 'Siswa Kedua', 'nisn' => '0000000002']);
        $otherHistory = SiswaRiwayatKelas::create([
            'siswa_id' => $otherStudent->id,
            'tahun_ajaran_id' => $yearId,
            'kelas_id' => $otherClassId,
            'status' => 'Aktif',
        ]);

        return [$student, $history, $yearId, $classId, $subjects, $otherHistory, $otherStudent];
    }

    private function createUser(string $role, string $username): Pengguna
    {
        $guruId = null;
        if ($role === 'guru') {
            $guruId = GuruBiodata::create(['nama' => $username])->id;
        }

        return Pengguna::create([
            'peran' => $role,
            'username' => $username,
            'password' => 'unused',
            'aktif' => true,
            'wajib_ganti_password' => false,
            'guru_id' => $guruId,
        ]);
    }

    private function assignSubject(Pengguna $teacher, int $yearId, int $classId, int $subjectId): void
    {
        DB::table('guru_mengajar')->insert([
            'tahun_ajaran_id' => $yearId,
            'guru_id' => $teacher->guru_id,
            'kelas_id' => $classId,
            'mapel_id' => $subjectId,
        ]);
    }

    private function assignHomeroom(Pengguna $teacher, int $yearId, int $classId): void
    {
        DB::table('guru_wali_kelas')->insert([
            'tahun_ajaran_id' => $yearId,
            'guru_id' => $teacher->guru_id,
            'kelas_id' => $classId,
        ]);
    }
}
