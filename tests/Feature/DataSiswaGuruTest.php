<?php

namespace Tests\Feature;

use App\Models\Auth\Pengguna;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdf;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use PhpOffice\PhpSpreadsheet\IOFactory;
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
        Schema::create('guru_biodata', function (Blueprint $table): void {
            $table->id();
            $table->string('nama');
            $table->string('nip')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('guru_mengajar', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tahun_ajaran_id');
            $table->unsignedBigInteger('guru_id');
            $table->unsignedBigInteger('kelas_id');
            $table->unsignedBigInteger('mapel_id');
            $table->timestamps();
        });
        Schema::create('guru_wali_kelas', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tahun_ajaran_id');
            $table->unsignedBigInteger('guru_id');
            $table->unsignedBigInteger('kelas_id');
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
            $table->string('tahun_lulus', 4)->nullable();
            $table->string('alasan_keluar')->nullable();
            $table->date('tanggal_keluar')->nullable();
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

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringContainsString('.xlsx', (string) $response->headers->get('content-disposition'));

        $workbook = IOFactory::load($response->baseResponse->getFile()->getPathname());
        $sheet = $workbook->getActiveSheet();
        $rows = $sheet->toArray();

        $this->assertSame('NISN', $rows[0][0]);
        $this->assertSame('Siswa Satu', $sheet->getCell('C2')->getValue());
        $this->assertSame('A1:J2', $sheet->getAutoFilter()->getRange());
        $this->assertSame('A2', $sheet->getFreezePane());
        $this->assertSame('17365D', $sheet->getStyle('A1')->getFill()->getStartColor()->getRGB());
        $workbook->disconnectWorksheets();
    }

    public function test_teacher_student_list_excludes_graduated_and_transferred_students(): void
    {
        $this->createStudent('Siswa Lulus', 1);
        DB::table('siswa_biodata')->where('nama_lengkap', 'Siswa Lulus')->update(['tahun_lulus' => '2026']);
        $transferredStudentId = $this->createStudent('Siswa Pindah', 1);
        DB::table('siswa_riwayat_kelas')->where('siswa_id', $transferredStudentId)->update(['status' => 'Pindah keluar']);

        $this->actingAs($this->createAccount('guru'))
            ->get(route('guru.data-siswa'))
            ->assertOk()
            ->assertSee('Siswa Satu')
            ->assertDontSee('Siswa Dua')
            ->assertDontSee('Siswa Lulus')
            ->assertDontSee('Siswa Pindah')
            ->assertSee('<option value="X-1"', false)
            ->assertDontSee('<option value="X-2"', false)
            ->assertSee('<option value="Lulus" >Alumni</option>', false)
            ->assertSee('<option value="Pindah keluar" >Mutasi</option>', false)
            ->assertDontSee('<option value="Keluar"', false);

        $this->get(route('guru.data-siswa', ['st' => 'Lulus']))
            ->assertOk()
            ->assertSee('<option value="Lulus" selected>Alumni</option>', false);
    }

    public function test_teacher_and_homeroom_views_only_show_their_separately_assigned_classes(): void
    {
        $teacher = $this->createAccount('guru', true, 'both');

        $this->actingAs($teacher)
            ->get(route('guru.data-siswa'))
            ->assertOk()
            ->assertSee('Siswa Satu')
            ->assertDontSee('Siswa Dua')
            ->assertSee('<option value="X-1"', false)
            ->assertDontSee('<option value="X-2"', false);

        $this->get(route('guru.data-siswa.wali'))
            ->assertOk()
            ->assertSee('Siswa Dua')
            ->assertDontSee('Siswa Satu')
            ->assertSee('<option value="X-2"', false)
            ->assertDontSee('<option value="X-1"', false);

        $this->get(route('guru.data-siswa.show', ['id' => 2]))
            ->assertForbidden();
        $this->get(route('guru.data-siswa.show', ['id' => 1, 'tab' => 'wali']))
            ->assertForbidden();
        $this->get(route('guru.data-siswa.pdf', ['id' => 2]))
            ->assertForbidden();
        $this->get(route('guru.data-siswa.pdf-batch', ['ids' => '2']))
            ->assertForbidden();
        $this->get(route('guru.data-siswa.pdf-batch', [
            'target_kelas' => 'X-2',
            'target_ta' => '1',
        ]))->assertForbidden();
        $this->get(route('guru.data-siswa.foto', ['id' => 2]))
            ->assertForbidden();
        $this->getJson(route('guru.data-siswa.export', ['kelas' => 'X-2']))
            ->assertUnprocessable();
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

    public function test_unassigned_teacher_cannot_access_student_lists_or_details(): void
    {
        $teacher = $this->createAccount('guru', false);

        $this->actingAs($teacher)
            ->get(route('guru.data-siswa'))
            ->assertForbidden();

        $this->get(route('guru.data-siswa.show', 1))
            ->assertForbidden();

        $this->get(route('guru.data-siswa.pdf', 1))
            ->assertForbidden();
    }

    public function test_homeroom_only_teacher_uses_the_dedicated_student_page(): void
    {
        $wali = $this->createAccount('guru', true, 'wali');

        $this->actingAs($wali)
            ->get(route('guru.data-siswa'))
            ->assertForbidden();

        $this->get(route('guru.data-siswa.wali'))
            ->assertOk()
            ->assertSee('Data Siswa Kelas Wali')
            ->assertSee('Siswa Satu')
            ->assertDontSee('Siswa Dua');
    }

    public function test_teacher_cannot_open_student_edit_form(): void
    {
        $this->actingAs($this->createAccount('guru'))
            ->get('/guru/data-siswa/1/edit')
            ->assertNotFound();
    }

    public function test_teacher_cannot_update_student_data(): void
    {
        $this->actingAs($this->createAccount('guru'))
            ->put('/guru/data-siswa/1', [
                'nama_lengkap' => 'Nama Tidak Boleh Berubah',
                'nisn' => '1000000001',
                'kelas' => 'X-1',
                'tahun_ajaran' => '2026/2027',
            ])
            ->assertStatus(405);

        $this->assertDatabaseHas('siswa_biodata', [
            'id' => 1,
            'nama_lengkap' => 'Siswa Satu',
        ]);
    }

    private function createAccount(string $role, bool $assigned = true, string $assignment = 'mapel'): Pengguna
    {
        $guruId = null;
        if ($role === 'guru') {
            $guruId = DB::table('guru_biodata')->insertGetId([
                'nama' => 'Guru Uji',
                'nip' => 'NIP-'.(DB::table('guru_biodata')->count() + 1),
            ]);
        }

        $id = DB::table('akun_pengguna')->insertGetId([
            'peran' => $role,
            'username' => $role.'-test',
            'email' => $role.'@example.test',
            'password' => 'password',
            'aktif' => true,
            'wajib_ganti_password' => false,
            'guru_id' => $guruId,
        ]);

        if ($guruId !== null && $assigned && in_array($assignment, ['mapel', 'both'], true)) {
            DB::table('guru_mengajar')->insert([
                'tahun_ajaran_id' => 1,
                'guru_id' => $guruId,
                'kelas_id' => 1,
                'mapel_id' => 1,
            ]);
        }

        if ($guruId !== null && $assigned && in_array($assignment, ['wali', 'both'], true)) {
            DB::table('guru_wali_kelas')->insert([
                'tahun_ajaran_id' => 1,
                'guru_id' => $guruId,
                'kelas_id' => $assignment === 'both' ? 2 : 1,
            ]);
        }

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
