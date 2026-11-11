<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StudentAttachmentDownloadTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('siswa_berkas');
        Schema::dropIfExists('catatan_aktivitas');
        Schema::dropIfExists('siswa_riwayat_kelas');
        Schema::dropIfExists('sekolah_kelas');
        Schema::dropIfExists('sekolah_tahun_ajaran');
        Schema::dropIfExists('siswa_biodata');

        Schema::create('siswa_biodata', function (Blueprint $table): void {
            $table->id();
            $table->string('nama_lengkap')->nullable();
            $table->string('nisn')->nullable();
            $table->boolean('kk_ada')->default(false);
            $table->boolean('akta_ada')->default(false);
            $table->boolean('ijazah_smp_ada')->default(false);
            $table->boolean('surat_pindah_ada')->default(false);
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
            $table->unsignedInteger('ukuran_byte')->nullable();
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

        Schema::create('catatan_aktivitas', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('pengguna_id')->nullable();
            $table->string('peran');
            $table->string('aksi');
            $table->text('keterangan');
            $table->string('objek_tipe')->nullable();
            $table->unsignedBigInteger('objek_id')->nullable();
            $table->string('alamat_ip')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('siswa_berkas');
        Schema::dropIfExists('catatan_aktivitas');
        Schema::dropIfExists('siswa_riwayat_kelas');
        Schema::dropIfExists('sekolah_kelas');
        Schema::dropIfExists('sekolah_tahun_ajaran');
        Schema::dropIfExists('siswa_biodata');

        parent::tearDown();
    }

    public function test_admin_can_download_a_student_attachment_from_private_storage(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('siswa/berkas/1/kk.pdf', 'student document');
        DB::table('siswa_biodata')->insert([
            'id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('siswa_berkas')->insert([
            'siswa_id' => 1,
            'jenis' => 'kk',
            'nama_file' => 'kartu-keluarga.pdf',
            'lokasi' => 'siswa/berkas/1/kk.pdf',
            'tipe_file' => 'application/pdf',
            'ukuran_byte' => 16,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withoutMiddleware()
            ->get(route('admin.data-siswa.berkas', ['id' => 1, 'jenis' => 'kk']));

        $response->assertOk()->assertDownload('kartu-keluarga.pdf');
    }

    public function test_attachment_download_rejects_unknown_document_types(): void
    {
        $this->withoutMiddleware()
            ->get(route('admin.data-siswa.berkas', ['id' => 1, 'jenis' => '../private']))
            ->assertNotFound();
    }

    public function test_student_form_uploads_and_records_a_document(): void
    {
        Storage::fake('local');
        DB::table('sekolah_tahun_ajaran')->insert([
            'nama' => '2025/2026',
            'sedang_berjalan' => true,
        ]);
        DB::table('sekolah_kelas')->insert(['nama' => 'X-1']);

        $response = $this->withoutMiddleware()->post(route('admin.form-pendataan.store'), [
            'nama_lengkap' => 'Siswa Uji',
            'nisn' => '1234567890',
            'kelas' => 'X-1',
            'tahun_ajaran' => '2025/2026',
            'berkas' => [
                'kk' => UploadedFile::fake()->create('kartu-keluarga.pdf', 32, 'application/pdf'),
            ],
        ]);

        $response->assertRedirect(route('admin.data-siswa', [
            'kelas' => 'X-1',
            'tahun_ajaran' => 1,
        ]));

        $attachment = DB::table('siswa_berkas')->where('jenis', 'kk')->first();
        $this->assertNotNull($attachment);
        $this->assertSame('kartu-keluarga.pdf', $attachment->nama_file);
        Storage::disk('local')->assertExists($attachment->lokasi);
        $this->assertTrue((bool) DB::table('siswa_biodata')->value('kk_ada'));
    }
}
