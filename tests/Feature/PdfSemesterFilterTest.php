<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PdfSemesterFilterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'guru_wali_kelas',
            'siswa_ekskul',
            'siswa_kehadiran',
            'siswa_nilai',
            'sekolah_mapel',
            'siswa_riwayat_kelas',
            'sekolah_kelas',
            'sekolah_tahun_ajaran',
            'sekolah_pengaturan',
            'sekolah_identitas',
            'siswa_biodata',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('siswa_biodata', function (Blueprint $table): void {
            $table->id();
            $table->string('nama_lengkap');
            $table->string('nisn')->nullable();
            $table->string('nomor_induk')->nullable();
            $table->timestamp('deleted_at')->nullable();
        });
        Schema::create('sekolah_identitas', function (Blueprint $table): void {
            $table->id();
        });
        Schema::create('sekolah_pengaturan', function (Blueprint $table): void {
            $table->id();
            $table->string('kunci')->unique();
            $table->string('isi')->nullable();
        });
        Schema::create('sekolah_tahun_ajaran', function (Blueprint $table): void {
            $table->id();
            $table->string('nama');
        });
        Schema::create('sekolah_kelas', function (Blueprint $table): void {
            $table->id();
            $table->string('nama');
            $table->string('tingkat');
        });
        Schema::create('siswa_riwayat_kelas', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('siswa_id');
            $table->unsignedBigInteger('tahun_ajaran_id');
            $table->unsignedBigInteger('kelas_id');
            $table->string('status')->nullable();
            $table->timestamp('deleted_at')->nullable();
        });
        Schema::create('sekolah_mapel', function (Blueprint $table): void {
            $table->id();
            $table->string('kode');
        });
        Schema::create('siswa_nilai', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('riwayat_kelas_id');
            $table->string('semester');
            $table->unsignedBigInteger('mapel_id');
            $table->decimal('nilai_angka', 5, 2);
        });
        Schema::create('siswa_kehadiran', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('riwayat_kelas_id');
            $table->string('semester');
            $table->unsignedSmallInteger('sakit')->default(0);
            $table->unsignedSmallInteger('izin')->default(0);
            $table->unsignedSmallInteger('alpa')->default(0);
            $table->text('kokurikuler')->nullable();
        });
        Schema::create('siswa_ekskul', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('riwayat_kelas_id');
            $table->string('semester');
            $table->string('nama_kegiatan');
            $table->string('predikat')->nullable();
        });
        Schema::create('guru_wali_kelas', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('kelas_id');
            $table->unsignedBigInteger('tahun_ajaran_id');
        });

        DB::table('siswa_biodata')->insert([
            'id' => 1,
            'nama_lengkap' => 'Siswa Enam Semester',
            'nisn' => '1234567890',
            'nomor_induk' => '1234',
        ]);
        DB::table('sekolah_tahun_ajaran')->insert([
            ['id' => 1, 'nama' => '2023/2024'],
            ['id' => 2, 'nama' => '2024/2025'],
            ['id' => 3, 'nama' => '2025/2026'],
        ]);
        DB::table('sekolah_kelas')->insert([
            ['id' => 1, 'nama' => 'X-1', 'tingkat' => 'X'],
            ['id' => 2, 'nama' => 'XI-1', 'tingkat' => 'XI'],
            ['id' => 3, 'nama' => 'XII-1', 'tingkat' => 'XII'],
        ]);
        DB::table('siswa_riwayat_kelas')->insert([
            ['id' => 1, 'siswa_id' => 1, 'tahun_ajaran_id' => 1, 'kelas_id' => 1, 'status' => 'Aktif'],
            ['id' => 2, 'siswa_id' => 1, 'tahun_ajaran_id' => 2, 'kelas_id' => 2, 'status' => 'Aktif'],
            ['id' => 3, 'siswa_id' => 1, 'tahun_ajaran_id' => 3, 'kelas_id' => 3, 'status' => 'Lulus'],
        ]);
        DB::table('sekolah_mapel')->insert(['id' => 1, 'kode' => 'mtk']);
        DB::table('siswa_nilai')->insert([
            ['riwayat_kelas_id' => 1, 'semester' => 'Ganjil', 'mapel_id' => 1, 'nilai_angka' => 81],
            ['riwayat_kelas_id' => 2, 'semester' => 'Ganjil', 'mapel_id' => 1, 'nilai_angka' => 88],
            ['riwayat_kelas_id' => 3, 'semester' => 'Genap', 'mapel_id' => 1, 'nilai_angka' => 96],
        ]);
    }

    protected function tearDown(): void
    {
        foreach ([
            'guru_wali_kelas',
            'siswa_ekskul',
            'siswa_kehadiran',
            'siswa_nilai',
            'sekolah_mapel',
            'siswa_riwayat_kelas',
            'sekolah_kelas',
            'sekolah_tahun_ajaran',
            'sekolah_identitas',
            'siswa_biodata',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_pdf_semester_six_filter_renders_only_the_matching_grade_history(): void
    {
        $response = $this->withoutMiddleware()->get(route('admin.data-siswa.pdf', [
            'id' => 1,
            'bio' => 0,
            'nilai' => 1,
            'hadir' => 0,
            'ekskul' => 0,
            'naik' => 0,
            'sem' => 6,
            'kelas_filter' => 'XII-1',
        ]));

        $response->assertOk()
            ->assertSee('Semester 6')
            ->assertSee('96')
            ->assertDontSee('81')
            ->assertDontSee('88');
    }
}
