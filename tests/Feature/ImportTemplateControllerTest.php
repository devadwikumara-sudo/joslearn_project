<?php

namespace Tests\Feature;

use App\Models\Auth\Pengguna;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

#[RunTestsInSeparateProcesses]
class ImportTemplateControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:'.base64_encode(str_repeat('j', 32))]);

        Schema::dropIfExists('akun_pengguna');
        Schema::create('akun_pengguna', function (Blueprint $table): void {
            $table->id();
            $table->string('peran');
            $table->string('username')->unique();
            $table->string('email')->nullable();
            $table->string('password');
            $table->boolean('aktif')->default(true);
            $table->boolean('wajib_ganti_password')->default(false);
            $table->timestamp('email_diverifikasi_at')->nullable();
            $table->timestamp('terakhir_login_at')->nullable();
            $table->unsignedBigInteger('guru_id')->nullable();
            $table->unsignedBigInteger('siswa_id')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * @return array<string, array{string, array<int, string>}>
     */
    public static function activeTemplateRoutes(): array
    {
        return [
            'siswa' => ['admin.import.siswa.template', [
                'NISN', 'NIS', 'Nama', 'Kelas', 'Tahun Ajaran', 'Jenis Kelamin',
                'Tempat Lahir', 'Tanggal Lahir', 'Agama', 'NIK', 'Kewarganegaraan',
                'Alamat', 'RT/RW', 'Kelurahan', 'Kecamatan', 'Kabupaten', 'Provinsi',
                'Kode Pos', 'Nomor HP', 'Tinggal', 'Jarak ke Sekolah',
                'Golongan Darah', 'Penyakit', 'Kelainan Fisik', 'Tinggi Badan',
                'Berat Badan', 'Sekolah Asal', 'Nama Ayah', 'Pendidikan Ayah',
                'Pekerjaan Ayah', 'Penghasilan Ayah', 'HP Ayah', 'Nama Ibu',
                'Pendidikan Ibu', 'Pekerjaan Ibu', 'Penghasilan Ibu', 'HP Ibu',
                'Nama Wali', 'Status Siswa',
            ]],
            'guru' => ['admin.import.guru.template', [
                'Nama', 'NIP', 'NUPTK', 'NIK', 'Jenis Kelamin', 'Tempat Lahir',
                'Tanggal Lahir', 'Pendidikan', 'Status Kepegawaian', 'Mulai Bertugas',
                'Nomor HP', 'Alamat',
            ]],
            'nilai' => ['admin.import.nilai.template', ['NISN', 'Nama', 'Kelas', 'Semester', 'Mata Pelajaran', 'Nilai Akhir']],
            'kehadiran' => ['admin.import.kehadiran.template', ['NISN', 'Nama', 'Kelas', 'Semester', 'Sakit (hari)', 'Izin (hari)', 'Alpa (hari)', 'Kokurikuler']],
            'ekskul' => ['admin.import.ekskul.template', ['NISN', 'Nama', 'Kelas', 'Semester', 'Nama Ekskul', 'Predikat']],
        ];
    }

    #[DataProvider('activeTemplateRoutes')]
    public function test_active_template_route_downloads_a_valid_xlsx_with_expected_headers(string $routeName, array $expectedHeaders): void
    {
        $admin = Pengguna::create([
            'peran' => 'admin',
            'username' => 'template-admin',
            'password' => 'unused',
            'aktif' => true,
        ]);

        $response = $this->actingAs($admin)->get(route($routeName));

        $response->assertOk();
        $this->assertStringContainsString(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            (string) $response->headers->get('content-type')
        );
        $this->assertStringContainsString('.xlsx', (string) $response->headers->get('content-disposition'));

        $workbook = IOFactory::load($response->baseResponse->getFile()->getPathname());
        $this->assertSame(['Data', 'Petunjuk'], $workbook->getSheetNames());
        $this->assertSame($expectedHeaders, $workbook->getSheetByName('Data')->toArray()[0]);
        $this->assertStringContainsString('Tidak ada baris contoh', $workbook->getSheetByName('Petunjuk')->getCell('A3')->getValue());
        $workbook->disconnectWorksheets();
    }
}
