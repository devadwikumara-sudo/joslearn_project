<?php

namespace Tests\Feature;

use App\Models\Auth\Pengguna;
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
