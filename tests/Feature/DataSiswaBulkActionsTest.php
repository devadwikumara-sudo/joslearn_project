<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\DataSiswaController;
use App\Models\Auth\Pengguna;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DataSiswaBulkActionsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('akun_pengguna', function (Blueprint $table): void {
            $table->id();
            $table->string('peran');
            $table->string('username');
            $table->string('email');
            $table->string('password');
            $table->boolean('aktif')->default(true);
            $table->unsignedBigInteger('guru_id')->nullable();
            $table->unsignedBigInteger('siswa_id')->nullable();
            $table->boolean('wajib_ganti_password')->default(false);
            $table->rememberToken();
            $table->timestamps();
        });
        Schema::create('sekolah_tahun_ajaran', function (Blueprint $table): void {
            $table->id();
            $table->string('nama');
            $table->boolean('sedang_berjalan')->default(false);
            $table->timestamps();
        });
        Schema::create('sekolah_kelas', function (Blueprint $table): void {
            $table->id();
            $table->string('nama');
            $table->string('tingkat');
            $table->timestamps();
        });
        Schema::create('siswa_biodata', function (Blueprint $table): void {
            $table->id();
            $table->string('nisn')->nullable();
            $table->string('nomor_induk')->nullable();
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
            $table->unique(['siswa_id', 'tahun_ajaran_id']);
        });
        Schema::create('siswa_berkas', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('siswa_id');
        });
        Schema::create('sekolah_mapel', function (Blueprint $table): void {
            $table->id();
        });
        Schema::create('siswa_nilai', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('riwayat_kelas_id');
            $table->unsignedBigInteger('mapel_id');
        });
        Schema::create('siswa_kehadiran', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('riwayat_kelas_id');
        });
        Schema::create('siswa_ekskul', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('riwayat_kelas_id');
        });
        Schema::create('catatan_aktivitas', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('pengguna_id')->nullable();
            $table->string('peran')->nullable();
            $table->string('aksi');
            $table->string('keterangan')->nullable();
            $table->string('objek_tipe')->nullable();
            $table->unsignedBigInteger('objek_id')->nullable();
            $table->string('alamat_ip')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        DB::table('sekolah_tahun_ajaran')->insert([
            ['id' => 1, 'nama' => '2025/2026', 'sedang_berjalan' => false],
            ['id' => 2, 'nama' => '2026/2027', 'sedang_berjalan' => true],
        ]);
        DB::table('sekolah_kelas')->insert([
            ['id' => 1, 'nama' => 'X-1', 'tingkat' => 'X'],
            ['id' => 2, 'nama' => 'X-2', 'tingkat' => 'X'],
        ]);
    }

    public function test_selected_delete_only_trashes_selected_students_inside_the_filter(): void
    {
        $admin = $this->createAccount('admin');
        $selectedStudentId = $this->createStudent('Dipilih', 1, 1);
        $otherClassStudentId = $this->createStudent('Kelas lain', 1, 2);
        $unselectedStudentId = $this->createStudent('Tidak dipilih', 1, 1);

        $response = $this->actingAs($admin)->from(route('admin.data-siswa'))
            ->delete(route('admin.data-siswa.bulk-delete'), [
                'tahun_ajaran' => '1',
                'kelas' => 'X-1',
                'konfirmasi' => 'hapus',
                'siswa_ids' => [$selectedStudentId, $otherClassStudentId],
            ]);

        $response->assertRedirect(route('admin.data-siswa'));
        $this->assertSoftDeleted('siswa_biodata', ['id' => $selectedStudentId]);
        $this->assertNotSoftDeleted('siswa_biodata', ['id' => $otherClassStudentId]);
        $this->assertNotSoftDeleted('siswa_biodata', ['id' => $unselectedStudentId]);
        $this->assertDatabaseHas('catatan_aktivitas', [
            'pengguna_id' => $admin->id,
            'peran' => 'admin',
            'aksi' => 'hapus_siswa_massal',
        ]);
    }

    public function test_delete_all_for_current_year_does_not_trash_students_from_other_years(): void
    {
        $this->actingAs($this->createAccount('admin'));
        $previousYearStudentId = $this->createStudent('Tahun sebelumnya', 1, 1);
        $currentYearStudentId = $this->createStudent('Tahun berjalan', 2, 1);
        $currentYearOtherClassStudentId = $this->createStudent('Kelas lain tahun ini', 2, 2);

        $response = $this->from(route('admin.data-siswa'))->delete(route('admin.data-siswa.bulk-delete'), [
            'tahun_ajaran' => '2',
            'kelas' => '*',
            'konfirmasi' => 'hapus',
        ]);

        $response->assertRedirect(route('admin.data-siswa'));
        $this->assertNotSoftDeleted('siswa_biodata', ['id' => $previousYearStudentId]);
        $this->assertSoftDeleted('siswa_biodata', ['id' => $currentYearStudentId]);
        $this->assertSoftDeleted('siswa_biodata', ['id' => $currentYearOtherClassStudentId]);
    }

    public function test_selected_class_move_only_updates_selected_students_and_logs_the_action(): void
    {
        $admin = $this->createAccount('admin');
        $selectedStudentId = $this->createStudent('Dipilih pindah', 1, 1);
        $unselectedStudentId = $this->createStudent('Tetap di kelas', 1, 1);

        $response = $this->actingAs($admin)->from(route('admin.data-siswa'))
            ->post(route('admin.data-siswa.bulk-update'), [
                'tahun_ajaran' => 1,
                'kelas' => 'X-1',
                'target_tahun_ajaran' => 1,
                'target_kelas' => 'X-2',
                'siswa_ids' => [$selectedStudentId],
            ]);

        $response->assertRedirect(route('admin.data-siswa'));
        $this->assertDatabaseHas('siswa_riwayat_kelas', [
            'siswa_id' => $selectedStudentId,
            'tahun_ajaran_id' => 1,
            'kelas_id' => 2,
        ]);
        $this->assertDatabaseHas('siswa_riwayat_kelas', [
            'siswa_id' => $unselectedStudentId,
            'tahun_ajaran_id' => 1,
            'kelas_id' => 1,
        ]);
        $this->assertDatabaseHas('catatan_aktivitas', [
            'pengguna_id' => $admin->id,
            'aksi' => 'pindah_kelas_massal',
        ]);
    }

    public function test_bulk_delete_requires_explicit_confirmation(): void
    {
        $this->actingAs($this->createAccount('admin'));
        $studentId = $this->createStudent('Tidak terhapus', 1, 1);

        $response = $this->delete(route('admin.data-siswa.bulk-delete'), [
            'tahun_ajaran' => '1',
            'kelas' => 'X-1',
        ]);

        $response->assertSessionHasErrors(['konfirmasi']);
        $this->assertNotSoftDeleted('siswa_biodata', ['id' => $studentId]);
    }

    public function test_bulk_delete_rejects_an_unbounded_all_years_filter(): void
    {
        $this->actingAs($this->createAccount('admin'));
        $studentId = $this->createStudent('Tidak terhapus', 1, 1);

        $response = $this->from(route('admin.data-siswa'))->delete(route('admin.data-siswa.bulk-delete'), [
            'tahun_ajaran' => '*',
            'kelas' => '*',
            'konfirmasi' => 'hapus',
        ]);

        $response->assertRedirect(route('admin.data-siswa'));
        $response->assertSessionHas('error', 'Pilih tahun ajaran tertentu atau kelas tertentu agar penghapusan massal memiliki batas yang jelas.');
        $this->assertNotSoftDeleted('siswa_biodata', ['id' => $studentId]);
    }

    public function test_teacher_cannot_use_admin_bulk_delete_endpoint(): void
    {
        $studentId = $this->createStudent('Tetap aman', 1, 1);

        $this->actingAs($this->createAccount('guru'))
            ->delete(route('admin.data-siswa.bulk-delete'), [
                'tahun_ajaran' => '1',
                'kelas' => 'X-1',
                'konfirmasi' => 'hapus',
            ])
            ->assertForbidden();

        $this->assertNotSoftDeleted('siswa_biodata', ['id' => $studentId]);
    }

    public function test_default_data_siswa_list_includes_all_classes_for_the_active_year(): void
    {
        $firstStudentId = $this->createStudent('Kelas X-1', 2, 1);
        $secondStudentId = $this->createStudent('Kelas X-2', 2, 2);
        $previousYearStudentId = $this->createStudent('Tahun sebelumnya', 1, 1);

        $view = app(DataSiswaController::class)->index(Request::create('/admin/data-siswa'));
        $siswaList = $view->getData()['siswaList'];
        $listedIds = $siswaList->getCollection()->pluck('id')->all();
        sort($listedIds);

        $this->assertSame(2, $siswaList->total());
        $this->assertSame([$firstStudentId, $secondStudentId], $listedIds);
        $this->assertNotContains($previousYearStudentId, $listedIds);
    }

    private function createAccount(string $role): Pengguna
    {
        $id = DB::table('akun_pengguna')->insertGetId([
            'peran' => $role,
            'username' => $role.'-test',
            'email' => $role.'@example.test',
            'password' => 'password',
            'aktif' => true,
            'wajib_ganti_password' => false,
        ]);

        return Pengguna::findOrFail($id);
    }

    private function createStudent(string $name, int $yearId, int $classId): int
    {
        $studentId = DB::table('siswa_biodata')->insertGetId([
            'nisn' => '100000000'.(string) $yearId.(string) $classId,
            'nomor_induk' => '500'.$yearId.$classId,
            'nama_lengkap' => $name,
        ]);
        DB::table('siswa_riwayat_kelas')->insert([
            'siswa_id' => $studentId,
            'tahun_ajaran_id' => $yearId,
            'kelas_id' => $classId,
            'status' => 'Aktif',
        ]);

        return $studentId;
    }
}
