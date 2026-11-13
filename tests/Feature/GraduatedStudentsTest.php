<?php

namespace Tests\Feature;

use App\Models\SiswaBiodata;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GraduatedStudentsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('siswa_riwayat_kelas');
        Schema::dropIfExists('siswa_biodata');

        Schema::create('siswa_biodata', function (Blueprint $table): void {
            $table->id();
            $table->string('tahun_lulus', 4)->nullable();
            $table->string('alasan_keluar')->nullable();
            $table->date('tanggal_keluar')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('sekolah_tahun_ajaran', function (Blueprint $table): void {
            $table->id();
            $table->string('nama');
            $table->boolean('sedang_berjalan')->default(false);
            $table->timestamps();
        });
        Schema::create('siswa_riwayat_kelas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('siswa_id');
            $table->foreignId('tahun_ajaran_id')->nullable();
            $table->string('status');
            $table->softDeletes();
            $table->timestamps();
        });

        DB::table('sekolah_tahun_ajaran')->insert([
            'id' => 1,
            'nama' => '2026/2027',
            'sedang_berjalan' => true,
        ]);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('siswa_riwayat_kelas');
        Schema::dropIfExists('sekolah_tahun_ajaran');
        Schema::dropIfExists('siswa_biodata');

        parent::tearDown();
    }

    public function test_graduated_scope_includes_all_graduation_signals_but_not_non_graduates(): void
    {
        DB::table('siswa_biodata')->insert([
            ['id' => 1, 'tahun_lulus' => null, 'alasan_keluar' => 'Lulus', 'deleted_at' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'tahun_lulus' => '2025', 'alasan_keluar' => null, 'deleted_at' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'tahun_lulus' => null, 'alasan_keluar' => null, 'deleted_at' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'tahun_lulus' => null, 'alasan_keluar' => 'Belum Lulus', 'deleted_at' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'tahun_lulus' => null, 'alasan_keluar' => 'Pindah keluar', 'deleted_at' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('siswa_riwayat_kelas')->insert([
            ['siswa_id' => 3, 'status' => 'Lulus', 'deleted_at' => null, 'created_at' => now(), 'updated_at' => now()],
            ['siswa_id' => 4, 'status' => 'Aktif', 'deleted_at' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->assertSame(
            [1, 2, 3],
            SiswaBiodata::graduated()->orderBy('id')->pluck('id')->all()
        );
    }

    public function test_active_scope_includes_only_students_with_an_active_current_year_history(): void
    {
        DB::table('siswa_biodata')->insert([
            ['id' => 2, 'tahun_lulus' => '2025', 'alasan_keluar' => null, 'tanggal_keluar' => null, 'deleted_at' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'tahun_lulus' => null, 'alasan_keluar' => 'Belum Lulus', 'tanggal_keluar' => null, 'deleted_at' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'tahun_lulus' => null, 'alasan_keluar' => 'Pindah keluar', 'tanggal_keluar' => null, 'deleted_at' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'tahun_lulus' => null, 'alasan_keluar' => 'Belum Lulus', 'tanggal_keluar' => null, 'deleted_at' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'tahun_lulus' => null, 'alasan_keluar' => null, 'tanggal_keluar' => '2026-10-01', 'deleted_at' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 8, 'tahun_lulus' => null, 'alasan_keluar' => null, 'tanggal_keluar' => null, 'deleted_at' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('siswa_riwayat_kelas')->insert([
            ['siswa_id' => 4, 'tahun_ajaran_id' => 1, 'status' => 'Aktif', 'deleted_at' => null, 'created_at' => now(), 'updated_at' => now()],
            ['siswa_id' => 5, 'tahun_ajaran_id' => 1, 'status' => 'Pindah keluar', 'deleted_at' => null, 'created_at' => now(), 'updated_at' => now()],
            ['siswa_id' => 6, 'tahun_ajaran_id' => 1, 'status' => 'Aktif', 'deleted_at' => null, 'created_at' => now(), 'updated_at' => now()],
            ['siswa_id' => 7, 'tahun_ajaran_id' => 1, 'status' => 'Aktif', 'deleted_at' => null, 'created_at' => now(), 'updated_at' => now()],
            ['siswa_id' => 8, 'tahun_ajaran_id' => null, 'status' => 'Aktif', 'deleted_at' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->assertSame([4, 6], SiswaBiodata::aktif()->orderBy('id')->pluck('id')->all());
    }
}
