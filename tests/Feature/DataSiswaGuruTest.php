<?php

namespace Tests\Feature;

use App\Models\Auth\Pengguna;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdf;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class DataSiswaGuruTest extends TestCase
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
            $table->string('jenis_kelamin')->nullable();
            $table->string('tempat_lahir')->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->string('agama')->nullable();
            $table->string('nama_ayah')->nullable();
            $table->string('nama_ibu')->nullable();
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
        Schema::create('siswa_berkas', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('siswa_id');
            $table->string('jenis');
            $table->string('nama_file');
            $table->string('lokasi');
            $table->string('tipe_file')->nullable();
            $table->unsignedBigInteger('ukuran_byte')->nullable();
            $table->timestamps();
        });

        DB::table('sekolah_tahun_ajaran')->insert([
            'id' => 1,
            'nama' => '2026/2027',
            'sedang_berjalan' => true,
        ]);
        DB::table('sekolah_kelas')->insert([
            ['id' => 1, 'nama' => 'X-1', 'tingkat' => 'X'],
            ['id' => 2, 'nama' => 'X-2', 'tingkat' => 'X'],
        ]);
        $this->createStudent('Siswa Satu', 1);
        $this->createStudent('Siswa Dua', 2);
    }

    public function test_export_only_contains_students_matching_class_filter(): void
    {
        $response = $this->actingAs($this->createAccount('guru'))
            ->get(route('guru.data-siswa.export', ['kelas' => 'X-1']));

        $response->assertOk();
        $content = $response->streamedContent();
        $this->assertStringContainsString('Siswa Satu', $content);
        $this->assertStringNotContainsString('Siswa Dua', $content);
    }

    public function test_selected_pdf_only_contains_selected_students_matching_current_filters(): void
    {
        $pdf = Mockery::mock(DomPdf::class);
        $pdf->shouldReceive('setPaper')->once()->with('a4', 'landscape')->andReturnSelf();
        $pdf->shouldReceive('stream')->once()->andReturn(response('PDF'));
        Pdf::shouldReceive('loadView')
            ->once()
            ->withArgs(function (string $view, array $data): bool {
                $this->assertSame('admin.siswa-daftar-pdf', $view);
                $this->assertSame(['Siswa Satu'], $data['siswaList']->pluck('nama_lengkap')->all());

                return true;
            })
            ->andReturn($pdf);

        $response = $this->actingAs($this->createAccount('guru'))
            ->post(route('guru.data-siswa.pdf-selected'), [
                'tahun_ajaran' => 1,
                'kelas' => 'X-1',
                'siswa_ids' => [1, 2],
            ]);

        $response->assertOk();
    }

    public function test_student_account_cannot_access_teacher_data_exports(): void
    {
        $this->actingAs($this->createAccount('siswa'))
            ->get(route('guru.data-siswa.export'))
            ->assertRedirect(route('login'));
    }

    public function test_teacher_can_open_student_edit_form(): void
    {
        $this->actingAs($this->createAccount('guru'))
            ->get(route('guru.data-siswa.edit', 1))
            ->assertOk()
            ->assertSee('Siswa Satu')
            ->assertSee(route('guru.data-siswa.update', 1), false);
    }

    public function test_student_account_cannot_edit_student_data(): void
    {
        $this->actingAs($this->createAccount('siswa'))
            ->put(route('guru.data-siswa.update', 1), [
                'nama_lengkap' => 'Nama Tidak Boleh Berubah',
                'nisn' => '1000000001',
                'kelas' => 'X-1',
                'tahun_ajaran' => '2026/2027',
            ])
            ->assertRedirect(route('login'));

        $this->assertDatabaseHas('siswa_biodata', [
            'id' => 1,
            'nama_lengkap' => 'Siswa Satu',
        ]);
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

    private function createStudent(string $name, int $classId): int
    {
        $studentId = DB::table('siswa_biodata')->insertGetId([
            'nisn' => '100000000'.(string) $classId,
            'nomor_induk' => '500'.$classId,
            'nama_lengkap' => $name,
        ]);
        DB::table('siswa_riwayat_kelas')->insert([
            'siswa_id' => $studentId,
            'tahun_ajaran_id' => 1,
            'kelas_id' => $classId,
            'status' => 'Aktif',
        ]);

        return $studentId;
    }
}
