<?php

namespace Tests\Feature;

use App\Models\Auth\Pengguna;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tests\TestCase;

#[RunTestsInSeparateProcesses]
class ImportBatchManagementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'catatan_aktivitas',
            'import_baris',
            'import_unggahan',
            'siswa_nilai',
            'siswa_kehadiran',
            'siswa_ekskul',
            'siswa_riwayat_kelas',
            'siswa_biodata',
            'sekolah_mapel',
            'sekolah_kelas',
            'sekolah_tahun_ajaran',
            'akun_pengguna',
        ] as $table) {
            Schema::dropIfExists($table);
        }

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
            $table->string('nama');
            $table->string('tingkat');
            $table->timestamps();
        });
        Schema::create('siswa_biodata', function (Blueprint $table): void {
            $table->id();
            $table->string('nama_lengkap');
            $table->string('nisn')->nullable();
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
        Schema::create('sekolah_mapel', function (Blueprint $table): void {
            $table->id();
            $table->string('kode');
            $table->string('nama');
            $table->timestamps();
        });
        Schema::create('import_unggahan', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('pengguna_id');
            $table->string('jenis');
            $table->string('nama_file');
            $table->string('lokasi');
            $table->string('status');
            $table->unsignedBigInteger('tahun_ajaran_id')->nullable();
            $table->unsignedBigInteger('kelas_id')->nullable();
            $table->string('semester')->nullable();
            $table->unsignedInteger('jumlah_baris')->default(0);
            $table->unsignedInteger('jumlah_tersimpan')->default(0);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('selesai_at')->nullable();
        });
        Schema::create('import_baris', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('unggahan_id');
            $table->unsignedInteger('nomor_baris_excel');
            $table->text('data_asli')->nullable();
            $table->text('data_bersih')->nullable();
            $table->string('status');
            $table->text('pesan')->nullable();
        });

        foreach (['siswa_nilai', 'siswa_kehadiran', 'siswa_ekskul'] as $tableName) {
            Schema::create($tableName, function (Blueprint $table) use ($tableName): void {
                $table->id();
                $table->unsignedBigInteger('riwayat_kelas_id');
                $table->string('semester');
                if ($tableName === 'siswa_nilai') {
                    $table->unsignedBigInteger('mapel_id');
                    $table->decimal('nilai_angka', 5, 2);
                } elseif ($tableName === 'siswa_kehadiran') {
                    $table->unsignedSmallInteger('sakit')->default(0);
                    $table->unsignedSmallInteger('izin')->default(0);
                    $table->unsignedSmallInteger('alpa')->default(0);
                    $table->text('kokurikuler')->nullable();
                } else {
                    $table->string('nama_kegiatan');
                    $table->string('predikat')->nullable();
                }
                $table->timestamps();
            });
        }
        $migration = require base_path('database/migrations/2026_11_11_233536_add_import_batch_tracking_to_academic_records.php');
        $migration->up();

        Schema::create('catatan_aktivitas', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('pengguna_id')->nullable();
            $table->string('peran')->nullable();
            $table->string('aksi');
            $table->text('keterangan')->nullable();
            $table->string('objek_tipe')->nullable();
            $table->unsignedBigInteger('objek_id')->nullable();
            $table->string('alamat_ip')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function test_admin_deletes_only_data_created_by_the_selected_batch_and_logs_the_action(): void
    {
        $admin = $this->createUser('admin');
        [$yearId, $historyId, $batchId] = $this->createNilaiBatch($admin);
        $ownedRecordId = $this->createNilaiRecord($historyId, 80, $batchId);
        $otherBatchRecordId = $this->createNilaiRecord($historyId, 75, $this->createBatch($admin, $yearId, 'nilai'));
        $untrackedRecordId = $this->createNilaiRecord($historyId, 70, null);

        $response = $this->actingAs($admin)->delete(route('admin.import.batch.destroy', $batchId), [
            'tahun_ajaran_id' => $yearId,
            'konfirmasi' => 'HAPUS BATCH '.$batchId,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseMissing('siswa_nilai', ['id' => $ownedRecordId]);
        $this->assertDatabaseHas('siswa_nilai', ['id' => $otherBatchRecordId, 'nilai_angka' => 75]);
        $this->assertDatabaseHas('siswa_nilai', ['id' => $untrackedRecordId, 'nilai_angka' => 70]);
        $this->assertDatabaseHas('siswa_riwayat_kelas', ['id' => $historyId]);
        $this->assertDatabaseHas('import_unggahan', ['id' => $batchId, 'status' => 'dibatalkan']);
        $this->assertDatabaseHas('catatan_aktivitas', [
            'aksi' => 'hapus_batch_import_nilai',
            'objek_tipe' => 'import_unggahan',
            'objek_id' => $batchId,
        ]);
    }

    public function test_teacher_cannot_delete_a_batch(): void
    {
        $teacher = $this->createUser('guru');
        [$yearId, $historyId, $batchId] = $this->createNilaiBatch($teacher);
        $recordId = $this->createNilaiRecord($historyId, 80, $batchId);

        $response = $this->actingAs($teacher)->delete(route('admin.import.batch.destroy', $batchId), [
            'tahun_ajaran_id' => $yearId,
            'konfirmasi' => 'HAPUS BATCH '.$batchId,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('siswa_nilai', ['id' => $recordId, 'nilai_angka' => 80]);
        $this->assertDatabaseMissing('catatan_aktivitas', ['aksi' => 'hapus_batch_import_nilai']);
    }

    public function test_admin_can_view_and_edit_records_in_a_batch(): void
    {
        $admin = $this->createUser('admin');
        [$yearId, $historyId, $batchId] = $this->createNilaiBatch($admin);
        $recordId = $this->createNilaiRecord($historyId, 80, $batchId);
        DB::table('import_baris')->insert([
            'unggahan_id' => $batchId,
            'nomor_baris_excel' => 2,
            'data_asli' => json_encode(['nisn' => '0012345678']),
            'data_bersih' => json_encode(['nisn' => '0012345678']),
            'status' => 'tersimpan',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.import.batch.show', $batchId))
            ->assertOk()
            ->assertSee('Baris file yang diimpor')
            ->assertSee('0012345678');

        $this->put(route('admin.import.batch.records.update', [$batchId, $recordId]), [
            'nilai_angka' => 91,
        ])->assertRedirect();

        $this->assertDatabaseHas('siswa_nilai', [
            'id' => $recordId,
            'import_unggahan_id' => $batchId,
            'nilai_angka' => 91,
        ]);
        $this->assertDatabaseHas('catatan_aktivitas', ['aksi' => 'edit_batch_import_nilai']);
    }

    public function test_import_page_lists_batches_for_the_selected_academic_year(): void
    {
        $admin = $this->createUser('admin');
        [$yearId, $historyId, $batchId] = $this->createNilaiBatch($admin);
        $this->createNilaiRecord($historyId, 80, $batchId);

        $this->actingAs($admin)
            ->get(route('admin.import.nilai', ['tahun_ajaran_id' => $yearId]))
            ->assertOk()
            ->assertSee('Data hasil impor 2026/2027')
            ->assertSee('X-1')
            ->assertSee('Ganjil')
            ->assertSee('Hapus batch')
            ->assertSee('Hapus semua Nilai tahun ini');
    }

    public function test_admin_must_type_the_year_specific_confirmation_to_delete_all_values(): void
    {
        $admin = $this->createUser('admin');
        [$yearId, $historyId] = $this->createAcademicContext();
        $recordId = $this->createNilaiRecord($historyId, 80, null);
        $otherYearId = DB::table('sekolah_tahun_ajaran')->insertGetId([
            'nama' => '2025/2026',
            'sedang_berjalan' => false,
        ]);
        $studentId = DB::table('siswa_riwayat_kelas')->where('id', $historyId)->value('siswa_id');
        $classId = DB::table('siswa_riwayat_kelas')->where('id', $historyId)->value('kelas_id');
        $otherHistoryId = DB::table('siswa_riwayat_kelas')->insertGetId([
            'siswa_id' => $studentId,
            'tahun_ajaran_id' => $otherYearId,
            'kelas_id' => $classId,
            'status' => 'Aktif',
        ]);
        $otherYearRecordId = $this->createNilaiRecord($otherHistoryId, 75, null);
        $attendanceId = DB::table('siswa_kehadiran')->insertGetId([
            'riwayat_kelas_id' => $historyId,
            'semester' => 'Ganjil',
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.import.batch.destroy-all', 'nilai'), [
                'tahun_ajaran_id' => $yearId,
                'konfirmasi' => 'HAPUS SEMUA NILAI',
            ])
            ->assertSessionHasErrors('konfirmasi');

        $this->assertDatabaseHas('siswa_nilai', ['id' => $recordId]);

        $this->delete(route('admin.import.batch.destroy-all', 'nilai'), [
            'tahun_ajaran_id' => $yearId,
            'konfirmasi' => 'HAPUS SEMUA NILAI 2026/2027',
        ])->assertRedirect();

        $this->assertDatabaseMissing('siswa_nilai', ['id' => $recordId]);
        $this->assertDatabaseHas('siswa_nilai', ['id' => $otherYearRecordId, 'nilai_angka' => 75]);
        $this->assertDatabaseHas('siswa_kehadiran', ['id' => $attendanceId]);
        $this->assertDatabaseHas('siswa_riwayat_kelas', ['id' => $historyId]);
        $this->assertDatabaseHas('catatan_aktivitas', ['aksi' => 'hapus_semua_import_nilai']);
    }

    private function createUser(string $role): Pengguna
    {
        return Pengguna::query()->create([
            'peran' => $role,
            'username' => $role.'-'.uniqid(),
            'password' => 'unused',
            'aktif' => true,
        ]);
    }

    /**
     * @return array{int, int, int}
     */
    private function createNilaiBatch(Pengguna $user): array
    {
        [$yearId, $historyId] = $this->createAcademicContext();
        $batchId = $this->createBatch($user, $yearId, 'nilai');

        return [$yearId, $historyId, $batchId];
    }

    /**
     * @return array{int, int}
     */
    private function createAcademicContext(): array
    {
        $yearId = DB::table('sekolah_tahun_ajaran')->insertGetId([
            'nama' => '2026/2027',
            'sedang_berjalan' => true,
        ]);
        $classId = DB::table('sekolah_kelas')->insertGetId([
            'nama' => 'X-1',
            'tingkat' => 'X',
        ]);
        $studentId = DB::table('siswa_biodata')->insertGetId([
            'nama_lengkap' => 'Siswa Uji',
            'nisn' => '0012345678',
        ]);
        $historyId = DB::table('siswa_riwayat_kelas')->insertGetId([
            'siswa_id' => $studentId,
            'tahun_ajaran_id' => $yearId,
            'kelas_id' => $classId,
            'status' => 'Aktif',
        ]);
        DB::table('sekolah_mapel')->insert([
            'kode' => 'mtk',
            'nama' => 'Matematika',
        ]);

        return [$yearId, $historyId];
    }

    private function createBatch(Pengguna $user, int $yearId, string $type): int
    {
        return DB::table('import_unggahan')->insertGetId([
            'pengguna_id' => $user->id,
            'jenis' => $type,
            'nama_file' => 'nilai.xlsx',
            'lokasi' => 'browser:nilai.xlsx',
            'status' => 'selesai',
            'tahun_ajaran_id' => $yearId,
            'kelas_id' => 1,
            'semester' => 'Ganjil',
            'jumlah_baris' => 1,
            'jumlah_tersimpan' => 1,
            'created_at' => now(),
            'selesai_at' => now(),
        ]);
    }

    private function createNilaiRecord(int $historyId, float $score, ?int $batchId): int
    {
        return DB::table('siswa_nilai')->insertGetId([
            'riwayat_kelas_id' => $historyId,
            'semester' => 'Ganjil',
            'import_unggahan_id' => $batchId,
            'import_terakhir_unggahan_id' => $batchId,
            'mapel_id' => 1,
            'nilai_angka' => $score,
        ]);
    }
}
