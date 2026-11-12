<?php

namespace Tests\Feature;

use App\Models\Auth\Pengguna;
use App\Models\Guru\GuruBiodata;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tests\TestCase;

#[RunTestsInSeparateProcesses]
class W6GuruExperienceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'catatan_aktivitas',
            'siswa_riwayat_kelas',
            'siswa_biodata',
            'akun_pengguna',
            'guru_wali_kelas',
            'guru_mengajar',
            'guru_biodata',
            'sekolah_pengaturan',
            'sekolah_mapel',
            'sekolah_kelas',
            'sekolah_tahun_ajaran',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('sekolah_tahun_ajaran', function (Blueprint $table): void {
            $table->id();
            $table->string('nama', 9);
            $table->boolean('sedang_berjalan')->default(false);
            $table->timestamps();
        });
        Schema::create('sekolah_pengaturan', function (Blueprint $table): void {
            $table->id();
            $table->string('kunci', 60);
            $table->string('isi')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
        Schema::create('sekolah_kelas', function (Blueprint $table): void {
            $table->id();
            $table->string('nama', 30);
            $table->string('tingkat', 3);
            $table->timestamps();
        });
        Schema::create('sekolah_mapel', function (Blueprint $table): void {
            $table->id();
            $table->string('kode', 3);
            $table->string('nama', 150);
            $table->timestamps();
        });
        Schema::create('guru_biodata', function (Blueprint $table): void {
            $table->id();
            $table->string('nama', 150);
            $table->string('nip', 30)->nullable();
            $table->softDeletes();
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
        Schema::create('akun_pengguna', function (Blueprint $table): void {
            $table->id();
            $table->string('peran');
            $table->string('username')->unique();
            $table->string('email')->nullable();
            $table->string('password');
            $table->boolean('aktif')->default(true);
            $table->unsignedBigInteger('guru_id')->nullable();
            $table->boolean('wajib_ganti_password')->default(false);
            $table->rememberToken();
            $table->timestamps();
        });
        Schema::create('siswa_biodata', function (Blueprint $table): void {
            $table->id();
            $table->string('nama_lengkap');
            $table->softDeletes();
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
        Schema::create('catatan_aktivitas', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('pengguna_id')->nullable();
            $table->string('peran', 10)->nullable();
            $table->string('aksi', 60);
            $table->string('keterangan', 500)->nullable();
            $table->string('objek_tipe', 40)->nullable();
            $table->unsignedBigInteger('objek_id')->nullable();
            $table->string('alamat_ip', 45)->nullable();
            $table->timestamp('created_at')->nullable();
        });

        DB::table('sekolah_tahun_ajaran')->insert([
            'id' => 1,
            'nama' => '2025/2026',
            'sedang_berjalan' => true,
        ]);
        DB::table('sekolah_pengaturan')->insert([
            'kunci' => 'semester_berjalan',
            'isi' => 'Ganjil',
        ]);
        DB::table('sekolah_kelas')->insert([
            ['id' => 1, 'nama' => 'X-1', 'tingkat' => 'X'],
            ['id' => 2, 'nama' => 'XI-1', 'tingkat' => 'XI'],
        ]);
        DB::table('sekolah_mapel')->insert([
            'id' => 1,
            'kode' => 'mtk',
            'nama' => 'Matematika',
        ]);
    }

    public function test_subject_teacher_home_shows_live_assignment_and_subject_menu_only(): void
    {
        $teacher = $this->createTeacher('Guru Mapel');
        $this->assignSubject($teacher, 1, 1);

        $response = $this->actingAs($teacher)->get(route('guru.beranda'));

        $response->assertOk()
            ->assertSee('Guru Matematika')
            ->assertSee('Kelas diajar')
            ->assertSee('X-1')
            ->assertSee('2025/2026')
            ->assertSee('Ganjil')
            ->assertSee('Dashboard')
            ->assertSee('Input Nilai Mapel')
            ->assertSee('Import Nilai')
            ->assertSee('Log Aktivitas')
            ->assertSee('Setting')
            ->assertDontSee('Wali Kelas X-1')
            ->assertDontSee('Import Kehadiran')
            ->assertDontSee('Import Ekskul');
    }

    public function test_homeroom_teacher_home_shows_active_students_and_homeroom_menu_only(): void
    {
        $teacher = $this->createTeacher('Wali Kelas');
        $this->assignHomeroom($teacher, 1);
        $this->createStudentHistory(1, 'Aktif');
        $this->createStudentHistory(1, 'Pindah keluar');

        $response = $this->actingAs($teacher)->get(route('guru.beranda'));

        $response->assertOk()
            ->assertSee('Wali Kelas X-1')
            ->assertSee('1 siswa aktif')
            ->assertSee('Dashboard')
            ->assertSee('Import Kehadiran')
            ->assertSee('Import Ekskul')
            ->assertSee('Log Aktivitas')
            ->assertSee('Setting')
            ->assertDontSee('Guru Mapel')
            ->assertDontSee('Input Nilai Mapel')
            ->assertDontSee('Import Nilai');
    }

    public function test_teacher_with_both_assignments_sees_both_home_cards_and_menus(): void
    {
        $teacher = $this->createTeacher('Guru Rangkap');
        $this->assignSubject($teacher, 1, 1);
        $this->assignHomeroom($teacher, 1);

        $response = $this->actingAs($teacher)->get(route('guru.beranda'));

        $response->assertOk()
            ->assertSee('Guru Mapel')
            ->assertSee('Wali Kelas X-1')
            ->assertSee('Input Nilai Mapel')
            ->assertSee('Import Nilai')
            ->assertSee('Import Kehadiran')
            ->assertSee('Import Ekskul');
    }

    public function test_unassigned_teacher_sees_notice_and_is_denied_assignment_routes(): void
    {
        $teacher = $this->createTeacher('Guru Belum Ditugaskan');
        $this->actingAs($teacher)
            ->get(route('guru.beranda'))
            ->assertOk()
            ->assertSee('Belum ada penugasan, hubungi admin');

        foreach ([
            'guru.input-nilai-mapel',
            'guru.import-nilai',
            'guru.import-ketidakhadiran',
            'guru.import-ekskul',
        ] as $routeName) {
            $this->get(route($routeName))->assertForbidden();
        }
    }

    public function test_teacher_activity_log_contains_only_the_authenticated_teachers_entries(): void
    {
        $teacher = $this->createTeacher('Guru Pemilik Log');
        $otherTeacher = $this->createTeacher('Guru Lain');
        DB::table('catatan_aktivitas')->insert([
            [
                'pengguna_id' => $teacher->id,
                'peran' => 'guru',
                'aksi' => 'aktivitas_milik_sendiri',
                'keterangan' => 'Aktivitas milik saya.',
            ],
            [
                'pengguna_id' => $otherTeacher->id,
                'peran' => 'guru',
                'aksi' => 'aktivitas_milik_guru_lain',
                'keterangan' => 'Aktivitas milik guru lain.',
            ],
        ]);

        $response = $this->actingAs($teacher)->get(route('guru.log-aktivitas'));

        $response->assertOk()
            ->assertSee('Aktivitas milik saya.')
            ->assertDontSee('Aktivitas milik guru lain.');
    }

    public function test_teacher_can_update_display_name_and_password_and_changes_are_logged(): void
    {
        $teacher = $this->createTeacher('Guru Sebelum');

        $this->actingAs($teacher)
            ->get(route('guru.setting'))
            ->assertOk()
            ->assertSee('Nama Tampilan')
            ->assertSee('Ganti Kata Sandi');

        $this->put(route('guru.setting.profil'), ['nama' => 'Guru Sesudah'])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('guru_biodata', [
            'id' => $teacher->guru_id,
            'nama' => 'Guru Sesudah',
        ]);
        $this->assertDatabaseHas('catatan_aktivitas', [
            'pengguna_id' => $teacher->id,
            'aksi' => 'ubah_nama_tampilan',
        ]);

        $this->put(route('guru.setting.password'), [
            'password_lama' => 'password-lama-uji',
            'password_baru' => 'password-baru-uji',
            'password_baru_confirmation' => 'password-baru-uji',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $teacher->refresh();
        $this->assertTrue(Hash::check('password-baru-uji', $teacher->password));
        $this->assertDatabaseHas('catatan_aktivitas', [
            'pengguna_id' => $teacher->id,
            'aksi' => 'ubah_kata_sandi',
        ]);
    }

    public function test_teacher_cannot_change_password_with_an_incorrect_current_password(): void
    {
        $teacher = $this->createTeacher('Guru Uji');
        $passwordHash = $teacher->password;

        $this->actingAs($teacher)
            ->put(route('guru.setting.password'), [
                'password_lama' => 'bukan-password-yang-benar',
                'password_baru' => 'password-baru-uji',
                'password_baru_confirmation' => 'password-baru-uji',
            ])
            ->assertSessionHasErrors([
                'password_lama' => 'Kata sandi saat ini tidak sesuai.',
            ]);

        $this->assertSame($passwordHash, $teacher->fresh()->password);
        $this->assertDatabaseMissing('catatan_aktivitas', [
            'pengguna_id' => $teacher->id,
            'aksi' => 'ubah_kata_sandi',
        ]);
    }

    private function createTeacher(string $name): Pengguna
    {
        $guru = GuruBiodata::query()->create([
            'nama' => $name,
            'nip' => 'NIP-'.str_replace(' ', '-', $name),
        ]);

        return Pengguna::query()->create([
            'peran' => 'guru',
            'username' => 'guru-'.strtolower(str_replace(' ', '-', $name)),
            'email' => null,
            'password' => 'password-lama-uji',
            'aktif' => true,
            'guru_id' => $guru->id,
            'wajib_ganti_password' => false,
        ]);
    }

    private function assignSubject(Pengguna $teacher, int $classId, int $subjectId): void
    {
        DB::table('guru_mengajar')->insert([
            'tahun_ajaran_id' => 1,
            'guru_id' => $teacher->guru_id,
            'kelas_id' => $classId,
            'mapel_id' => $subjectId,
        ]);
    }

    private function assignHomeroom(Pengguna $teacher, int $classId): void
    {
        DB::table('guru_wali_kelas')->insert([
            'tahun_ajaran_id' => 1,
            'guru_id' => $teacher->guru_id,
            'kelas_id' => $classId,
        ]);
    }

    private function createStudentHistory(int $classId, string $status): void
    {
        $studentId = DB::table('siswa_biodata')->insertGetId([
            'nama_lengkap' => 'Siswa Uji',
        ]);

        DB::table('siswa_riwayat_kelas')->insert([
            'siswa_id' => $studentId,
            'tahun_ajaran_id' => 1,
            'kelas_id' => $classId,
            'status' => $status,
        ]);
    }
}
