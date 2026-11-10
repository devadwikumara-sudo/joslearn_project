<?php

namespace Tests\Feature;

use App\Models\Auth\Pengguna;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tests\TestCase;

#[RunTestsInSeparateProcesses]
class TeacherClassPromotionTest extends TestCase
{
    private const TABLES = [
        'catatan_aktivitas',
        'catatan_kenaikan_kelas',
        'siswa_riwayat_kelas',
        'siswa_biodata',
        'guru_wali_kelas',
        'guru_biodata',
        'sekolah_kelas',
        'sekolah_tahun_ajaran',
        'akun_pengguna',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        foreach (self::TABLES as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('akun_pengguna', function (Blueprint $table): void {
            $table->id();
            $table->string('peran');
            $table->string('username')->unique();
            $table->string('email')->nullable();
            $table->timestamp('email_diverifikasi_at')->nullable();
            $table->string('password');
            $table->boolean('aktif')->default(true);
            $table->unsignedBigInteger('guru_id')->nullable();
            $table->unsignedBigInteger('siswa_id')->nullable();
            $table->boolean('wajib_ganti_password')->default(false);
            $table->string('remember_token')->nullable();
            $table->timestamps();
        });

        Schema::create('sekolah_tahun_ajaran', function (Blueprint $table): void {
            $table->id();
            $table->string('nama', 9)->unique();
            $table->boolean('sedang_berjalan')->default(false);
            $table->timestamps();
        });

        Schema::create('sekolah_kelas', function (Blueprint $table): void {
            $table->id();
            $table->string('nama')->unique();
            $table->string('tingkat');
            $table->timestamps();
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
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('guru_wali_kelas', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tahun_ajaran_id');
            $table->unsignedBigInteger('kelas_id');
            $table->unsignedBigInteger('guru_id');
            $table->timestamps();
        });

        Schema::create('siswa_biodata', function (Blueprint $table): void {
            $table->id();
            $table->string('nama_lengkap');
            $table->string('nisn')->nullable();
            $table->string('tahun_lulus', 4)->nullable();
            $table->string('alasan_keluar')->nullable();
            $table->string('kelas_keluar')->nullable();
            $table->date('tanggal_keluar')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('siswa_riwayat_kelas', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('siswa_id');
            $table->unsignedBigInteger('tahun_ajaran_id');
            $table->unsignedBigInteger('kelas_id');
            $table->string('status')->default('Aktif');
            $table->string('keputusan_kenaikan')->nullable();
            $table->unsignedBigInteger('kelas_tujuan_id')->nullable();
            $table->date('tanggal_keputusan_kenaikan')->nullable();
            $table->unsignedBigInteger('wali_guru_id')->nullable();
            $table->string('nama_wali_kelas')->nullable();
            $table->string('nip_wali_kelas')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('catatan_kenaikan_kelas', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('pengguna_id')->nullable();
            $table->unsignedBigInteger('kelas_asal_id');
            $table->unsignedBigInteger('tahun_ajaran_asal_id');
            $table->unsignedBigInteger('tahun_ajaran_tujuan_id')->nullable();
            $table->unsignedSmallInteger('jumlah_siswa')->default(0);
            $table->text('riwayat_baru')->nullable();
            $table->text('data_sebelumnya')->nullable();
            $table->timestamp('dibatalkan_at')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('catatan_aktivitas', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('pengguna_id')->nullable();
            $table->string('peran', 10)->nullable();
            $table->string('aksi');
            $table->string('keterangan', 500)->nullable();
            $table->string('objek_tipe')->nullable();
            $table->unsignedBigInteger('objek_id')->nullable();
            $table->string('alamat_ip', 45)->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    protected function tearDown(): void
    {
        foreach (self::TABLES as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_wali_can_promote_and_cancel_only_their_class_with_destination_homeroom_snapshot(): void
    {
        [$year, $nextYear, $sourceClass, $destinationClass, $teacher, $student] = $this->seedPromotionData();
        $targetHomeroom = $this->createTeacher('Guru Tujuan', '1987654321');
        DB::table('guru_wali_kelas')->insert([
            'tahun_ajaran_id' => $nextYear,
            'kelas_id' => $destinationClass,
            'guru_id' => $targetHomeroom,
        ]);
        $user = $this->createUser('guru', 'guru-x1', $teacher);

        $this->actingAs($user)->post(route('guru.kenaikan-kelas.proses'), [
            'ta_aktif' => '2024/2025',
            'ta_berikutnya' => '2030/2031',
            'kelas_asal' => 'X-1',
            'keputusan' => [$student => 'Naik'],
            'kelas_tujuan' => [$student => 'XI-1'],
        ])->assertRedirect(route('guru.kenaikan-kelas', ['kelas' => 'X-1']));

        $this->assertDatabaseHas('siswa_riwayat_kelas', [
            'siswa_id' => $student,
            'tahun_ajaran_id' => $nextYear,
            'kelas_id' => $destinationClass,
            'wali_guru_id' => $targetHomeroom,
            'nama_wali_kelas' => 'Guru Tujuan',
            'nip_wali_kelas' => '1987654321',
        ]);
        $this->assertDatabaseHas('siswa_riwayat_kelas', [
            'siswa_id' => $student,
            'tahun_ajaran_id' => $year,
            'keputusan_kenaikan' => 'NAIK',
            'kelas_tujuan_id' => $destinationClass,
        ]);
        $this->assertDatabaseHas('catatan_aktivitas', ['aksi' => 'kenaikan_kelas']);

        $batchId = DB::table('catatan_kenaikan_kelas')->value('id');
        $this->from(route('guru.kenaikan-kelas'))->actingAs($user)
            ->post(route('guru.kenaikan-kelas.batalkan', $batchId))
            ->assertRedirect(route('guru.kenaikan-kelas'));

        $this->assertTrue(DB::table('siswa_riwayat_kelas')
            ->where('siswa_id', $student)
            ->where('tahun_ajaran_id', $nextYear)
            ->whereNotNull('deleted_at')
            ->exists());
        $this->assertDatabaseHas('siswa_riwayat_kelas', [
            'siswa_id' => $student,
            'tahun_ajaran_id' => $year,
            'keputusan_kenaikan' => null,
        ]);
        $this->assertDatabaseHas('catatan_aktivitas', ['aksi' => 'batalkan_kenaikan']);
    }

    public function test_wali_cannot_promote_or_cancel_another_class(): void
    {
        [$year, , , , $teacher, $student] = $this->seedPromotionData();
        $otherClass = DB::table('sekolah_kelas')->insertGetId([
            'nama' => 'XI-2',
            'tingkat' => 'XI',
        ]);
        $user = $this->createUser('guru', 'guru-x1', $teacher);

        $this->actingAs($user)->post(route('guru.kenaikan-kelas.proses'), [
            'kelas_asal' => 'XI-1',
            'keputusan' => [$student => 'Naik'],
        ])->assertForbidden();

        $batchId = DB::table('catatan_kenaikan_kelas')->insertGetId([
            'pengguna_id' => $user->id,
            'kelas_asal_id' => $otherClass,
            'tahun_ajaran_asal_id' => $year,
            'jumlah_siswa' => 1,
            'created_at' => now(),
        ]);
        $this->actingAs($user)->post(route('guru.kenaikan-kelas.batalkan', $batchId))
            ->assertForbidden();
        $this->assertDatabaseHas('catatan_kenaikan_kelas', [
            'id' => $batchId,
            'dibatalkan_at' => null,
        ]);
    }

    public function test_wali_only_sees_the_promotion_page_for_their_assigned_class(): void
    {
        [, , , , $teacher] = $this->seedPromotionData();
        $user = $this->createUser('guru', 'guru-x1', $teacher);

        $this->actingAs($user)->get(route('guru.kenaikan-kelas'))
            ->assertOk()
            ->assertSee('Kenaikan Kelas');
        $this->actingAs($user)->get(route('guru.kenaikan-kelas', ['kelas' => 'XI-1']))
            ->assertForbidden();
    }

    public function test_assigning_a_new_homeroom_teacher_updates_only_histories_in_that_year(): void
    {
        [$year, , $sourceClass, , $teacher, $student] = $this->seedPromotionData();
        $oldYear = DB::table('sekolah_tahun_ajaran')->insertGetId([
            'nama' => '2024/2025',
            'sedang_berjalan' => false,
        ]);
        DB::table('siswa_riwayat_kelas')->insert([
            'siswa_id' => $student,
            'tahun_ajaran_id' => $oldYear,
            'kelas_id' => $sourceClass,
            'status' => 'Aktif',
            'wali_guru_id' => $teacher,
            'nama_wali_kelas' => 'Guru Lama',
            'nip_wali_kelas' => '1234567890',
        ]);
        $admin = $this->createUser('admin', 'admin', null);

        $this->actingAs($admin)->post(route('admin.data-guru.store'), [
            'nama' => 'Guru Pengganti',
            'nip' => '1098765432',
            'is_wali' => '1',
            'wali_kelas' => 'X-1',
        ])->assertRedirect(route('admin.data-guru'));

        $newTeacherId = DB::table('guru_biodata')->where('nip', '1098765432')->value('id');
        $this->assertDatabaseHas('siswa_riwayat_kelas', [
            'siswa_id' => $student,
            'tahun_ajaran_id' => $year,
            'wali_guru_id' => $newTeacherId,
            'nama_wali_kelas' => 'Guru Pengganti',
            'nip_wali_kelas' => '1098765432',
        ]);
        $this->assertDatabaseHas('siswa_riwayat_kelas', [
            'siswa_id' => $student,
            'tahun_ajaran_id' => $oldYear,
            'wali_guru_id' => $teacher,
            'nama_wali_kelas' => 'Guru Lama',
            'nip_wali_kelas' => '1234567890',
        ]);
    }

    private function seedPromotionData(): array
    {
        $year = DB::table('sekolah_tahun_ajaran')->insertGetId([
            'nama' => '2025/2026',
            'sedang_berjalan' => true,
        ]);
        $nextYear = DB::table('sekolah_tahun_ajaran')->insertGetId([
            'nama' => '2026/2027',
            'sedang_berjalan' => false,
        ]);
        $sourceClass = DB::table('sekolah_kelas')->insertGetId(['nama' => 'X-1', 'tingkat' => 'X']);
        $destinationClass = DB::table('sekolah_kelas')->insertGetId(['nama' => 'XI-1', 'tingkat' => 'XI']);
        $teacher = $this->createTeacher('Guru X1', '1234567890');
        DB::table('guru_wali_kelas')->insert([
            'tahun_ajaran_id' => $year,
            'kelas_id' => $sourceClass,
            'guru_id' => $teacher,
        ]);
        $student = DB::table('siswa_biodata')->insertGetId([
            'nama_lengkap' => 'Siswa Uji',
            'nisn' => '0000000001',
        ]);
        DB::table('siswa_riwayat_kelas')->insert([
            'siswa_id' => $student,
            'tahun_ajaran_id' => $year,
            'kelas_id' => $sourceClass,
            'status' => 'Aktif',
        ]);

        return [$year, $nextYear, $sourceClass, $destinationClass, $teacher, $student];
    }

    private function createTeacher(string $name, string $nip): int
    {
        return (int) DB::table('guru_biodata')->insertGetId([
            'nama' => $name,
            'nip' => $nip,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createUser(string $role, string $username, ?int $teacherId): Pengguna
    {
        return Pengguna::create([
            'peran' => $role,
            'username' => $username,
            'password' => 'test-password',
            'aktif' => true,
            'guru_id' => $teacherId,
            'wajib_ganti_password' => false,
        ]);
    }
}
