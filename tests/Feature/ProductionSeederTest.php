<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tests\TestCase;

#[RunTestsInSeparateProcesses]
class ProductionSeederTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (['sekolah_identitas', 'akun_pengguna', 'guru_biodata', 'siswa_biodata'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('sekolah_identitas', function (Blueprint $table): void {
            $table->unsignedTinyInteger('id')->primary();
            $table->string('nama');
            $table->string('npsn')->nullable();
            $table->string('alamat')->nullable();
            $table->timestamps();
        });

        Schema::create('akun_pengguna', function (Blueprint $table): void {
            $table->id();
            $table->string('peran');
            $table->string('username')->unique();
            $table->string('email')->nullable()->unique();
            $table->string('password');
            $table->boolean('wajib_ganti_password')->default(false);
            $table->boolean('aktif')->default(true);
            $table->timestamp('email_diverifikasi_at')->nullable();
            $table->timestamps();
        });

        Schema::create('guru_biodata', function (Blueprint $table): void {
            $table->id();
            $table->string('nama');
            $table->string('nip')->nullable();
        });

        Schema::create('siswa_biodata', function (Blueprint $table): void {
            $table->id();
            $table->string('nama_lengkap');
        });
    }

    protected function tearDown(): void
    {
        foreach (['sekolah_identitas', 'akun_pengguna', 'guru_biodata', 'siswa_biodata'] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_database_seeder_creates_only_production_identity_and_initial_admin_in_production(): void
    {
        $this->app['env'] = 'production';
        config([
            'app.env' => 'production',
            'auth.admin_initial_username' => 'admin-pertama',
            'auth.admin_initial_email' => 'admin@example.test',
            'auth.admin_initial_password' => 'StrongTestPassword-2026',
        ]);

        app(DatabaseSeeder::class)->run();

        $this->assertDatabaseHas('sekolah_identitas', [
            'id' => 1,
            'nama' => 'SMA Negeri 1 Rejoso Nganjuk',
        ]);
        $this->assertDatabaseCount('akun_pengguna', 1);
        $admin = DB::table('akun_pengguna')->first();
        $this->assertSame('admin', $admin->peran);
        $this->assertSame('admin-pertama', $admin->username);
        $this->assertTrue(Hash::check('StrongTestPassword-2026', $admin->password));
        $this->assertTrue((bool) $admin->wajib_ganti_password);
        $this->assertDatabaseCount('guru_biodata', 0);
        $this->assertDatabaseCount('siswa_biodata', 0);
    }
}
