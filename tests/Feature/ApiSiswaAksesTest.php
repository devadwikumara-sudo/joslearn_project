<?php

namespace Tests\Feature;

use App\Models\Auth\AkunSiswa;
use App\Models\Auth\Pengguna;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdf;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Mockery;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tests\TestCase;

#[RunTestsInSeparateProcesses]
class ApiSiswaAksesTest extends TestCase
{
    private const MAIN_TABLES = [
        'guru_wali_kelas',
        'guru_biodata',
        'sekolah_identitas',
        'siswa_ekskul',
        'siswa_kehadiran',
        'siswa_nilai',
        'sekolah_mapel_per_tingkat',
        'sekolah_mapel',
        'sekolah_pengaturan',
        'siswa_riwayat_kelas',
        'sekolah_kelas',
        'sekolah_tahun_ajaran',
        'siswa_biodata',
        'akun_pengguna',
        'akun_perangkat_login',
        'akun_notifikasi',
        'akun_kode_otp',
        'personal_access_tokens',
        'akun_siswa',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        foreach (self::MAIN_TABLES as $table) {
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
            $table->timestamp('email_diverifikasi_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('akun_siswa', function (Blueprint $table): void {
            $table->id();
            $table->string('username')->unique();
            $table->string('email')->nullable()->unique();
            $table->timestamp('email_diverifikasi_at')->nullable();
            $table->string('password');
            $table->boolean('aktif')->default(true);
            $table->unsignedBigInteger('siswa_id')->unique();
            $table->boolean('wajib_ganti_password')->default(false);
            $table->timestamp('terakhir_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('siswa_biodata', function (Blueprint $table): void {
            $table->id();
            foreach ([
                'nama_lengkap', 'nisn', 'nomor_induk', 'tempat_lahir', 'tanggal_lahir',
                'jenis_kelamin', 'agama', 'alamat_jalan', 'alamat_rt_rw', 'alamat_desa',
                'alamat_kecamatan', 'alamat_kabupaten', 'alamat_provinsi', 'alamat_kode_pos',
                'nama_ayah', 'nama_ibu', 'nama_wali', 'sekolah_asal', 'tanggal_diterima',
                'tinggi_badan_cm', 'berat_badan_kg', 'foto',
                'foto_masuk_2x3', 'foto_masuk_3x4', 'foto_keluar_2x3', 'foto_keluar_3x4',
            ] as $column) {
                $table->string($column)->nullable();
            }
            $table->softDeletes();
            $table->timestamps();
        });

        // Dipakai data builder dokumen cetak (sama dengan PDF di web).
        Schema::create('sekolah_identitas', function (Blueprint $table): void {
            $table->id();
            $table->string('nama')->nullable();
            $table->timestamps();
        });

        Schema::create('guru_biodata', function (Blueprint $table): void {
            $table->id();
            $table->string('nama');
            $table->string('nip')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('guru_wali_kelas', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('kelas_id');
            $table->unsignedBigInteger('tahun_ajaran_id');
            $table->unsignedBigInteger('guru_id');
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
            $table->string('nama')->unique();
            $table->string('tingkat');
            $table->timestamps();
        });

        Schema::create('siswa_riwayat_kelas', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('siswa_id');
            $table->unsignedBigInteger('tahun_ajaran_id');
            $table->unsignedBigInteger('kelas_id');
            $table->string('status');
            $table->string('keputusan_kenaikan')->nullable();
            $table->date('tanggal_keputusan_kenaikan')->nullable();
            $table->string('nama_wali_kelas')->nullable();
            $table->string('nip_wali_kelas')->nullable();
            $table->unsignedBigInteger('kelas_tujuan_id')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('sekolah_pengaturan', function (Blueprint $table): void {
            $table->id();
            $table->string('kunci')->unique();
            $table->string('isi')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        Schema::create('sekolah_mapel', function (Blueprint $table): void {
            $table->id();
            $table->string('kode')->unique();
            $table->string('nama');
            $table->timestamps();
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

        Schema::create('personal_access_tokens', function (Blueprint $table): void {
            $table->id();
            $table->morphs('tokenable');
            $table->text('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('akun_notifikasi', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('pengguna_id')->nullable();
            $table->unsignedBigInteger('akun_siswa_id')->nullable();
            $table->string('judul');
            $table->text('isi')->nullable();
            $table->string('jenis');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('akun_perangkat_login', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('pengguna_id')->nullable();
            $table->unsignedBigInteger('akun_siswa_id')->nullable();
            $table->unsignedBigInteger('token_id')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('akun_kode_otp', function (Blueprint $table): void {
            $table->id();
            $table->string('email');
            $table->string('tujuan');
            $table->string('kode_hash');
            $table->unsignedTinyInteger('percobaan')->default(0);
            $table->timestamp('berlaku_sampai');
            $table->timestamp('dipakai_at')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    protected function tearDown(): void
    {
        foreach (self::MAIN_TABLES as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_biodata_endpoint_returns_only_the_authenticated_students_record(): void
    {
        $ownStudentId = $this->createStudent('Siswa Milik Akun', '1234567890');
        $this->createStudent('Siswa Lain', '1234567891');
        $user = $this->createStudentAccount('siswa-uji', $ownStudentId);
        $token = $user->createToken('uji')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/biodata')
            ->assertOk()
            ->assertJsonPath('data.nama', 'Siswa Milik Akun')
            ->assertJsonPath('data.nisn', '1234567890')
            ->assertDontSee('Siswa Lain');
    }

    public function test_student_cannot_read_values_from_an_unopened_semester_or_another_students_history(): void
    {
        $ownStudentId = $this->createStudent('Siswa Milik Akun', '1234567890');
        $otherStudentId = $this->createStudent('Siswa Lain', '1234567891');
        $this->createAcademicRecords($ownStudentId, $otherStudentId);
        $user = $this->createStudentAccount('siswa-nilai', $ownStudentId);
        $token = $user->createToken('uji')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/nilai?ta=1&semester=Ganjil')
            ->assertOk()
            ->assertJsonCount(1, 'data.nilai')
            ->assertJsonPath('data.nilai.0.nilai', 88);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/nilai?ta=1&semester=Genap')
            ->assertForbidden();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/nilai?ta=2&semester=Ganjil')
            ->assertForbidden();
    }

    public function test_student_can_read_genap_after_the_active_semester_is_opened(): void
    {
        $ownStudentId = $this->createStudent('Siswa Milik Akun', '1234567890');
        $otherStudentId = $this->createStudent('Siswa Lain', '1234567891');
        $this->createAcademicRecords($ownStudentId, $otherStudentId);
        DB::table('sekolah_pengaturan')
            ->where('kunci', 'semester_berjalan')
            ->update(['isi' => 'Genap']);
        $token = $this->createStudentAccount('siswa-genap', $ownStudentId)
            ->createToken('uji')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/nilai?ta=1&semester=Genap')
            ->assertOk()
            ->assertJsonPath('data.terpilih.semester', 'Genap')
            ->assertJsonCount(1, 'data.nilai')
            ->assertJsonPath('data.nilai.0.nilai', 91);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/nilai')
            ->assertOk()
            ->assertJsonPath('data.terpilih.semester', 'Genap')
            ->assertJsonPath('data.nilai.0.nilai', 91);
    }

    public function test_downloaded_biodata_pdf_uses_the_web_document_for_only_the_authenticated_student(): void
    {
        $ownStudentId = $this->createStudent('Siswa Milik Akun', '1234567890');
        $this->createStudent('Siswa Lain', '1234567891');
        $token = $this->createStudentAccount('siswa-unduh', $ownStudentId)->createToken('uji')->plainTextToken;

        $this->expectPdf('biodata-1234567890.pdf', function (string $html): void {
            $this->assertStringContainsStringIgnoringCase('Siswa Milik Akun', $html);
            $this->assertStringNotContainsStringIgnoringCase('Siswa Lain', $html);
            $this->assertStringNotContainsString('1234567891', $html);
            // Variabel CSS layout web diganti warna sebenarnya karena DomPDF tidak mengenalnya
            $this->assertStringNotContainsString('var(--', $html);
        });

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->get('/api/v1/unduh/bio')
            ->assertOk()
            ->assertSeeText('PDF');
    }

    public function test_downloaded_grade_pdf_contains_only_the_selected_open_period(): void
    {
        $ownStudentId = $this->createStudent('Siswa Milik Akun', '1234567890');
        $otherStudentId = $this->createStudent('Siswa Lain', '1234567891');
        $this->createAcademicRecords($ownStudentId, $otherStudentId);
        // Nilai khas agar mudah dibedakan dari angka lain di dokumen
        DB::table('siswa_nilai')->where('riwayat_kelas_id', 1)->where('semester', 'Ganjil')->update(['nilai_angka' => 87]);
        DB::table('siswa_nilai')->where('riwayat_kelas_id', 1)->where('semester', 'Genap')->update(['nilai_angka' => 93]);
        DB::table('siswa_nilai')->where('riwayat_kelas_id', 2)->update(['nilai_angka' => 71]);
        DB::table('siswa_nilai')->where('riwayat_kelas_id', 3)->update(['nilai_angka' => 62]);
        $token = $this->createStudentAccount('siswa-nilai', $ownStudentId)->createToken('uji')->plainTextToken;

        $this->expectPdf('nilai-1234567890.pdf', function (string $html): void {
            $this->assertStringContainsStringIgnoringCase('Siswa Milik Akun', $html);
            $this->assertStringContainsString('Matematika', $html);
            $this->assertGradeCell($html, 87, true);
            // Semester Genap yang belum terbuka, tahun ajaran berikutnya, dan siswa lain tidak boleh ikut
            foreach ([93, 71, 62] as $nilaiTerlarang) {
                $this->assertGradeCell($html, $nilaiTerlarang, false);
            }
            $this->assertStringNotContainsStringIgnoringCase('Siswa Lain', $html);
        });

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->get('/api/v1/unduh/nilai?ta=1&semester=Ganjil')
            ->assertOk();
    }

    public function test_complete_pdf_omits_the_promotion_decision_until_it_is_recorded(): void
    {
        $ownStudentId = $this->createStudent('Siswa Milik Akun', '1234567890');
        $this->createAcademicRecords($ownStudentId, $this->createStudent('Siswa Lain', '1234567891'));
        $token = $this->createStudentAccount('siswa-lengkap', $ownStudentId)->createToken('uji')->plainTextToken;

        $this->expectPdf('dokumen-lengkap-1234567890.pdf', function (string $html): void {
            $this->assertStringContainsStringIgnoringCase('Siswa Milik Akun', $html);
            $this->assertStringContainsString('Matematika', $html);
            $this->assertStringNotContainsString('KENAIKAN KELAS', $html);
        });

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->get('/api/v1/unduh/all?ta=1&semester=Ganjil')
            ->assertOk();
    }

    public function test_complete_pdf_includes_the_promotion_decision_once_it_is_recorded(): void
    {
        $ownStudentId = $this->createStudent('Siswa Milik Akun', '1234567890');
        $this->createAcademicRecords($ownStudentId, $this->createStudent('Siswa Lain', '1234567891'));
        DB::table('siswa_riwayat_kelas')->where('id', 1)->update([
            'keputusan_kenaikan' => 'NAIK',
            'tanggal_keputusan_kenaikan' => '2027-06-20',
        ]);
        $token = $this->createStudentAccount('siswa-lengkap', $ownStudentId)->createToken('uji')->plainTextToken;

        $this->expectPdf('dokumen-lengkap-1234567890.pdf', function (string $html): void {
            $this->assertStringContainsString('KENAIKAN KELAS', $html);
        });

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->get('/api/v1/unduh/all?ta=1&semester=Ganjil')
            ->assertOk();
    }

    public function test_pdf_download_is_forbidden_for_unopened_periods_without_building_a_document(): void
    {
        $ownStudentId = $this->createStudent('Siswa Milik Akun', '1234567890');
        $this->createAcademicRecords($ownStudentId, $this->createStudent('Siswa Lain', '1234567891'));
        $token = $this->createStudentAccount('siswa-terkunci', $ownStudentId)->createToken('uji')->plainTextToken;

        Pdf::shouldReceive('loadHTML')->never();

        foreach (['/api/v1/unduh/nilai?ta=1&semester=Genap', '/api/v1/unduh/all?ta=2&semester=Ganjil'] as $url) {
            $this->withHeader('Authorization', 'Bearer '.$token)
                ->get($url)
                ->assertForbidden();
        }
    }

    public function test_pdf_embeds_the_students_photo_from_the_private_folder(): void
    {
        Storage::fake('local');
        $ownStudentId = $this->createStudent('Siswa Milik Akun', '1234567890');
        Storage::disk('local')->put('siswa/foto/foto_uji.jpg', 'ISI-FOTO-UJI');
        DB::table('siswa_biodata')->where('id', $ownStudentId)->update(['foto' => 'siswa/foto/foto_uji.jpg']);
        $token = $this->createStudentAccount('siswa-foto', $ownStudentId)->createToken('uji')->plainTextToken;

        $this->expectPdf('biodata-1234567890.pdf', function (string $html): void {
            $this->assertStringContainsString('data:image/jpeg;base64,'.base64_encode('ISI-FOTO-UJI'), $html);
        });

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->get('/api/v1/unduh/bio')
            ->assertOk();
    }

    public function test_mobile_pdf_embeds_each_of_the_four_student_photo_slots(): void
    {
        Storage::fake('local');
        $ownStudentId = $this->createStudent('Siswa Milik Akun', '1234567890');
        $slots = [
            'foto_masuk_2x3',
            'foto_masuk_3x4',
            'foto_keluar_2x3',
            'foto_keluar_3x4',
        ];
        $photoPaths = [];
        $photoContents = [];
        foreach ($slots as $slot) {
            $content = 'photo-content-'.$slot;
            $path = 'siswa/foto/'.$slot.'.png';
            Storage::disk('local')->put($path, $content);
            $photoPaths[$slot] = $path;
            $photoContents[$slot] = $content;
        }
        DB::table('siswa_biodata')->where('id', $ownStudentId)->update($photoPaths);
        $token = $this->createStudentAccount('siswa-foto', $ownStudentId)->createToken('uji')->plainTextToken;

        $this->expectPdf('biodata-1234567890.pdf', function (string $html) use ($photoContents): void {
            foreach ($photoContents as $slot => $content) {
                $this->assertStringContainsString('data:image/png;base64,'.base64_encode($content), $html);
            }
            $this->assertSame(2, substr_count($html, 'alt="Foto masuk"'));
            $this->assertSame(2, substr_count($html, 'alt="Foto keluar"'));
        });

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->get('/api/v1/unduh/bio')
            ->assertOk();
    }

    public function test_pdf_never_embeds_files_outside_the_photo_folder(): void
    {
        Storage::fake('local');
        $ownStudentId = $this->createStudent('Siswa Milik Akun', '1234567890');
        Storage::disk('local')->put('rahasia.jpg', 'ISI-RAHASIA');
        DB::table('siswa_biodata')->where('id', $ownStudentId)->update(['foto' => 'siswa/foto/../rahasia.jpg']);
        $token = $this->createStudentAccount('siswa-foto', $ownStudentId)->createToken('uji')->plainTextToken;

        $this->expectPdf('biodata-1234567890.pdf', function (string $html): void {
            $this->assertStringNotContainsString(base64_encode('ISI-RAHASIA'), $html);
        });

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->get('/api/v1/unduh/bio')
            ->assertOk();
    }

    public function test_revoked_sanctum_token_returns_401(): void
    {
        $studentId = $this->createStudent('Siswa Milik Akun', '1234567890');
        $user = $this->createStudentAccount('siswa-token', $studentId);
        $token = $user->createToken('uji')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/logout')
            ->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
        Auth::forgetGuards();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/biodata')
            ->assertStatus(401);
    }

    public function test_sixth_failed_mobile_login_attempt_is_rate_limited(): void
    {
        RateLimiter::clear('login-mobile:tidak-ada|127.0.0.1');

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->postJson('/api/v1/login', [
                'username' => 'tidak-ada',
                'password' => 'salah',
            ])->assertStatus(401);
        }

        $this->postJson('/api/v1/login', [
            'username' => 'tidak-ada',
            'password' => 'salah',
        ])->assertStatus(429);
    }

    public function test_mobile_registration_activates_account_only_in_student_account_table_without_otp(): void
    {
        Mail::shouldReceive('raw')->never();

        $this->createStudent('Siswa Pendaftar', '1234567890');

        $this->postJson('/api/v1/daftar', [
            'nama' => 'Siswa Pendaftar',
            'nisn' => '1234567890',
            'email' => 'siswa@example.test',
            'password' => 'password-rahasia',
        ])->assertCreated()
            ->assertJsonPath('data.nisn', '1234567890');

        $this->assertSame(
            1,
            DB::table('akun_siswa')->where('username', '1234567890')->where('aktif', true)->count()
        );
        $this->assertSame(0, DB::table('akun_kode_otp')->count());
        $this->assertDatabaseMissing('akun_pengguna', ['username' => '1234567890']);
    }

    public function test_mobile_login_does_not_authenticate_web_accounts(): void
    {
        Pengguna::query()->create([
            'peran' => 'admin',
            'username' => 'admin-web',
            'email' => 'admin@example.test',
            'password' => 'password-rahasia',
            'aktif' => true,
        ]);

        $this->postJson('/api/v1/login', [
            'username' => 'admin-web',
            'password' => 'password-rahasia',
        ])->assertUnauthorized();
    }

    /**
     * Pasang harapan: PDF dibuat sekali dari HTML dokumen web, berkertas F4, lalu diunduh
     * dengan nama file yang diharapkan. $periksaHtml memeriksa HTML yang dikirim ke DomPDF.
     */
    private function expectPdf(string $namaFile, callable $periksaHtml): void
    {
        $pdf = Mockery::mock(DomPdf::class);
        $pdf->shouldReceive('setPaper')->once()->with([0, 0, 609.45, 935.43])->andReturnSelf();
        $pdf->shouldReceive('download')->once()->with($namaFile)->andReturn(response('PDF'));

        Pdf::shouldReceive('loadHTML')
            ->once()
            ->withArgs(function (string $html) use ($periksaHtml): bool {
                $periksaHtml($html);

                return true;
            })
            ->andReturn($pdf);
    }

    /**
     * Sel nilai dicari berdasarkan batas tag, bukan sekadar angka, karena angka yang sama
     * bisa muncul di stylesheet (mis. warna #1e293b memuat "93").
     */
    private function assertGradeCell(string $html, int $nilai, bool $diharapkan): void
    {
        $ada = preg_match('/<td[^>]*>\s*'.$nilai.'(?:[.,]\d+)?\s*<\/td>/', $html) === 1;

        $this->assertSame($diharapkan, $ada, 'Sel nilai '.$nilai.($diharapkan ? ' seharusnya ada.' : ' tidak boleh ada.'));
    }

    private function createStudent(string $name, string $nisn): int
    {
        return (int) DB::table('siswa_biodata')->insertGetId([
            'nama_lengkap' => $name,
            'nisn' => $nisn,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createStudentAccount(string $username, int $studentId): AkunSiswa
    {
        return AkunSiswa::query()->create([
            'username' => $username,
            'email' => $username.'@example.test',
            'password' => 'test-password',
            'aktif' => true,
            'siswa_id' => $studentId,
            'wajib_ganti_password' => false,
        ]);
    }

    private function createAcademicRecords(int $ownStudentId, int $otherStudentId): void
    {
        DB::table('sekolah_tahun_ajaran')->insert([
            ['id' => 1, 'nama' => '2026/2027', 'sedang_berjalan' => true],
            ['id' => 2, 'nama' => '2027/2028', 'sedang_berjalan' => false],
        ]);
        DB::table('sekolah_kelas')->insert([
            ['id' => 1, 'nama' => 'X-1', 'tingkat' => 'X'],
        ]);
        DB::table('sekolah_pengaturan')->insert([
            'kunci' => 'semester_berjalan',
            'isi' => 'Ganjil',
        ]);
        DB::table('sekolah_mapel')->insert([
            'id' => 1,
            'kode' => 'mtk',
            'nama' => 'Matematika',
        ]);
        DB::table('sekolah_mapel_per_tingkat')->insert([
            'tingkat' => 'X',
            'mapel_id' => 1,
            'kelompok_mapel' => 'Wajib',
            'urutan' => 1,
        ]);
        DB::table('siswa_riwayat_kelas')->insert([
            ['id' => 1, 'siswa_id' => $ownStudentId, 'tahun_ajaran_id' => 1, 'kelas_id' => 1, 'status' => 'Aktif'],
            ['id' => 2, 'siswa_id' => $ownStudentId, 'tahun_ajaran_id' => 2, 'kelas_id' => 1, 'status' => 'Aktif'],
            ['id' => 3, 'siswa_id' => $otherStudentId, 'tahun_ajaran_id' => 1, 'kelas_id' => 1, 'status' => 'Aktif'],
        ]);
        DB::table('siswa_nilai')->insert([
            ['riwayat_kelas_id' => 1, 'semester' => 'Ganjil', 'mapel_id' => 1, 'nilai_angka' => 88],
            ['riwayat_kelas_id' => 1, 'semester' => 'Genap', 'mapel_id' => 1, 'nilai_angka' => 91],
            ['riwayat_kelas_id' => 2, 'semester' => 'Ganjil', 'mapel_id' => 1, 'nilai_angka' => 100],
            ['riwayat_kelas_id' => 3, 'semester' => 'Ganjil', 'mapel_id' => 1, 'nilai_angka' => 10],
        ]);
    }
}
