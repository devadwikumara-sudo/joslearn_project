<?php

namespace Tests\Feature;

use App\Models\Auth\Pengguna;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class PdfSemesterFilterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'akun_pengguna',
            'guru_mengajar',
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

        Schema::create('akun_pengguna', function (Blueprint $table): void {
            $table->id();
            $table->string('peran');
            $table->string('username')->unique();
            $table->string('password');
            $table->boolean('aktif')->default(true);
            $table->unsignedBigInteger('guru_id')->nullable();
            $table->string('remember_token')->nullable();
            $table->timestamps();
        });
        Schema::create('siswa_biodata', function (Blueprint $table): void {
            $table->id();
            $table->string('nama_lengkap');
            $table->string('nisn')->nullable();
            $table->string('nomor_induk')->nullable();
            $table->string('foto')->nullable();
            $table->string('tahun_lulus')->nullable();
            $table->string('alasan_keluar')->nullable();
            $table->date('tanggal_keluar')->nullable();
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
            $table->boolean('sedang_berjalan')->default(false);
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
        Schema::create('guru_mengajar', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('kelas_id');
            $table->unsignedBigInteger('tahun_ajaran_id');
            $table->unsignedBigInteger('guru_id');
            $table->unsignedBigInteger('mapel_id');
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
            ['id' => 1, 'nama' => '2023/2024', 'sedang_berjalan' => false],
            ['id' => 2, 'nama' => '2024/2025', 'sedang_berjalan' => false],
            ['id' => 3, 'nama' => '2025/2026', 'sedang_berjalan' => true],
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
            'akun_pengguna',
            'guru_mengajar',
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

    public function test_admin_can_render_single_and_batch_student_pdfs(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('siswa/foto/foto_siswa.jpg', UploadedFile::fake()->image('foto.jpg')->getContent());
        DB::table('siswa_biodata')->where('id', 1)->update([
            'foto' => 'siswa/foto/foto_siswa.jpg',
        ]);

        $admin = Pengguna::query()->create([
            'peran' => 'admin',
            'username' => 'admin-test',
            'password' => 'test-password',
            'aktif' => true,
        ]);

        $singleResponse = $this->actingAs($admin)->get(route('admin.data-siswa.pdf', 1));

        $singleResponse->assertOk()
            ->assertSee('I. BUKU INDUK REGISTER PESERTA DIDIK')
            ->assertSee('Siswa Enam Semester')
            ->assertSee('data:image/jpeg;base64,', false);

        $batchResponse = $this->get(route('admin.pdf.batch', ['ids' => '1']));

        $batchResponse->assertOk()
            ->assertSee('I. BUKU INDUK REGISTER PESERTA DIDIK')
            ->assertSee('Siswa Enam Semester')
            ->assertSee('data:image/jpeg;base64,', false);
    }

    public function test_batch_pdf_keeps_forty_student_registers_with_bounded_output_and_render_time(): void
    {
        $studentIds = [];
        $studentNames = [];
        for ($index = 1; $index <= 40; $index++) {
            $studentName = "Siswa Batch Uji {$index}";
            $studentNames[] = $studentName;
            $studentIds[] = DB::table('siswa_biodata')->insertGetId([
                'nama_lengkap' => $studentName,
                'nisn' => str_pad((string) $index, 10, '0', STR_PAD_LEFT),
                'nomor_induk' => 'BATCH-'.$index,
            ]);
        }

        $startedAt = microtime(true);
        $response = $this->withoutMiddleware()->get(route('admin.pdf.batch', [
            'ids' => implode(',', $studentIds),
            'bio' => 1,
            'nilai' => 0,
            'hadir' => 0,
            'ekskul' => 0,
            'naik' => 0,
        ]));
        $elapsedSeconds = microtime(true) - $startedAt;
        $content = (string) $response->assertOk()->getContent();

        foreach ($studentNames as $studentName) {
            $this->assertStringContainsString($studentName, $content);
        }
        $this->assertLessThan(15, $elapsedSeconds);
        $this->assertLessThan(5 * 1024 * 1024, strlen($content));
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

    public function test_pdf_renders_only_selected_slots_and_defaults_to_all_six_slots(): void
    {
        $selectedResponse = $this->withoutMiddleware()->get(route('admin.data-siswa.pdf', [
            'id' => 1,
            'bio' => 0,
            'nilai' => 1,
            'hadir' => 0,
            'ekskul' => 0,
            'naik' => 0,
            'slots_present' => 1,
            'slots' => ['X-1', 'XII-2'],
        ]));

        $selectedResponse->assertOk()
            ->assertSee('value="X-1" checked', false)
            ->assertSee('value="XII-2" checked', false)
            ->assertDontSee('value="XI-1" checked', false)
            ->assertSee('>81</td>', false)
            ->assertSee('>96</td>', false)
            ->assertDontSee('>88</td>', false)
            ->assertDontSee('>94</td>', false);

        $allSlotsResponse = $this->get(route('admin.data-siswa.pdf', 1));
        $allSlotsResponse->assertOk();
        foreach (['X-1', 'X-2', 'XI-1', 'XI-2', 'XII-1', 'XII-2'] as $slot) {
            $allSlotsResponse->assertSee('value="'.$slot.'" checked', false);
        }
    }

    public function test_batch_pdf_accepts_multiple_students_and_whole_class_or_all_students(): void
    {
        $activeStudentId = $this->createActiveStudent();
        $options = [
            'bio' => 1,
            'nilai' => 0,
            'hadir' => 0,
            'ekskul' => 0,
            'naik' => 0,
        ];

        $selectedResponse = $this->withoutMiddleware()->get(route('admin.pdf.batch', $options + [
            'ids' => '1,'.$activeStudentId,
        ]));
        $selectedResponse->assertOk()
            ->assertSee('Siswa Enam Semester')
            ->assertSee('Siswa Aktif Cetak');

        $classResponse = $this->get(route('admin.pdf.batch', $options + [
            'target_kelas' => 'XII-1',
            'target_ta' => '3',
        ]));
        $classResponse->assertOk()
            ->assertSee('Siswa Enam Semester')
            ->assertSee('Siswa Aktif Cetak');

        $allResponse = $this->get(route('admin.pdf.batch', $options + [
            'target_kelas' => '*',
            'target_ta' => '*',
        ]));
        $allResponse->assertOk()
            ->assertSee('Siswa Enam Semester')
            ->assertSee('Siswa Aktif Cetak');
    }

    public function test_teacher_cannot_print_a_student_outside_the_visible_active_scope(): void
    {
        $activeStudentId = $this->createActiveStudent();
        Storage::fake('local');
        Storage::disk('local')->put('siswa/foto/foto_siswa_aktif.jpg', 'foto-uji');
        Storage::disk('local')->put('siswa/foto/foto_siswa_luar_cakupan.jpg', 'foto-uji');
        DB::table('siswa_biodata')->where('id', $activeStudentId)->update([
            'foto' => 'siswa/foto/foto_siswa_aktif.jpg',
        ]);
        DB::table('siswa_biodata')->where('id', 1)->update([
            'foto' => 'siswa/foto/foto_siswa_luar_cakupan.jpg',
        ]);

        $guruId = DB::table('guru_biodata')->insertGetId([
            'nama' => 'Guru PDF',
            'nip' => 'NIP-GURU-PDF',
        ]);
        $teacher = Pengguna::query()->create([
            'peran' => 'guru',
            'username' => 'guru-pdf-test',
            'password' => 'test-password',
            'aktif' => true,
            'guru_id' => $guruId,
        ]);
        DB::table('guru_wali_kelas')->insert([
            'kelas_id' => 3,
            'tahun_ajaran_id' => 3,
            'guru_id' => $guruId,
        ]);

        $forbiddenSingleResponse = $this->actingAs($teacher)
            ->get(route('guru.data-siswa.pdf', ['id' => 1, 'tab' => 'wali']))
            ->assertForbidden();
        $forbiddenSingleResponse->assertDontSee('data:image/jpeg;base64,', false);

        $forbiddenBatchResponse = $this->get(route('guru.data-siswa.pdf-batch', ['ids' => '1', 'tab' => 'wali']))
            ->assertForbidden();
        $forbiddenBatchResponse->assertDontSee('data:image/jpeg;base64,', false);

        $this->get(route('guru.data-siswa.pdf', ['id' => $activeStudentId, 'tab' => 'wali']))
            ->assertOk()
            ->assertSee('data:image/jpeg;base64,', false);
        $this->get(route('guru.data-siswa.pdf-batch', ['ids' => (string) $activeStudentId, 'tab' => 'wali']))
            ->assertOk()
            ->assertSee('data:image/jpeg;base64,', false);
    }

    public function test_pdf_orders_missing_grade_placeholders_before_the_existing_xii_history(): void
    {
        DB::table('siswa_riwayat_kelas')->where('kelas_id', '!=', 3)->delete();

        $response = $this->renderCapaianPdf();

        $response->assertOk()
            ->assertSee('X : Tidak diajarkan pada semester tersebut');
        $this->assertSame(['X', 'XI', 'XII-1'], $this->reportClasses((string) $response->getContent()));
        $this->assertSame(2, substr_count((string) $response->getContent(), 'Belum ada data'));
    }

    public function test_pdf_orders_x_placeholder_and_xii_history_when_xi_is_missing(): void
    {
        DB::table('siswa_riwayat_kelas')->where('id', 2)->delete();

        $response = $this->renderCapaianPdf();

        $response->assertOk()
            ->assertSee('X : Tidak diajarkan pada semester tersebut')
            ->assertSee('Belum ada data');
        $this->assertSame(['X-1', 'XI', 'XII-1'], $this->reportClasses((string) $response->getContent()));
    }

    public function test_pdf_orders_complete_grade_histories_by_grade_not_school_year(): void
    {
        DB::table('sekolah_tahun_ajaran')->where('id', 1)->update(['nama' => '2025/2026']);
        DB::table('sekolah_tahun_ajaran')->where('id', 2)->update(['nama' => '2023/2024']);
        DB::table('sekolah_tahun_ajaran')->where('id', 3)->update(['nama' => '2024/2025']);

        $response = $this->renderCapaianPdf();

        $response->assertOk();
        $this->assertSame(['X-1', 'XI-1', 'XII-1'], $this->reportClasses((string) $response->getContent()));
    }

    public function test_hide_empty_pdf_removes_placeholders_and_keeps_nonempty_history(): void
    {
        DB::table('siswa_riwayat_kelas')->whereIn('id', [1, 2])->delete();

        $response = $this->renderCapaianPdf(['hide_empty' => 1]);

        $response->assertOk()
            ->assertDontSee('Belum ada data');
        $this->assertSame(['XII-1'], $this->reportClasses((string) $response->getContent()));
    }

    public function test_specific_school_year_filter_does_not_add_grade_placeholders(): void
    {
        $response = $this->renderCapaianPdf(['ta' => '2024/2025']);

        $response->assertOk()
            ->assertDontSee('Belum ada data');
        $this->assertSame(['XI-1'], $this->reportClasses((string) $response->getContent()));
    }

    public function test_pdf_embeds_the_photo_in_both_entry_slots_for_f4_and_a4(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('siswa/foto/foto_x.jpg', UploadedFile::fake()->image('foto.jpg')->getContent());
        DB::table('siswa_biodata')->where('id', 1)->update(['foto' => 'siswa/foto/foto_x.jpg']);

        foreach (['F4', 'A4'] as $paper) {
            $response = $this->renderCapaianPdf([
                'bio' => 1,
                'nilai' => 0,
                'paper' => $paper,
            ]);
            $content = (string) $response->assertOk()->getContent();

            $this->assertSame(2, substr_count($content, 'alt="Foto masuk"'));
            preg_match_all('/<img src="data:image\/jpeg;base64,[^"]+" alt="Foto masuk">/', $content, $matches);
            $this->assertCount(2, $matches[0]);
            $this->assertSame(2, substr_count($content, 'Foto Keluar'));
            $this->assertStringContainsString($paper === 'F4' ? '215mm 330mm' : '210mm 297mm', $content);
        }

        $this->assertSame('siswa/foto/foto_x.jpg', DB::table('siswa_biodata')->where('id', 1)->value('foto'));
    }

    public function test_missing_or_unapproved_photo_paths_keep_register_placeholders_and_return_ok(): void
    {
        Storage::fake('local');

        foreach ([
            null,
            'siswa/foto/tidak-ada.jpg',
            'siswa/foto/../rahasia.txt',
            'siswa/foto/foto.php',
            'siswa/foto/foto.txt',
        ] as $photoPath) {
            DB::table('siswa_biodata')->where('id', 1)->update(['foto' => $photoPath]);

            $response = $this->renderCapaianPdf([
                'bio' => 1,
                'nilai' => 0,
            ]);
            $content = (string) $response->assertOk()->getContent();

            $this->assertStringNotContainsString('alt="Foto masuk"', $content);
            $this->assertSame(2, substr_count($content, 'Foto Masuk'));
        }
    }

    public function test_pdf_embeds_photos_from_the_legacy_public_upload_path(): void
    {
        $directory = public_path('uploads/foto');
        $uploadsDirectory = public_path('uploads');
        $directoryExisted = is_dir($directory);
        $uploadsDirectoryExisted = is_dir($uploadsDirectory);
        $photoPath = $directory.DIRECTORY_SEPARATOR.'foto_pdf_test.png';
        $photoBytes = UploadedFile::fake()->image('foto.png')->getContent();

        if (! $directoryExisted) {
            mkdir($directory, 0777, true);
        }
        file_put_contents($photoPath, $photoBytes);
        DB::table('siswa_biodata')->where('id', 1)->update(['foto' => 'uploads/foto/foto_pdf_test.png']);

        try {
            $response = $this->renderCapaianPdf([
                'bio' => 1,
                'nilai' => 0,
            ]);
            $content = (string) $response->assertOk()->getContent();

            preg_match_all('/<img src="data:image\/png;base64,[^"]+" alt="Foto masuk">/', $content, $matches);
            $this->assertCount(2, $matches[0]);
        } finally {
            if (is_file($photoPath)) {
                unlink($photoPath);
            }
            if (! $directoryExisted && is_dir($directory) && count(scandir($directory)) === 2) {
                rmdir($directory);
            }
            if (! $uploadsDirectoryExisted && is_dir($uploadsDirectory) && count(scandir($uploadsDirectory)) === 2) {
                rmdir($uploadsDirectory);
            }
        }
    }

    public function test_missing_slots_print_empty_tables_unless_they_are_hidden(): void
    {
        $activeStudentId = $this->createActiveStudent();
        $visibleEmptySlots = $this->withoutMiddleware()->get(route('admin.data-siswa.pdf', [
            'id' => $activeStudentId,
            'bio' => 0,
            'nilai' => 1,
            'hadir' => 1,
            'ekskul' => 1,
            'naik' => 0,
        ]));

        $visibleEmptySlots->assertOk()
            ->assertSee('Belum ada data')
            ->assertSee('Semester 1')
            ->assertSee('Semester 3');

        $hiddenEmptySlots = $this->get(route('admin.data-siswa.pdf', [
            'id' => $activeStudentId,
            'bio' => 1,
            'nilai' => 1,
            'hadir' => 1,
            'ekskul' => 1,
            'naik' => 0,
            'hide_empty' => 1,
        ]));

        $hiddenEmptySlots->assertOk()
            ->assertSee('I. BUKU INDUK REGISTER PESERTA DIDIK')
            ->assertDontSee('II. LAPORAN HASIL CAPAIAN PEMBELAJARAN');
    }

    private function createActiveStudent(): int
    {
        $studentId = DB::table('siswa_biodata')->insertGetId([
            'nama_lengkap' => 'Siswa Aktif Cetak',
            'nisn' => '9876543210',
            'nomor_induk' => '5678',
        ]);
        DB::table('siswa_riwayat_kelas')->insert([
            'siswa_id' => $studentId,
            'tahun_ajaran_id' => 3,
            'kelas_id' => 3,
            'status' => 'Aktif',
        ]);

        return $studentId;
    }

    private function renderCapaianPdf(array $filters = []): TestResponse
    {
        return $this->withoutMiddleware()->get(route('admin.data-siswa.pdf', array_merge([
            'id' => 1,
            'bio' => 0,
            'nilai' => 1,
            'hadir' => 0,
            'ekskul' => 0,
            'naik' => 0,
        ], $filters)));
    }

    private function reportClasses(string $content): array
    {
        preg_match_all('/<th colspan="\d+">Kelas ([^<]+)<\/th>/', $content, $matches);

        return $matches[1];
    }

    public function test_pdf_uses_correct_school_identity_defaults_when_school_settings_are_empty(): void
    {
        $response = $this->withoutMiddleware()->get(route('admin.pdf.batch', [
            'ids' => '1',
            'bio' => 1,
            'nilai' => 0,
            'hadir' => 0,
            'ekskul' => 0,
            'naik' => 1,
        ]));

        $response->assertOk()
            ->assertSee('KELAS', false)
            ->assertSee('BUKU INDUK REGISTER PESERTA DIDIK', false)
            ->assertDontSee('SMA NEGERI 1 NGANJUK')
            ->assertDontSee('20538338')
            ->assertDontSee('Drs. SUGIARTO, M.Pd.')
            ->assertDontSee('19670815 199412 1 003');
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
