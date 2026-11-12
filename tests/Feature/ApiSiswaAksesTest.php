<?php

namespace Tests\Feature;

use App\Models\Auth\Pengguna;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdf;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Mockery;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tests\TestCase;

#[RunTestsInSeparateProcesses]
class ApiSiswaAksesTest extends TestCase
{
    private const TABLES = [
        'akun_perangkat_login',
        'akun_notifikasi',
        'personal_access_tokens',
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
            $table->string('password');
            $table->boolean('aktif')->default(true);
            $table->unsignedBigInteger('guru_id')->nullable();
            $table->unsignedBigInteger('siswa_id')->nullable();
            $table->boolean('wajib_ganti_password')->default(false);
            $table->timestamp('email_diverifikasi_at')->nullable();
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
                'tinggi_badan_cm', 'berat_badan_kg',
            ] as $column) {
                $table->string($column)->nullable();
            }
            $table->softDeletes();
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
            $table->unsignedBigInteger('pengguna_id');
            $table->string('judul');
            $table->text('isi')->nullable();
            $table->string('jenis');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('akun_perangkat_login', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('pengguna_id');
            $table->unsignedBigInteger('token_id')->nullable();
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

    public function test_downloaded_biodata_pdf_is_built_from_only_the_authenticated_student(): void
    {
        $ownStudentId = $this->createStudent('Siswa Milik Akun', '1234567890');
        $this->createStudent('Siswa Lain', '1234567891');
        $user = $this->createStudentAccount('siswa-unduh', $ownStudentId);
        $token = $user->createToken('uji')->plainTextToken;

        $pdf = Mockery::mock(DomPdf::class);
        $pdf->shouldReceive('setPaper')->once()->with('a4')->andReturnSelf();
        $pdf->shouldReceive('download')->once()->with('biodata-1234567890.pdf')->andReturn(response('PDF'));
        Pdf::shouldReceive('loadView')
            ->once()
            ->withArgs(function (string $view, array $data) use ($ownStudentId): bool {
                $this->assertSame('siswa-mobile.dokumen-pdf', $view);
                $this->assertSame($ownStudentId, $data['siswa']->id);
                $this->assertSame('Siswa Milik Akun', $data['siswa']->nama_lengkap);

                return true;
            })
            ->andReturn($pdf);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->get('/api/v1/unduh/bio')
            ->assertOk()
            ->assertSeeText('PDF');
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

    private function createStudent(string $name, string $nisn): int
    {
        return (int) DB::table('siswa_biodata')->insertGetId([
            'nama_lengkap' => $name,
            'nisn' => $nisn,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createStudentAccount(string $username, int $studentId): Pengguna
    {
        return Pengguna::query()->create([
            'peran' => 'siswa',
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
