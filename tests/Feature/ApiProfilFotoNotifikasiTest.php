<?php

namespace Tests\Feature;

use App\Models\Auth\AkunSiswa;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tests\TestCase;

#[RunTestsInSeparateProcesses]
class ApiProfilFotoNotifikasiTest extends TestCase
{
    private const TABLES = [
        'akun_notifikasi',
        'personal_access_tokens',
        'akun_siswa',
        'siswa_biodata',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        foreach (self::TABLES as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('siswa_biodata', function (Blueprint $table): void {
            $table->id();
            $table->string('nama_lengkap');
            $table->string('nisn')->nullable();
            $table->string('foto')->nullable();
            $table->boolean('foto_ada')->default(false);
            $table->softDeletes();
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
            $table->string('jenis')->default('info');
            $table->timestamp('dibaca_at')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        Storage::fake('local');
    }

    protected function tearDown(): void
    {
        foreach (self::TABLES as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_student_can_upload_a_photo_that_is_stored_privately(): void
    {
        [$akun, $token] = $this->createStudentAccount('Siswa Satu', '1234567890');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->withHeader('Accept', 'application/json')
            ->post('/api/v1/profil/foto', [
                'foto_file' => UploadedFile::fake()->create('foto.jpg', 100, 'image/jpeg'),
            ])
            ->assertOk()
            ->assertJsonPath('pesan', 'Foto profil berhasil diperbarui.');

        $siswa = DB::table('siswa_biodata')->where('id', $akun->siswa_id)->first();

        $this->assertStringStartsWith('siswa/foto/foto_1234567890_', $siswa->foto);
        $this->assertSame(1, (int) $siswa->foto_ada);
        Storage::disk('local')->assertExists($siswa->foto);
        $this->assertDatabaseHas('akun_notifikasi', [
            'akun_siswa_id' => $akun->id,
            'judul' => 'Foto profil berhasil diperbarui',
        ]);
    }

    public function test_new_photo_replaces_and_deletes_the_previous_private_file(): void
    {
        [$akun, $token] = $this->createStudentAccount('Siswa Satu', '1234567890');
        Storage::disk('local')->put('siswa/foto/foto_lama.jpg', 'lama');
        DB::table('siswa_biodata')->where('id', $akun->siswa_id)->update([
            'foto' => 'siswa/foto/foto_lama.jpg',
            'foto_ada' => true,
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->withHeader('Accept', 'application/json')
            ->post('/api/v1/profil/foto', [
                'foto_file' => UploadedFile::fake()->create('baru.png', 100, 'image/png'),
            ])
            ->assertOk();

        $siswa = DB::table('siswa_biodata')->where('id', $akun->siswa_id)->first();

        $this->assertNotSame('siswa/foto/foto_lama.jpg', $siswa->foto);
        Storage::disk('local')->assertMissing('siswa/foto/foto_lama.jpg');
        Storage::disk('local')->assertExists($siswa->foto);
    }

    public function test_replacing_a_photo_never_deletes_files_outside_the_photo_folder(): void
    {
        [$akun, $token] = $this->createStudentAccount('Siswa Satu', '1234567890');
        Storage::disk('local')->put('rahasia.txt', 'jangan dihapus');
        DB::table('siswa_biodata')->where('id', $akun->siswa_id)->update([
            'foto' => 'siswa/foto/../rahasia.txt',
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->withHeader('Accept', 'application/json')
            ->post('/api/v1/profil/foto', [
                'foto_file' => UploadedFile::fake()->create('baru.jpg', 100, 'image/jpeg'),
            ])
            ->assertOk();

        Storage::disk('local')->assertExists('rahasia.txt');
    }

    public function test_photo_upload_rejects_missing_non_image_and_oversized_files(): void
    {
        [$akun, $token] = $this->createStudentAccount('Siswa Satu', '1234567890');

        $kirim = fn (array $data) => $this->withHeader('Authorization', 'Bearer '.$token)
            ->withHeader('Accept', 'application/json')
            ->post('/api/v1/profil/foto', $data);

        $kirim([])->assertStatus(422)->assertJsonValidationErrors(['foto_file']);

        $kirim(['foto_file' => UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf')])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['foto_file']);

        $kirim(['foto_file' => UploadedFile::fake()->create('besar.jpg', 3000, 'image/jpeg')])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['foto_file']);

        $this->assertNull(DB::table('siswa_biodata')->where('id', $akun->siswa_id)->value('foto'));
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_photo_upload_requires_a_valid_token(): void
    {
        $this->withHeader('Accept', 'application/json')
            ->post('/api/v1/profil/foto', [
                'foto_file' => UploadedFile::fake()->create('foto.jpg', 100, 'image/jpeg'),
            ])
            ->assertUnauthorized();

        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_photo_upload_only_changes_the_authenticated_students_record(): void
    {
        [$akun, $token] = $this->createStudentAccount('Siswa Satu', '1234567890');
        [$lain] = $this->createStudentAccount('Siswa Lain', '1234567891');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->withHeader('Accept', 'application/json')
            ->post('/api/v1/profil/foto', [
                'foto_file' => UploadedFile::fake()->create('foto.jpg', 100, 'image/jpeg'),
            ])
            ->assertOk();

        $this->assertNotNull(DB::table('siswa_biodata')->where('id', $akun->siswa_id)->value('foto'));
        $this->assertNull(DB::table('siswa_biodata')->where('id', $lain->siswa_id)->value('foto'));
    }

    public function test_student_can_mark_one_notification_as_read(): void
    {
        [$akun, $token] = $this->createStudentAccount('Siswa Satu', '1234567890');
        $dibaca = $this->createNotification($akun->id, 'Pertama');
        $tetapBaru = $this->createNotification($akun->id, 'Kedua');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/notifikasi/{$dibaca}/baca")
            ->assertOk()
            ->assertJsonPath('pesan', 'Notifikasi ditandai sudah dibaca.');

        $this->assertNotNull(DB::table('akun_notifikasi')->where('id', $dibaca)->value('dibaca_at'));
        $this->assertNull(DB::table('akun_notifikasi')->where('id', $tetapBaru)->value('dibaca_at'));
    }

    public function test_student_cannot_mark_another_students_notification_as_read(): void
    {
        [, $token] = $this->createStudentAccount('Siswa Satu', '1234567890');
        [$lain] = $this->createStudentAccount('Siswa Lain', '1234567891');
        $milikLain = $this->createNotification($lain->id, 'Milik siswa lain');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/notifikasi/{$milikLain}/baca")
            ->assertNotFound();

        $this->assertNull(DB::table('akun_notifikasi')->where('id', $milikLain)->value('dibaca_at'));
    }

    public function test_marking_an_unknown_notification_returns_not_found(): void
    {
        [, $token] = $this->createStudentAccount('Siswa Satu', '1234567890');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/notifikasi/999/baca')
            ->assertNotFound();
    }

    public function test_marking_a_notification_requires_a_valid_token(): void
    {
        $this->postJson('/api/v1/notifikasi/1/baca')->assertUnauthorized();
    }

    /**
     * @return array{0: AkunSiswa, 1: string}
     */
    private function createStudentAccount(string $nama, string $nisn): array
    {
        $siswaId = (int) DB::table('siswa_biodata')->insertGetId([
            'nama_lengkap' => $nama,
            'nisn' => $nisn,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $akun = AkunSiswa::query()->create([
            'username' => $nisn,
            'email' => $nisn.'@example.test',
            'password' => 'test-password',
            'aktif' => true,
            'siswa_id' => $siswaId,
            'wajib_ganti_password' => false,
        ]);

        return [$akun, $akun->createToken('uji')->plainTextToken];
    }

    private function createNotification(int $akunSiswaId, string $judul): int
    {
        return (int) DB::table('akun_notifikasi')->insertGetId([
            'akun_siswa_id' => $akunSiswaId,
            'judul' => $judul,
            'isi' => null,
            'jenis' => 'info',
            'created_at' => now(),
        ]);
    }
}
