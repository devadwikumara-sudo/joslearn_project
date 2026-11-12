<?php

namespace Tests\Feature;

use App\Models\Auth\Pengguna;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tests\TestCase;

#[RunTestsInSeparateProcesses]
class HakAksesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('akun_pengguna');
        Schema::create('akun_pengguna', function (Blueprint $table): void {
            $table->id();
            $table->string('peran');
            $table->string('username')->unique();
            $table->string('email')->nullable();
            $table->string('password');
            $table->boolean('aktif')->default(true);
            $table->unsignedBigInteger('guru_id')->nullable();
            $table->unsignedBigInteger('siswa_id')->nullable();
            $table->boolean('wajib_ganti_password')->default(false);
            $table->rememberToken();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('akun_pengguna');

        parent::tearDown();
    }

    public function test_student_cannot_enter_either_web_portal(): void
    {
        $student = $this->createAccount('siswa');

        $this->actingAs($student)->get('/admin/dashboard')
            ->assertRedirect(route('login'));
        $this->actingAs($student)->get('/guru/beranda')
            ->assertRedirect(route('login'));
    }

    public function test_teacher_cannot_open_administrator_functions(): void
    {
        $teacher = $this->createAccount('guru');
        $adminRoutes = [
            ['get', '/admin/setting'],
            ['get', '/admin/data-guru'],
            ['post', '/admin/data-guru'],
            ['delete', '/admin/data-guru/1'],
            ['get', '/admin/import/siswa'],
            ['get', '/admin/import/guru'],
            ['post', '/admin/setting/cadangan/unduh'],
        ];

        foreach ($adminRoutes as [$method, $uri]) {
            $this->actingAs($teacher)->call($method, $uri)->assertForbidden();
        }
    }

    private function createAccount(string $role): Pengguna
    {
        return Pengguna::query()->create([
            'peran' => $role,
            'username' => $role.'-'.uniqid(),
            'email' => $role.'@example.test',
            'password' => 'test-password',
            'aktif' => true,
            'wajib_ganti_password' => false,
        ]);
    }
}
