<?php

namespace Tests\Feature;

use App\Models\Auth\AkunSiswa;
use App\Models\Auth\Pengguna;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SeparateStudentAccountsMigrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'personal_access_tokens',
            'akun_perangkat_login',
            'akun_notifikasi',
            'akun_siswa',
            'akun_kode_otp',
            'akun_pengguna',
            'siswa_biodata',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('siswa_biodata', function (Blueprint $table): void {
            $table->id();
            $table->string('nama_lengkap');
        });

        Schema::create('akun_pengguna', function (Blueprint $table): void {
            $table->id();
            $table->string('peran');
            $table->string('username')->unique();
            $table->string('email')->nullable();
            $table->timestamp('email_diverifikasi_at')->nullable();
            $table->string('password');
            $table->boolean('wajib_ganti_password')->default(false);
            $table->boolean('aktif')->default(true);
            $table->unsignedBigInteger('guru_id')->nullable();
            $table->unsignedBigInteger('siswa_id')->nullable();
            $table->timestamp('terakhir_login_at')->nullable();
            $table->string('remember_token')->nullable();
            $table->timestamps();
        });

        Schema::create('akun_kode_otp', function (Blueprint $table): void {
            $table->id();
            $table->string('email');
            $table->string('tujuan');
            $table->string('kode_hash');
            $table->unsignedTinyInteger('percobaan')->default(0);
            $table->timestamp('berlaku_sampai');
            $table->timestamp('dipakai_at')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('akun_notifikasi', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('pengguna_id');
            $table->string('judul');
            $table->string('isi')->nullable();
            $table->string('jenis')->default('info');
            $table->timestamp('dibaca_at')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->foreign('pengguna_id', 'fk_notifikasi_pengguna')
                ->references('id')
                ->on('akun_pengguna')
                ->cascadeOnDelete();
        });

        Schema::create('akun_perangkat_login', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('pengguna_id');
            $table->unsignedBigInteger('token_id')->nullable();
            $table->string('nama_perangkat')->nullable();
            $table->string('alamat_ip')->nullable();
            $table->timestamp('terakhir_aktif_at')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->foreign('pengguna_id', 'fk_sesi_pengguna')
                ->references('id')
                ->on('akun_pengguna')
                ->cascadeOnDelete();
        });

        Schema::create('personal_access_tokens', function (Blueprint $table): void {
            $table->id();
            $table->morphs('tokenable');
            $table->text('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function test_student_accounts_and_mobile_data_move_and_can_be_restored(): void
    {
        DB::table('siswa_biodata')->insert(['id' => 22, 'nama_lengkap' => 'Siswa Uji']);
        DB::table('akun_pengguna')->insert([
            'id' => 1,
            'peran' => 'admin',
            'username' => 'admin-web',
            'email' => 'admin@example.test',
            'password' => 'admin-hash',
            'aktif' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('akun_pengguna')->insert([
            'id' => 2,
            'peran' => 'siswa',
            'username' => '1234567890',
            'email' => 'siswa@example.test',
            'email_diverifikasi_at' => now(),
            'password' => 'student-hash',
            'aktif' => true,
            'siswa_id' => 22,
            'wajib_ganti_password' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('akun_kode_otp')->insert([
            'id' => 1,
            'email' => 'siswa@example.test',
            'tujuan' => 'daftar',
            'kode_hash' => 'otp-hash',
            'percobaan' => 1,
            'berlaku_sampai' => now()->addMinutes(10),
            'created_at' => now(),
        ]);
        DB::table('akun_notifikasi')->insert([
            'id' => 1,
            'pengguna_id' => 2,
            'judul' => 'Notifikasi uji',
            'isi' => 'Isi uji',
            'jenis' => 'info',
            'created_at' => now(),
        ]);
        DB::table('akun_perangkat_login')->insert([
            'id' => 1,
            'pengguna_id' => 2,
            'token_id' => 1,
            'nama_perangkat' => 'Perangkat uji',
            'created_at' => now(),
        ]);
        DB::table('personal_access_tokens')->insert([
            'id' => 1,
            'tokenable_type' => Pengguna::class,
            'tokenable_id' => 2,
            'name' => 'Perangkat uji',
            'token' => str_repeat('a', 64),
            'abilities' => '["siswa"]',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $migration = require database_path('migrations/2026_11_21_193250_separate_student_accounts_database.php');
        $migration->up();

        $this->assertDatabaseHas('akun_pengguna', ['id' => 1, 'peran' => 'admin']);
        $this->assertDatabaseMissing('akun_pengguna', ['id' => 2]);
        $this->assertDatabaseHas('akun_siswa', [
            'id' => 2,
            'siswa_id' => 22,
            'password' => 'student-hash',
        ]);
        $this->assertDatabaseHas('akun_notifikasi', [
            'id' => 1,
            'pengguna_id' => null,
            'akun_siswa_id' => 2,
        ]);
        $this->assertDatabaseHas('akun_perangkat_login', [
            'id' => 1,
            'pengguna_id' => null,
            'akun_siswa_id' => 2,
        ]);
        $this->assertSame(
            AkunSiswa::class,
            DB::table('personal_access_tokens')->where('id', 1)->value('tokenable_type')
        );
        $this->assertSame(
            1,
            DB::table('akun_kode_otp')->where('id', 1)->count()
        );

        $migration->down();

        $restoredAccount = DB::table('akun_pengguna')->where('username', '1234567890')->first();
        $this->assertNotNull($restoredAccount);
        $this->assertSame('siswa', $restoredAccount->peran);
        $this->assertSame('student-hash', $restoredAccount->password);
        $this->assertDatabaseHas('akun_notifikasi', [
            'id' => 1,
            'pengguna_id' => $restoredAccount->id,
        ]);
        $this->assertDatabaseHas('akun_perangkat_login', [
            'id' => 1,
            'pengguna_id' => $restoredAccount->id,
        ]);
        $this->assertSame(
            Pengguna::class,
            DB::table('personal_access_tokens')->where('id', 1)->value('tokenable_type')
        );
        $this->assertSame(
            $restoredAccount->id,
            DB::table('personal_access_tokens')->where('id', 1)->value('tokenable_id')
        );
        $this->assertFalse(Schema::hasTable('akun_siswa'));
        $this->assertFalse(Schema::hasColumn('akun_notifikasi', 'akun_siswa_id'));
    }
}
