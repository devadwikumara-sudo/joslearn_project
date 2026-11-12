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
            'guru_biodata',
            'siswa_ekskul',
            'siswa_kehadiran',
            'siswa_nilai',
            'sekolah_mapel_per_tingkat',
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
            $table->string('keputusan_kenaikan')->nullable();
            $table->unsignedBigInteger('kelas_tujuan_id')->nullable();
            $table->string('nama_wali_kelas')->nullable();
            $table->string('nip_wali_kelas')->nullable();
            $table->timestamp('deleted_at')->nullable();
        });
        Schema::create('sekolah_mapel', function (Blueprint $table): void {
            $table->id();
            $table->string('kode');
            $table->string('nama')->nullable();
        });
        Schema::create('sekolah_mapel_per_tingkat', function (Blueprint $table): void {
            $table->id();
            $table->string('tingkat');
            $table->unsignedBigInteger('mapel_id');
            $table->string('kelompok_mapel');
            $table->unsignedInteger('urutan')->default(0);
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
            $table->unsignedBigInteger('guru_id')->nullable();
        });
        Schema::create('guru_biodata', function (Blueprint $table): void {
            $table->id();
            $table->string('nama');
            $table->string('nip')->nullable();
            $table->timestamp('deleted_at')->nullable();
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
        DB::table('sekolah_mapel')->insert(['id' => 1, 'kode' => 'mtk', 'nama' => 'Matematika']);
        DB::table('sekolah_mapel_per_tingkat')->insert([
            ['tingkat' => 'X', 'mapel_id' => 1, 'kelompok_mapel' => 'Mapel', 'urutan' => 1],
            ['tingkat' => 'XI', 'mapel_id' => 1, 'kelompok_mapel' => 'Mapel', 'urutan' => 1],
            ['tingkat' => 'XII', 'mapel_id' => 1, 'kelompok_mapel' => 'Mapel', 'urutan' => 1],
        ]);
        DB::table('siswa_nilai')->insert([
            ['riwayat_kelas_id' => 1, 'semester' => 'Ganjil', 'mapel_id' => 1, 'nilai_angka' => 81],
            ['riwayat_kelas_id' => 1, 'semester' => 'Genap', 'mapel_id' => 1, 'nilai_angka' => 82],
            ['riwayat_kelas_id' => 2, 'semester' => 'Ganjil', 'mapel_id' => 1, 'nilai_angka' => 88],
            ['riwayat_kelas_id' => 2, 'semester' => 'Genap', 'mapel_id' => 1, 'nilai_angka' => 89],
            ['riwayat_kelas_id' => 3, 'semester' => 'Ganjil', 'mapel_id' => 1, 'nilai_angka' => 94],
            ['riwayat_kelas_id' => 3, 'semester' => 'Genap', 'mapel_id' => 1, 'nilai_angka' => 96],
        ]);
    }

    protected function tearDown(): void
    {
        foreach ([
            'guru_wali_kelas',
            'guru_biodata',
            'siswa_ekskul',
            'siswa_kehadiran',
            'siswa_nilai',
            'sekolah_mapel_per_tingkat',
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
            ->assertSee('>96</td>', false)
            ->assertDontSee('>81</td>', false)
            ->assertDontSee('>88</td>', false);
    }

    public function test_pdf_semester_number_and_term_filters_render_only_the_matching_scores(): void
    {
        $semesterScores = [1 => 81, 2 => 82, 3 => 88, 4 => 89, 5 => 94, 6 => 96];

        foreach ($semesterScores as $semester => $score) {
            $response = $this->withoutMiddleware()->get(route('admin.data-siswa.pdf', [
                'id' => 1,
                'bio' => 0,
                'nilai' => 1,
                'hadir' => 0,
                'ekskul' => 0,
                'naik' => 0,
                'sem' => $semester,
            ]));

            $response->assertOk()
                ->assertSee('value="'.$semester.'" selected', false)
                ->assertSee('>'.$score.'</td>', false);
            foreach ($semesterScores as $otherSemester => $otherScore) {
                if ($otherSemester !== $semester) {
                    $response->assertDontSee('>'.$otherScore.'</td>', false);
                }
            }
        }

        $ganjilResponse = $this->get(route('admin.data-siswa.pdf', [
            'id' => 1,
            'bio' => 0,
            'nilai' => 1,
            'hadir' => 0,
            'ekskul' => 0,
            'naik' => 0,
            'sem' => 'Ganjil',
        ]));

        $ganjilResponse->assertOk()
            ->assertSee('>81</td>', false)
            ->assertSee('>88</td>', false)
            ->assertSee('>94</td>', false)
            ->assertDontSee('>82</td>', false)
            ->assertDontSee('>89</td>', false)
            ->assertDontSee('>96</td>', false);

        $genapResponse = $this->get(route('admin.data-siswa.pdf', [
            'id' => 1,
            'bio' => 0,
            'nilai' => 1,
            'hadir' => 0,
            'ekskul' => 0,
            'naik' => 0,
            'sem' => 'Genap',
        ]));

        $genapResponse->assertOk()
            ->assertSee('>82</td>', false)
            ->assertSee('>89</td>', false)
            ->assertSee('>96</td>', false)
            ->assertDontSee('>81</td>', false)
            ->assertDontSee('>88</td>', false)
            ->assertDontSee('>94</td>', false);
    }

    public function test_pdf_uses_each_grade_curriculum_and_saved_promotion_and_homeroom_data(): void
    {
        DB::table('sekolah_kelas')->insert([
            ['id' => 4, 'nama' => 'XI-2', 'tingkat' => 'XI'],
        ]);
        DB::table('siswa_riwayat_kelas')->where('id', 1)->update([
            'keputusan_kenaikan' => 'NAIK',
            'kelas_tujuan_id' => 4,
            'nama_wali_kelas' => 'Wali X Tersimpan',
            'nip_wali_kelas' => 'NIP-X-TERSIMPAN',
        ]);
        DB::table('siswa_riwayat_kelas')->where('id', 2)->update([
            'keputusan_kenaikan' => 'TIDAK NAIK',
            'kelas_tujuan_id' => null,
            'nama_wali_kelas' => null,
            'nip_wali_kelas' => null,
        ]);
        DB::table('siswa_riwayat_kelas')->where('id', 3)->update([
            'keputusan_kenaikan' => 'TIDAK LULUS',
            'kelas_tujuan_id' => null,
            'nama_wali_kelas' => 'Wali XII Tersimpan',
            'nip_wali_kelas' => 'NIP-XII-TERSIMPAN',
        ]);
        DB::table('siswa_nilai')
            ->where('riwayat_kelas_id', 3)
            ->where('semester', 'Ganjil')
            ->update(['nilai_angka' => 93]);

        DB::table('guru_biodata')->insert([
            ['id' => 1, 'nama' => 'Wali XI Pengganti', 'nip' => 'NIP-XI-PENGGANTI'],
            ['id' => 2, 'nama' => 'Wali X Pengganti', 'nip' => 'NIP-X-PENGGANTI'],
        ]);
        DB::table('guru_wali_kelas')->insert([
            ['kelas_id' => 1, 'tahun_ajaran_id' => 1, 'guru_id' => 2],
            ['kelas_id' => 2, 'tahun_ajaran_id' => 2, 'guru_id' => 1],
        ]);

        DB::table('sekolah_mapel')->insert([
            ['id' => 2, 'kode' => 'sci', 'nama' => 'Sains Kelas X'],
            ['id' => 3, 'kode' => 'fis', 'nama' => 'Fisika Kelas XI'],
            ['id' => 4, 'kode' => 'bio', 'nama' => 'Biologi Kelas XII'],
        ]);
        DB::table('sekolah_mapel_per_tingkat')->delete();
        DB::table('sekolah_mapel_per_tingkat')->insert([
            ['tingkat' => 'X', 'mapel_id' => 1, 'kelompok_mapel' => 'Wajib X', 'urutan' => 1],
            ['tingkat' => 'X', 'mapel_id' => 2, 'kelompok_mapel' => 'Wajib X', 'urutan' => 2],
            ['tingkat' => 'XI', 'mapel_id' => 1, 'kelompok_mapel' => 'Wajib XI', 'urutan' => 1],
            ['tingkat' => 'XI', 'mapel_id' => 3, 'kelompok_mapel' => 'Wajib XI', 'urutan' => 2],
            ['tingkat' => 'XII', 'mapel_id' => 1, 'kelompok_mapel' => 'Wajib XII', 'urutan' => 1],
            ['tingkat' => 'XII', 'mapel_id' => 4, 'kelompok_mapel' => 'Wajib XII', 'urutan' => 2],
        ]);

        DB::table('siswa_nilai')->insert([
            ['riwayat_kelas_id' => 1, 'semester' => 'Genap', 'mapel_id' => 2, 'nilai_angka' => 72],
            ['riwayat_kelas_id' => 2, 'semester' => 'Ganjil', 'mapel_id' => 3, 'nilai_angka' => 86],
            ['riwayat_kelas_id' => 2, 'semester' => 'Genap', 'mapel_id' => 3, 'nilai_angka' => 90],
            ['riwayat_kelas_id' => 3, 'semester' => 'Ganjil', 'mapel_id' => 4, 'nilai_angka' => 94],
            ['riwayat_kelas_id' => 3, 'semester' => 'Genap', 'mapel_id' => 4, 'nilai_angka' => 98],
        ]);

        $response = $this->withoutMiddleware()->get(route('admin.data-siswa.pdf', [
            'id' => 1,
            'bio' => 0,
            'nilai' => 1,
            'hadir' => 0,
            'ekskul' => 0,
            'naik' => 1,
            'paper' => 'A4',
        ]));

        $response->assertOk()
            ->assertSee('210mm 297mm')
            ->assertSee('Semester 1')
            ->assertSee('Semester 2')
            ->assertSee('Semester 3')
            ->assertSee('Semester 4')
            ->assertSee('Semester 5')
            ->assertSee('Semester 6')
            ->assertSeeInOrder([
                'Kelas X-1',
                'Wajib X',
                'Matematika',
                '81',
                '82',
                'Sains Kelas X',
                'X',
                '72',
                '81,00',
                '77,00',
                'Kelas XI-1',
                'Wajib XI',
                '88',
                '89',
                'Fisika Kelas XI',
                '86',
                '90',
                'Kelas XII-1',
                'Wajib XII',
                '93',
                '96',
                'Biologi Kelas XII',
                '94',
                '98',
            ])
            ->assertSee('Kelas X-1 Ke Kelas XI-2')
            ->assertSee('Kelas XI-1 Ke Kelas XII-1')
            ->assertSee('Wali X Tersimpan')
            ->assertSee('Wali XI Pengganti')
            ->assertSee('Wali XII Tersimpan')
            ->assertDontSee('Wali X Pengganti')
            ->assertSee('NIP-X-TERSIMPAN')
            ->assertSee('NIP-XI-PENGGANTI')
            ->assertSee('<b>TIDAK NAIK</b>', false)
            ->assertSee('<b>TIDAK LULUS</b>', false);
    }

    public function test_pdf_without_class_history_renders_and_supports_both_paper_sizes(): void
    {
        DB::table('siswa_biodata')->insert([
            'id' => 2,
            'nama_lengkap' => 'Siswa Tanpa Riwayat',
            'nisn' => '0987654321',
            'nomor_induk' => '5678',
        ]);

        $f4Response = $this->withoutMiddleware()->get(route('admin.data-siswa.pdf', [
            'id' => 2,
            'bio' => 0,
            'nilai' => 1,
            'hadir' => 0,
            'ekskul' => 0,
            'naik' => 0,
            'paper' => 'F4',
        ]));

        $f4Response->assertOk()
            ->assertSee('Siswa Tanpa Riwayat')
            ->assertSee('215mm 330mm');

        $a4Response = $this->get(route('admin.data-siswa.pdf', [
            'id' => 2,
            'bio' => 0,
            'nilai' => 1,
            'hadir' => 0,
            'ekskul' => 0,
            'naik' => 0,
            'paper' => 'A4',
        ]));

        $a4Response->assertOk()
            ->assertSee('Siswa Tanpa Riwayat')
            ->assertSee('210mm 297mm');
    }
}
