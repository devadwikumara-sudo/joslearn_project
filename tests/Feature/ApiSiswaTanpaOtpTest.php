<?php

namespace Tests\Feature;

use App\Models\Auth\AkunSiswa;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Mockery;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tests\TestCase;

#[RunTestsInSeparateProcesses]
class ApiSiswaTanpaOtpTest extends TestCase
{
    private const TABLES = [
        'akun_perangkat_login',
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

        Schema::create('akun_perangkat_login', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('akun_siswa_id')->nullable();
            $table->unsignedBigInteger('token_id')->nullable();
            $table->string('nama_perangkat')->nullable();
            $table->string('alamat_ip')->nullable();
            $table->timestamp('terakhir_aktif_at')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        config(['app.url' => 'https://sekolah.example']);
    }

    protected function tearDown(): void
    {
        foreach (self::TABLES as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_registered_student_can_login_immediately_without_otp(): void
    {
        Mail::shouldReceive('raw')->never();
        $this->buatSiswa('Siswa Satu', '1234567890');

        $this->postJson('/api/v1/daftar', [
            'nama' => 'Siswa Satu',
            'nisn' => '1234567890',
            'email' => 'Siswa@Example.test',
            'password' => 'password-rahasia',
        ])->assertCreated();

        $this->postJson('/api/v1/login', [
            'username' => '1234567890',
            'password' => 'password-rahasia',
        ])->assertOk()->assertJsonStructure(['data' => ['token']]);

        $this->assertDatabaseHas('akun_siswa', ['username' => '1234567890', 'email' => 'siswa@example.test']);
    }

    public function test_registration_with_a_wrong_name_is_rejected(): void
    {
        $this->buatSiswa('Siswa Satu', '1234567890');

        $this->postJson('/api/v1/daftar', [
            'nama' => 'Orang Lain',
            'nisn' => '1234567890',
            'email' => 'siswa@example.test',
            'password' => 'password-rahasia',
        ])->assertStatus(422)->assertJsonPath('kode', 'register_gagal');

        $this->assertSame(0, DB::table('akun_siswa')->count());
    }

    public function test_registration_cannot_take_over_an_existing_or_disabled_account(): void
    {
        $aktif = $this->buatSiswa('Siswa Satu', '1234567890');
        $nonaktif = $this->buatSiswa('Siswa Dua', '1234567891');
        $this->buatAkun('1234567890', $aktif, true);
        $akunNonaktif = $this->buatAkun('1234567891', $nonaktif, false);
        $sandiLama = $akunNonaktif->password;

        foreach ([['Siswa Satu', '1234567890'], ['Siswa Dua', '1234567891']] as [$nama, $nisn]) {
            $this->postJson('/api/v1/daftar', [
                'nama' => $nama,
                'nisn' => $nisn,
                'email' => 'baru'.$nisn.'@example.test',
                'password' => 'password-penyerang',
            ])->assertStatus(422)->assertJsonPath('kode', 'register_gagal');
        }

        $akunNonaktif->refresh();
        $this->assertFalse($akunNonaktif->aktif);
        $this->assertSame($sandiLama, $akunNonaktif->password);
        $this->assertSame(2, DB::table('akun_siswa')->count());
    }

    public function test_disabled_account_cannot_login(): void
    {
        $this->buatAkun('1234567891', $this->buatSiswa('Siswa Dua', '1234567891'), false);

        $this->postJson('/api/v1/login', [
            'username' => '1234567891',
            'password' => 'password-lama',
        ])->assertStatus(403)->assertJsonPath('kode', 'akun_nonaktif');
    }

    public function test_forgot_password_emails_a_reset_link_for_an_active_account(): void
    {
        $this->buatAkun('1234567890', $this->buatSiswa('Siswa Satu', '1234567890'), true);
        $isiEmail = $this->harapkanEmail('siswa1234567890@example.test');

        $this->postJson('/api/v1/lupa-password', ['email' => 'siswa1234567890@example.test'])
            ->assertOk()
            ->assertJsonPath('pesan', 'Jika email terdaftar, tautan untuk membuat kata sandi baru telah dikirim.')
            ->assertJsonStructure(['data' => ['email']]);

        $this->assertStringContainsString('https://sekolah.example/siswa/reset-sandi?token=', $isiEmail());
    }

    public function test_forgot_password_gives_the_same_answer_for_unknown_and_disabled_accounts(): void
    {
        Mail::shouldReceive('raw')->never();
        $this->buatAkun('1234567891', $this->buatSiswa('Siswa Dua', '1234567891'), false);

        foreach (['tidak-ada@example.test', 'siswa1234567891@example.test'] as $email) {
            $this->postJson('/api/v1/lupa-password', ['email' => $email])
                ->assertOk()
                ->assertJsonPath('pesan', 'Jika email terdaftar, tautan untuk membuat kata sandi baru telah dikirim.')
                ->assertJsonMissingPath('data.tautan_debug');
        }
    }

    public function test_reset_link_sets_a_new_password_once_and_signs_out_all_devices(): void
    {
        $akun = $this->buatAkun('1234567890', $this->buatSiswa('Siswa Satu', '1234567890'), true);
        $akun->forceFill(['wajib_ganti_password' => true])->save();
        $akun->createToken('perangkat-lama');
        DB::table('akun_perangkat_login')->insert(['akun_siswa_id' => $akun->id, 'token_id' => 1, 'created_at' => now()]);
        $isiEmail = $this->harapkanEmail('siswa1234567890@example.test');

        $this->postJson('/api/v1/lupa-password', ['email' => 'siswa1234567890@example.test'])->assertOk();
        preg_match('~/siswa/reset-sandi\?token=(\S+)~', $isiEmail(), $cocok);
        $this->assertCount(2, $cocok);
        $token = urldecode($cocok[1]);

        $this->get('/siswa/reset-sandi?token='.urlencode($token))
            ->assertOk()
            ->assertSee('Buat Kata Sandi Baru');

        $this->post('/siswa/reset-sandi', [
            'token' => $token,
            'password' => 'sandi-baru-123',
            'password_confirmation' => 'sandi-baru-123',
        ])->assertOk()->assertSee('Kata Sandi Berhasil Diganti');

        $akun->refresh();
        $this->assertTrue(Hash::check('sandi-baru-123', $akun->password));
        $this->assertFalse((bool) $akun->wajib_ganti_password);
        $this->assertSame(0, $akun->tokens()->count());
        $this->assertSame(0, DB::table('akun_perangkat_login')->where('akun_siswa_id', $akun->id)->count());

        // Tautan sekali pakai: sandi sudah berganti sehingga token lama tidak berlaku
        $this->get('/siswa/reset-sandi?token='.urlencode($token))->assertStatus(410);
        $this->post('/siswa/reset-sandi', [
            'token' => $token,
            'password' => 'sandi-lain-456',
            'password_confirmation' => 'sandi-lain-456',
        ])->assertStatus(410);
        $this->assertTrue(Hash::check('sandi-baru-123', $akun->fresh()->password));
    }

    public function test_reset_page_rejects_missing_forged_and_disabled_account_tokens(): void
    {
        $this->get('/siswa/reset-sandi')->assertStatus(410);
        $this->get('/siswa/reset-sandi?token=token-palsu')->assertStatus(410);

        $nonaktif = $this->buatAkun('1234567891', $this->buatSiswa('Siswa Dua', '1234567891'), false);
        $token = app(\App\Services\ResetSandiSiswaService::class)->buatToken($nonaktif);

        $this->get('/siswa/reset-sandi?token='.urlencode($token))->assertStatus(410);
        $this->post('/siswa/reset-sandi', [
            'token' => $token,
            'password' => 'sandi-baru-123',
            'password_confirmation' => 'sandi-baru-123',
        ])->assertStatus(410);
        $this->assertFalse(Hash::check('sandi-baru-123', $nonaktif->fresh()->password));
    }

    public function test_reset_form_validates_password_confirmation(): void
    {
        $akun = $this->buatAkun('1234567890', $this->buatSiswa('Siswa Satu', '1234567890'), true);
        $token = app(\App\Services\ResetSandiSiswaService::class)->buatToken($akun);

        $this->post('/siswa/reset-sandi', [
            'token' => $token,
            'password' => 'sandi-baru-123',
            'password_confirmation' => 'beda',
        ])->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('password-lama', $akun->fresh()->password));
    }

    public function test_otp_endpoints_for_mobile_no_longer_exist(): void
    {
        foreach (['verifikasi-otp', 'kirim-ulang-otp', 'reset-password'] as $jalur) {
            $this->postJson('/api/v1/'.$jalur, [])->assertNotFound();
        }
    }

    /**
     * Pasang harapan satu email dan kembalikan penutup untuk membaca isinya.
     */
    private function harapkanEmail(string $tujuan): \Closure
    {
        $isi = null;

        Mail::shouldReceive('raw')
            ->once()
            ->withArgs(function (string $pesan, callable $callback) use (&$isi, $tujuan): bool {
                $isi = $pesan;
                $mail = Mockery::mock(Message::class);
                $mail->shouldReceive('to')->once()->with($tujuan)->andReturnSelf();
                $mail->shouldReceive('subject')->once()->with('Atur Ulang Kata Sandi JosBook')->andReturnSelf();
                $callback($mail);

                return true;
            });

        return function () use (&$isi): string {
            return (string) $isi;
        };
    }

    private function buatSiswa(string $nama, string $nisn): int
    {
        return (int) DB::table('siswa_biodata')->insertGetId([
            'nama_lengkap' => $nama,
            'nisn' => $nisn,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function buatAkun(string $nisn, int $siswaId, bool $aktif): AkunSiswa
    {
        return AkunSiswa::query()->create([
            'username' => $nisn,
            'email' => 'siswa'.$nisn.'@example.test',
            'password' => 'password-lama',
            'aktif' => $aktif,
            'siswa_id' => $siswaId,
            'wajib_ganti_password' => false,
        ]);
    }
}
