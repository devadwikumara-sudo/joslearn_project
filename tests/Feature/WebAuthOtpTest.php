<?php

namespace Tests\Feature;

use App\Models\Auth\Pengguna;
use App\Models\Guru\GuruBiodata;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WebAuthOtpTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('akun_kode_otp');
        Schema::dropIfExists('catatan_aktivitas');
        Schema::dropIfExists('akun_pengguna');
        Schema::dropIfExists('guru_biodata');
        Schema::create('guru_biodata', function (Blueprint $table): void {
            $table->id();
            $table->string('nama');
            $table->string('nip')->nullable()->unique();
            $table->string('email')->nullable();
            $table->string('status_kepegawaian')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('akun_pengguna', function (Blueprint $table): void {
            $table->id();
            $table->string('peran');
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('aktif')->default(false);
            $table->unsignedBigInteger('guru_id')->nullable();
            $table->unsignedBigInteger('siswa_id')->nullable();
            $table->boolean('wajib_ganti_password')->default(false);
            $table->timestamp('email_diverifikasi_at')->nullable();
            $table->timestamp('terakhir_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
        Schema::create('akun_kode_otp', function (Blueprint $table): void {
            $table->id();
            $table->string('email', 120);
            $table->string('tujuan');
            $table->string('kode_hash');
            $table->unsignedTinyInteger('percobaan')->default(0);
            $table->timestamp('berlaku_sampai');
            $table->timestamp('dipakai_at')->nullable();
            $table->timestamp('created_at')->nullable();
        });
        Schema::create('catatan_aktivitas', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('pengguna_id')->nullable();
            $table->string('peran', 10)->nullable();
            $table->string('aksi', 60);
            $table->string('keterangan', 500)->nullable();
            $table->string('objek_tipe', 40)->nullable();
            $table->unsignedBigInteger('objek_id')->nullable();
            $table->string('alamat_ip', 45)->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function test_registration_sends_hashed_otp_and_keeps_account_inactive_until_verification(): void
    {
        config(['auth.admin_registration_code' => 'dummy-admin-code']);

        $isiEmail = null;
        Mail::shouldReceive('raw')
            ->once()
            ->withArgs(function (string $message, callable $callback) use (&$isiEmail): bool {
                $isiEmail = $message;
                $mail = \Mockery::mock(Message::class);
                $mail->shouldReceive('to')->once()->with('admin@example.test')->andReturnSelf();
                $mail->shouldReceive('subject')->once()->with('Kode Verifikasi JosBook')->andReturnSelf();
                $callback($mail);

                return true;
            });

        $response = $this->post(route('daftar.post'), [
            'nama' => 'Admin Contoh',
            'email' => 'admin@example.test',
            'identitas' => 'admin-test',
            'peran' => 'admin',
            'admin_registration_code' => 'dummy-admin-code',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $response->assertRedirect(route('verifikasi-akun'));
        $pengguna = Pengguna::where('email', 'admin@example.test')->firstOrFail();
        $this->assertFalse($pengguna->aktif);
        $this->assertNull($pengguna->email_diverifikasi_at);

        $otp = DB::table('akun_kode_otp')->where('email', $pengguna->email)->first();
        $this->assertNotNull($otp);
        $this->assertSame(0, $otp->percobaan);
        $this->assertStringContainsString('Kode verifikasi pendaftaran akun', (string) $isiEmail);
        preg_match('/Anda: (\d{6})/', (string) $isiEmail, $cocok);
        $this->assertCount(2, $cocok);
        $this->assertTrue(Hash::check($cocok[1], $otp->kode_hash));
        $this->assertNotSame($cocok[1], $otp->kode_hash);

        $this->post(route('verifikasi-akun.post'), ['kode' => $cocok[1]])
            ->assertRedirect(route('admin.dashboard'));
        $this->assertTrue($pengguna->fresh()->aktif);
        $this->assertNotNull($pengguna->fresh()->email_diverifikasi_at);
        $this->assertNotNull($pengguna->fresh()->terakhir_login_at);
        $this->assertSame('verifikasi_pendaftaran', DB::table('catatan_aktivitas')->value('aksi'));
    }

    public function test_admin_registration_is_rejected_without_the_secret_code(): void
    {
        config(['auth.admin_registration_code' => 'dummy-admin-code']);

        $this->from(route('daftar'))->post(route('daftar.post'), [
            'nama' => 'Admin Contoh',
            'email' => 'admin-no-code@example.test',
            'identitas' => 'admin-no-code',
            'peran' => 'admin',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ])
            ->assertRedirect(route('daftar'))
            ->assertSessionHasErrors([
                'admin_registration_code' => 'Kode pendaftaran administrator tidak valid.',
            ]);

        $this->assertDatabaseMissing('akun_pengguna', ['username' => 'admin-no-code']);
    }

    public function test_sixth_failed_web_login_attempt_is_blocked_with_generic_error(): void
    {
        $pengguna = $this->buatAkunReset();

        for ($percobaan = 1; $percobaan <= 5; $percobaan++) {
            $this->from(route('login'))->post(route('login.post'), [
                'username' => $pengguna->username,
                'password' => 'password-salah',
            ])
                ->assertRedirect(route('login'))
                ->assertSessionHasErrors([
                    'username' => 'Username atau kata sandi salah',
                ]);
        }

        $this->from(route('login'))->post(route('login.post'), [
            'username' => $pengguna->username,
            'password' => 'PasswordLama123',
        ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors([
                'username' => 'Username atau kata sandi salah',
            ]);

        $this->assertGuest();
    }

    public function test_inactive_admin_is_rejected_from_admin_routes(): void
    {
        $pengguna = $this->buatAkunReset();
        $pengguna->aktif = false;
        $pengguna->save();

        $this->actingAs($pengguna)
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors([
                'username' => 'Akun Anda tidak aktif. Hubungi administrator.',
            ]);
    }

    public function test_admin_who_must_change_password_is_redirected_from_admin_routes(): void
    {
        $pengguna = $this->buatAkunReset();
        $pengguna->wajib_ganti_password = true;
        $pengguna->save();

        $this->actingAs($pengguna)
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('buat-kata-sandi-baru'));
    }

    public function test_teacher_registration_links_existing_biodata_without_creating_another(): void
    {
        $guru = $this->buatBiodataGuru('1987654321', 'Guru Terdaftar', 'guru@example.test');
        $this->expectOtpEmail('guru@example.test');

        $this->post(route('daftar.post'), [
            'nama' => 'Nama dari Form',
            'email' => 'guru@example.test',
            'identitas' => $guru->nip,
            'peran' => 'guru',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ])->assertRedirect(route('verifikasi-akun'));

        $akun = Pengguna::where('username', $guru->nip)->firstOrFail();
        $this->assertSame($guru->id, $akun->guru_id);
        $this->assertSame(1, GuruBiodata::count());
        $this->assertSame('Guru Terdaftar', $guru->fresh()->nama);
    }

    public function test_teacher_registration_rejects_an_unknown_nip(): void
    {
        $this->from(route('daftar'))->post(route('daftar.post'), [
            'nama' => 'Guru Baru',
            'email' => 'guru-baru@example.test',
            'identitas' => '1112223334',
            'peran' => 'guru',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ])
            ->assertRedirect(route('daftar'))
            ->assertSessionHasErrors([
                'identitas' => 'NIP belum terdaftar, hubungi admin',
            ]);

        $this->assertSame(0, GuruBiodata::count());
        $this->assertSame(0, Pengguna::count());
    }

    public function test_teacher_registration_rejects_a_nip_that_already_has_an_account(): void
    {
        $guru = $this->buatBiodataGuru('1987654321', 'Guru Terdaftar');
        Pengguna::create([
            'peran' => 'guru',
            'username' => $guru->nip,
            'email' => 'guru-lama@example.test',
            'password' => Hash::make('PasswordLama123'),
            'aktif' => true,
            'guru_id' => $guru->id,
        ]);

        $this->from(route('daftar'))->post(route('daftar.post'), [
            'nama' => 'Guru Terdaftar',
            'email' => 'guru-baru@example.test',
            'identitas' => $guru->nip,
            'peran' => 'guru',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ])
            ->assertRedirect(route('daftar'))
            ->assertSessionHasErrors([
                'identitas' => 'NIP ini sudah memiliki akun.',
            ]);

        $this->assertSame(1, Pengguna::count());
        $this->assertSame(1, GuruBiodata::count());
    }

    public function test_admin_can_create_a_teacher_account_with_one_time_temporary_password(): void
    {
        $admin = $this->buatAkunReset();
        $guru = $this->buatBiodataGuru('1987654321', 'Guru Contoh', 'guru@example.test');

        $response = $this->actingAs($admin)
            ->post(route('admin.data-guru.buat-akun', $guru->id))
            ->assertRedirect(route('admin.data-guru'))
            ->assertSessionHas('sandi_sementara');

        $akun = Pengguna::where('guru_id', $guru->id)->firstOrFail();
        $this->assertSame('guru', $akun->peran);
        $this->assertSame($guru->nip, $akun->username);
        $this->assertTrue($akun->aktif);
        $this->assertTrue($akun->wajib_ganti_password);
        $this->assertSame('buat_akun_guru', DB::table('catatan_aktivitas')->value('aksi'));
        $response->assertSessionHas('sandi_sementara', function (array $kredensial) use ($akun): bool {
            return $kredensial['username'] === $akun->username
                && Hash::check($kredensial['password'], $akun->password);
        });
    }

    public function test_admin_can_reset_teacher_password_and_requires_password_change(): void
    {
        $admin = $this->buatAkunReset();
        $guru = $this->buatBiodataGuru('1987654321', 'Guru Contoh', 'guru@example.test');
        $akun = Pengguna::create([
            'peran' => 'guru',
            'username' => $guru->nip,
            'email' => $guru->email,
            'password' => Hash::make('PasswordLama123'),
            'aktif' => true,
            'guru_id' => $guru->id,
        ]);

        $response = $this->actingAs($admin)
            ->post(route('admin.data-guru.reset-sandi', $guru->id))
            ->assertRedirect(route('admin.data-guru'))
            ->assertSessionHas('sandi_sementara');

        $akun->refresh();
        $this->assertTrue($akun->wajib_ganti_password);
        $this->assertFalse(Hash::check('PasswordLama123', $akun->password));
        $this->assertSame('reset_sandi_guru', DB::table('catatan_aktivitas')->value('aksi'));
        $response->assertSessionHas('sandi_sementara', function (array $kredensial) use ($akun): bool {
            return $kredensial['username'] === $akun->username
                && Hash::check($kredensial['password'], $akun->password);
        });
    }

    public function test_teacher_cannot_create_or_reset_teacher_accounts(): void
    {
        $biodataAktif = $this->buatBiodataGuru('1987654321', 'Guru Aktif', 'guru-aktif@example.test');
        $guruAktif = Pengguna::create([
            'peran' => 'guru',
            'username' => $biodataAktif->nip,
            'email' => $biodataAktif->email,
            'password' => Hash::make('PasswordGuru123'),
            'aktif' => true,
            'guru_id' => $biodataAktif->id,
        ]);
        $biodataTarget = $this->buatBiodataGuru('1987654322', 'Guru Target', 'guru-target@example.test');
        $akunTarget = Pengguna::create([
            'peran' => 'guru',
            'username' => $biodataTarget->nip,
            'email' => $biodataTarget->email,
            'password' => Hash::make('PasswordLama123'),
            'aktif' => true,
            'guru_id' => $biodataTarget->id,
        ]);
        $biodataTanpaAkun = $this->buatBiodataGuru('1987654323', 'Guru Belum Punya Akun');

        $this->actingAs($guruAktif)
            ->post(route('admin.data-guru.buat-akun', $biodataTanpaAkun->id))
            ->assertForbidden();

        $this->post(route('admin.data-guru.reset-sandi', $biodataTarget->id))
            ->assertForbidden();

        $this->assertFalse($akunTarget->fresh()->wajib_ganti_password);
        $this->assertSame(2, Pengguna::count());
        $this->assertSame(0, DB::table('catatan_aktivitas')->count());
    }

    public function test_wrong_code_is_rejected_and_sixth_attempt_is_blocked(): void
    {
        $pengguna = $this->buatAkunReset();
        $this->buatOtp($pengguna->email);
        $sesi = ['auth_flow' => 'reset', 'auth_user_id' => $pengguna->id];

        for ($percobaan = 1; $percobaan <= 5; $percobaan++) {
            $this->withSession($sesi)
                ->post(route('verifikasi-akun.post'), ['kode' => '000000'])
                ->assertSessionHasErrors(['kode' => 'Kode verifikasi salah.']);
        }

        $this->withSession($sesi)
            ->post(route('verifikasi-akun.post'), ['kode' => '000000'])
            ->assertSessionHasErrors(['kode' => 'Terlalu banyak percobaan. Silakan kirim ulang kode baru.']);

        $this->assertSame(5, DB::table('akun_kode_otp')->value('percobaan'));
    }

    public function test_password_reset_request_sends_a_hashed_otp_to_the_account_email(): void
    {
        $pengguna = $this->buatAkunReset();
        $isiEmail = null;
        Mail::shouldReceive('raw')
            ->once()
            ->withArgs(function (string $message, callable $callback) use (&$isiEmail, $pengguna): bool {
                $isiEmail = $message;
                $mail = \Mockery::mock(Message::class);
                $mail->shouldReceive('to')->once()->with($pengguna->email)->andReturnSelf();
                $mail->shouldReceive('subject')->once()->with('Kode Verifikasi JosBook')->andReturnSelf();
                $callback($mail);

                return true;
            });

        $this->post(route('reset-sandi.post'), ['email' => $pengguna->username])
            ->assertRedirect(route('verifikasi-akun'));

        $otp = DB::table('akun_kode_otp')->where('email', $pengguna->email)->first();
        $this->assertSame('lupa_password', $otp->tujuan);
        preg_match('/Anda: (\d{6})/', (string) $isiEmail, $cocok);
        $this->assertCount(2, $cocok);
        $this->assertTrue(Hash::check($cocok[1], $otp->kode_hash));
    }

    public function test_expired_code_is_rejected(): void
    {
        $pengguna = $this->buatAkunReset();
        $this->buatOtp($pengguna->email, '123456', now()->subSecond());

        $this->withSession(['auth_flow' => 'reset', 'auth_user_id' => $pengguna->id])
            ->post(route('verifikasi-akun.post'), ['kode' => '123456'])
            ->assertSessionHasErrors(['kode' => 'Kode verifikasi tidak valid atau sudah kedaluwarsa. Kirim ulang kode baru.']);

        $this->assertNull($pengguna->fresh()->email_diverifikasi_at);
    }

    public function test_password_reset_page_and_submission_require_a_verified_otp(): void
    {
        $pengguna = $this->buatAkunReset();
        $passwordLama = $pengguna->password;
        $sesi = ['auth_flow' => 'reset', 'auth_user_id' => $pengguna->id];

        $this->withSession($sesi)
            ->get(route('buat-kata-sandi-baru'))
            ->assertRedirect(route('reset-sandi'));

        $this->withSession($sesi)
            ->post(route('buat-kata-sandi-baru.post'), [
                'password' => 'PasswordBaru123',
                'password_confirmation' => 'PasswordBaru123',
            ])
            ->assertRedirect(route('reset-sandi'));

        $this->assertSame($passwordLama, $pengguna->fresh()->password);
    }

    public function test_valid_reset_otp_allows_password_change_once(): void
    {
        $pengguna = $this->buatAkunReset();
        $this->buatOtp($pengguna->email);
        $sesi = ['auth_flow' => 'reset', 'auth_user_id' => $pengguna->id];

        $this->withSession($sesi)
            ->post(route('verifikasi-akun.post'), ['kode' => '123456'])
            ->assertRedirect(route('buat-kata-sandi-baru'));
        $this->assertNotNull(DB::table('akun_kode_otp')->value('dipakai_at'));

        $this->get(route('buat-kata-sandi-baru'))->assertOk();
        $this->post(route('buat-kata-sandi-baru.post'), [
            'password' => 'PasswordBaru123',
            'password_confirmation' => 'PasswordBaru123',
        ])->assertRedirect(route('selesai-kata-sandi'));

        $this->assertTrue(Hash::check('PasswordBaru123', $pengguna->fresh()->password));
        $this->assertSame('reset_password', DB::table('catatan_aktivitas')->value('aksi'));
        $this->assertNull(session('auth_reset_verified'));
        $this->get(route('buat-kata-sandi-baru'))->assertRedirect(route('reset-sandi'));
    }

    public function test_resend_is_rejected_before_forty_five_seconds(): void
    {
        $pengguna = $this->buatAkunReset();
        $this->buatOtp($pengguna->email);

        $this->withSession(['auth_flow' => 'reset', 'auth_user_id' => $pengguna->id])
            ->post(route('verifikasi-akun.kirim-ulang'))
            ->assertSessionHasErrors(['kode']);

        $this->assertSame(1, DB::table('akun_kode_otp')->where('email', $pengguna->email)->count());
    }

    private function buatAkunReset(): Pengguna
    {
        return Pengguna::create([
            'peran' => 'admin',
            'username' => 'admin-reset',
            'email' => 'reset@example.test',
            'password' => Hash::make('PasswordLama123'),
            'aktif' => true,
        ]);
    }

    private function buatBiodataGuru(string $nip, string $nama, ?string $email = null): GuruBiodata
    {
        return GuruBiodata::create([
            'nama' => $nama,
            'nip' => $nip,
            'email' => $email,
            'status_kepegawaian' => 'Guru',
        ]);
    }

    private function expectOtpEmail(string $email): void
    {
        Mail::shouldReceive('raw')
            ->once()
            ->withArgs(function (string $message, callable $callback) use ($email): bool {
                $mail = \Mockery::mock(Message::class);
                $mail->shouldReceive('to')->once()->with($email)->andReturnSelf();
                $mail->shouldReceive('subject')->once()->with('Kode Verifikasi JosBook')->andReturnSelf();
                $callback($mail);

                return true;
            });
    }

    private function buatOtp(string $email, string $kode = '123456', mixed $berlakuSampai = null): void
    {
        DB::table('akun_kode_otp')->insert([
            'email' => $email,
            'tujuan' => 'lupa_password',
            'kode_hash' => Hash::make($kode),
            'percobaan' => 0,
            'berlaku_sampai' => $berlakuSampai ?? now()->addMinutes(10),
            'dipakai_at' => null,
            'created_at' => now(),
        ]);
    }
}
