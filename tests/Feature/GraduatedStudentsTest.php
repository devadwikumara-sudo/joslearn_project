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
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('siswa_riwayat_kelas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('siswa_id');
            $table->string('status');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('siswa_riwayat_kelas');
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
}
