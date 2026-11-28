<?php

namespace Tests\Feature;

use App\Models\Auth\Pengguna;
use App\Models\SiswaBiodata;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\TestResponse;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPUnit\Framework\Attributes\RunClassInSeparateProcess;
use Tests\TestCase;

#[RunClassInSeparateProcess]
class CekKualitasDataTest extends TestCase
{
    private int $urutan = 0;

    private int $tahunAjaranId;

    protected function setUp(): void
    {
        parent::setUp();
        DB::purge();
        config(['app.key' => 'base64:'.base64_encode(str_repeat('j', 32))]);

        Schema::dropIfExists('siswa_ekskul');
        Schema::dropIfExists('siswa_kehadiran');
        Schema::dropIfExists('siswa_nilai');
        Schema::dropIfExists('siswa_riwayat_kelas');
        Schema::dropIfExists('siswa_biodata');
        Schema::dropIfExists('sekolah_tahun_ajaran');
        Schema::dropIfExists('akun_pengguna');

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
        Schema::create('siswa_biodata', function (Blueprint $table): void {
            $table->id();
            $table->string('nisn')->nullable();
            $table->string('nomor_induk')->nullable();
            $table->string('nama_lengkap')->nullable();
            $table->boolean('foto_ada')->default(false);
            $table->string('tahun_lulus', 4)->nullable();
            $table->date('tanggal_keluar')->nullable();
            $table->string('alasan_keluar')->nullable();
            $table->string('nomor_skl')->nullable();
            $table->string('nomor_ijazah_sma')->nullable();
            $table->string('nomor_transkrip')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('sekolah_tahun_ajaran', function (Blueprint $table): void {
            $table->id();
            $table->string('nama', 9);
            $table->boolean('sedang_berjalan')->default(false);
        });
        Schema::create('siswa_riwayat_kelas', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('siswa_id');
            $table->unsignedBigInteger('tahun_ajaran_id');
            $table->unsignedBigInteger('kelas_id');
            $table->string('status')->default('Aktif');
            $table->softDeletes();
            $table->timestamps();
        });
        foreach (['siswa_nilai', 'siswa_kehadiran', 'siswa_ekskul'] as $tabel) {
            Schema::create($tabel, function (Blueprint $table): void {
                $table->id();
            });
        }

        DB::table('akun_pengguna')->delete();
        DB::table('siswa_riwayat_kelas')->delete();
        DB::table('siswa_biodata')->delete();
        DB::table('sekolah_tahun_ajaran')->delete();
        DB::table('siswa_nilai')->delete();
        DB::table('siswa_kehadiran')->delete();
        DB::table('siswa_ekskul')->delete();

        $this->tahunAjaranId = DB::table('sekolah_tahun_ajaran')->insertGetId([
            'nama' => '2026/2027',
            'sedang_berjalan' => true,
        ]);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('siswa_ekskul');
        Schema::dropIfExists('siswa_kehadiran');
        Schema::dropIfExists('siswa_nilai');
        Schema::dropIfExists('siswa_riwayat_kelas');
        Schema::dropIfExists('siswa_biodata');
        Schema::dropIfExists('sekolah_tahun_ajaran');
        Schema::dropIfExists('akun_pengguna');
        DB::purge();

        parent::tearDown();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.cek-kualitas-data'))->assertRedirect(route('login'));
    }

    public function test_teacher_cannot_open_the_data_quality_page(): void
    {
        $this->actingAs($this->makeUser('guru', 'guru-uji'))
            ->get(route('admin.laporan', ['tab' => 'kualitas']))
            ->assertForbidden();
    }

    public function test_clean_data_has_no_findings(): void
    {
        $this->makeSiswa();
        $this->makeSiswa();

        $response = $this->actingAs($this->makeUser('admin', 'admin-uji'))
            ->get(route('admin.laporan', ['tab' => 'kualitas']));

        $response->assertOk();
        $response->assertViewHas('masalah', fn ($masalah) => count($masalah) === 0);
        $response->assertViewHas('totalSiswa', 2);
        $response->assertSee('Tidak ditemukan kesalahan format maupun dokumen yang belum lengkap pada basis data siswa aktif.');
    }

    public function test_invalid_or_empty_nisn_is_reported_as_an_error(): void
    {
        $this->makeSiswa(['nisn' => null]);
        $this->makeSiswa(['nisn' => '12345']);
        $this->makeSiswa(['nisn' => '12345abcde']);
        $this->makeSiswa(['nisn' => '12345678901']);

        $response = $this->actingAs($this->makeUser('admin', 'admin-uji'))
            ->get(route('admin.laporan', ['tab' => 'kualitas']));

        $response->assertViewHas('masalah', function ($masalah) {
            $nisn = collect($masalah)->where('kolom', 'NISN');

            return $nisn->count() === 4 && $nisn->every(fn ($m) => $m['jenis'] === 'Kesalahan');
        });
    }

    public function test_empty_name_is_reported_as_an_error_with_the_student_id(): void
    {
        $siswa = $this->makeSiswa(['nama_lengkap' => '']);

        $this->actingAs($this->makeUser('admin', 'admin-uji'))
            ->get(route('admin.laporan', ['tab' => 'kualitas']))
            ->assertViewHas('masalah', function ($masalah) use ($siswa) {
                $temuan = collect($masalah)->firstWhere('kolom', 'Nama Lengkap');

                return $temuan !== null
                    && $temuan['jenis'] === 'Kesalahan'
                    && $temuan['siswa'] === 'ID#'.$siswa->id
                    && $temuan['id'] === $siswa->id;
            });
    }

    public function test_missing_photo_is_reported_as_a_warning(): void
    {
        $this->makeSiswa(['nama_lengkap' => 'Budi Tanpa Foto', 'foto_ada' => false]);

        $this->actingAs($this->makeUser('admin', 'admin-uji'))
            ->get(route('admin.laporan', ['tab' => 'kualitas']))
            ->assertViewHas('masalah', function ($masalah) {
                $temuan = collect($masalah)->firstWhere('kolom', 'Foto');

                return $temuan !== null
                    && $temuan['jenis'] === 'Peringatan'
                    && $temuan['siswa'] === 'Budi Tanpa Foto';
            });
    }

    public function test_graduates_are_excluded_from_active_student_quality_findings(): void
    {
        $this->makeSiswa(['nama_lengkap' => 'Alumni Contoh', 'tahun_lulus' => '2025']);

        $this->actingAs($this->makeUser('admin', 'admin-uji'))
            ->get(route('admin.laporan', ['tab' => 'kualitas']))
            ->assertViewHas('totalSiswa', 0)
            ->assertViewHas('masalah', fn ($masalah) => collect($masalah)
                ->where('kolom', 'Dokumen Lulus')
                ->isEmpty());
    }

    public function test_active_student_without_a_graduation_year_is_not_checked_for_documents(): void
    {
        $this->makeSiswa(['tahun_lulus' => null]);

        $this->actingAs($this->makeUser('admin', 'admin-uji'))
            ->get(route('admin.laporan', ['tab' => 'kualitas']))
            ->assertViewHas('masalah', fn ($masalah) => collect($masalah)->where('kolom', 'Dokumen Lulus')->isEmpty());
    }

    public function test_duplicate_nisn_is_reported_once_with_the_number_of_students(): void
    {
        $this->makeSiswa(['nisn' => '1234567890']);
        $this->makeSiswa(['nisn' => '1234567890']);
        $this->makeSiswa(['nisn' => '1234567891']);

        $this->actingAs($this->makeUser('admin', 'admin-uji'))
            ->get(route('admin.laporan', ['tab' => 'kualitas']))
            ->assertViewHas('masalah', function ($masalah) {
                $duplikat = collect($masalah)->where('kolom', 'NISN Duplikat');

                return $duplikat->count() === 1
                    && $duplikat->first()['jenis'] === 'Kesalahan'
                    && $duplikat->first()['masalah'] === 'NISN duplikat — ditemukan pada 2 siswa'
                    && $duplikat->first()['id'] === null;
            });
    }

    public function test_duplicate_student_number_is_reported_as_an_error(): void
    {
        $this->makeSiswa(['nomor_induk' => 'NIS-100']);
        $this->makeSiswa(['nomor_induk' => 'NIS-100']);
        $this->makeSiswa(['nomor_induk' => null]);
        $this->makeSiswa(['nomor_induk' => null]);

        $this->actingAs($this->makeUser('admin', 'admin-uji'))
            ->get(route('admin.laporan', ['tab' => 'kualitas']))
            ->assertViewHas('masalah', function ($masalah) {
                $duplikat = collect($masalah)->where('kolom', 'Nomor Induk Duplikat');

                return $duplikat->count() === 1
                    && $duplikat->first()['masalah'] === 'Nomor Induk duplikat — ditemukan pada 2 siswa';
            });
    }

    public function test_deleted_students_are_ignored_by_every_check(): void
    {
        $this->makeSiswa(['nisn' => '1234567890', 'nomor_induk' => 'NIS-1']);
        $this->makeSiswa(['nisn' => '1234567890', 'nomor_induk' => 'NIS-1'])->delete();
        $this->makeSiswa(['nisn' => 'rusak', 'foto_ada' => false])->delete();

        $response = $this->actingAs($this->makeUser('admin', 'admin-uji'))
            ->get(route('admin.laporan', ['tab' => 'kualitas']));

        $response->assertViewHas('totalSiswa', 1);
        $response->assertViewHas('masalah', fn ($masalah) => count($masalah) === 0);
    }

    public function test_filter_limits_the_findings_to_errors_or_warnings(): void
    {
        $this->makeSiswa(['nisn' => '123']);                  // Kesalahan (NISN)
        $this->makeSiswa(['foto_ada' => false]);              // Peringatan (Foto)
        $admin = $this->makeUser('admin', 'admin-uji');

        $semua = $this->actingAs($admin)->get(route('admin.laporan', ['tab' => 'kualitas']));
        $semua->assertViewHas('masalah', fn ($m) => collect($m)->pluck('jenis')->unique()->sort()->values()->all()
            === ['Kesalahan', 'Peringatan']);

        $this->actingAs($admin)
            ->get(route('admin.laporan', ['tab' => 'kualitas', 'jenis' => 'kesalahan']))
            ->assertViewHas('filter', 'kesalahan')
            ->assertViewHas('masalah', fn ($m) => count($m) > 0
                && collect($m)->every(fn ($x) => $x['jenis'] === 'Kesalahan'));

        $this->actingAs($admin)
            ->get(route('admin.laporan', ['tab' => 'kualitas', 'jenis' => 'peringatan']))
            ->assertViewHas('masalah', fn ($m) => count($m) > 0
                && collect($m)->every(fn ($x) => $x['jenis'] === 'Peringatan'));
    }

    public function test_summary_counts_students_and_academic_rows(): void
    {
        $this->makeSiswa(['nisn' => '123']);     // 1 kesalahan
        $this->makeSiswa(['foto_ada' => false]); // 1 peringatan
        DB::table('siswa_nilai')->insert([['id' => 1], ['id' => 2]]);
        DB::table('siswa_kehadiran')->insert([['id' => 1]]);
        DB::table('siswa_ekskul')->insert([['id' => 1]]);

        $response = $this->actingAs($this->makeUser('admin', 'admin-uji'))
            ->get(route('admin.laporan', ['tab' => 'kualitas']));

        $response->assertViewHas('totalSiswa', 2);
        $response->assertViewHas('totalBaris', 4);
        $response->assertViewHas('jumlahKesalahan', 1);
        $response->assertViewHas('jumlahPeringatan', 1);
    }

    public function test_findings_can_be_exported_as_xlsx(): void
    {
        $this->makeSiswa(['nama_lengkap' => 'Siswa Bermasalah', 'nisn' => '123']);

        $response = $this->actingAs($this->makeUser('admin', 'admin-uji'))
            ->get(route('admin.laporan', ['tab' => 'kualitas', 'export' => 1]));

        $response->assertOk();
        $this->assertStringContainsString(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $response->headers->get('Content-Type')
        );
        $this->assertStringContainsString(
            'attachment; filename=laporan-kualitas-data-',
            $response->headers->get('Content-Disposition')
        );

        $rows = $this->xlsxRows($response);
        $this->assertSame(['Jenis', 'Kolom', 'Nama Siswa', 'NISN', 'Masalah'], $rows[0]);
        $this->assertSame('Siswa Bermasalah', $rows[1][2]);
    }

    public function test_xlsx_export_respects_the_filter(): void
    {
        $this->makeSiswa(['nama_lengkap' => 'Siswa Salah', 'nisn' => '123']);
        $this->makeSiswa(['nama_lengkap' => 'Siswa Tanpa Foto', 'foto_ada' => false]);

        $rows = $this->xlsxRows($this->actingAs($this->makeUser('admin', 'admin-uji'))
            ->get(route('admin.laporan', ['tab' => 'kualitas', 'export' => 1, 'jenis' => 'peringatan'])));

        $this->assertCount(2, $rows);
        $this->assertSame('Siswa Tanpa Foto', $rows[1][2]);
    }

    public function test_the_legacy_export_route_redirects_to_the_quality_data_xlsx_export(): void
    {
        $response = $this->actingAs($this->makeUser('admin', 'admin-uji'))
            ->get(route('admin.cek-kualitas-data.export'));

        $response->assertRedirect(route('admin.laporan', ['tab' => 'kualitas', 'export' => 1]));
    }

    private function makeUser(string $role, string $username): Pengguna
    {
        return Pengguna::create([
            'peran' => $role,
            'username' => $username,
            'email' => $username.'@example.test',
            'password' => 'kata-sandi-uji',
            'aktif' => true,
        ]);
    }

    /**
     * Siswa yang lolos semua pemeriksaan; timpa kolom tertentu untuk membuat temuan.
     */
    private function makeSiswa(array $attributes = []): SiswaBiodata
    {
        $this->urutan++;

        $siswa = SiswaBiodata::create(array_merge([
            'nisn' => (string) (1000000000 + $this->urutan),
            'nomor_induk' => null,
            'nama_lengkap' => 'Siswa '.$this->urutan,
            'foto_ada' => true,
            'tahun_lulus' => null,
            'tanggal_keluar' => null,
            'alasan_keluar' => null,
        ], $attributes));

        DB::table('siswa_riwayat_kelas')->insert([
            'siswa_id' => $siswa->id,
            'tahun_ajaran_id' => $this->tahunAjaranId,
            'kelas_id' => 1,
            'status' => 'Aktif',
        ]);

        return $siswa;
    }

    /**
     * Baca isi XLSX hasil ekspor menjadi baris-baris, termasuk baris judul.
     *
     * @return array<int, array<int, string|null>>
     */
    private function xlsxRows(TestResponse $response): array
    {
        $response->assertOk();
        $spreadsheet = IOFactory::load($response->baseResponse->getFile()->getPathname());
        $rows = $spreadsheet->getActiveSheet()->toArray();
        $spreadsheet->disconnectWorksheets();

        return array_map('array_values', $rows);
    }
}
