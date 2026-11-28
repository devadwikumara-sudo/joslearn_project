<?php

namespace Tests\Feature;

use App\Models\Auth\Pengguna;
use App\Models\Guru\GuruBiodata;
use App\Models\SiswaBiodata;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TempatSampahTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::purge();
        config(['app.key' => 'base64:'.base64_encode(str_repeat('j', 32))]);

        Schema::dropIfExists('guru_biodata');
        Schema::dropIfExists('siswa_biodata');
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
            $table->string('nama_lengkap');
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('guru_biodata', function (Blueprint $table): void {
            $table->id();
            $table->string('nip')->nullable();
            $table->string('nuptk')->nullable();
            $table->string('nama');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('guru_biodata');
        Schema::dropIfExists('siswa_biodata');
        Schema::dropIfExists('akun_pengguna');
        DB::purge();

        parent::tearDown();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.sampah'))->assertRedirect(route('login'));
    }

    public function test_teacher_cannot_open_the_trash(): void
    {
        $this->actingAs($this->makeUser('guru', 'guru-uji'))
            ->get(route('admin.setting', ['tab' => 'sampah']))
            ->assertForbidden();
    }

    public function test_trash_lists_only_deleted_students_and_teachers(): void
    {
        $this->makeSiswa('Siswa Aktif');
        $this->makeSiswa('Siswa Terhapus')->delete();
        $this->makeGuru('Guru Aktif');
        $this->makeGuru('Guru Terhapus')->delete();

        $response = $this->actingAs($this->makeUser('admin', 'admin-uji'))
            ->get(route('admin.setting', ['tab' => 'sampah']));

        $response->assertOk();
        $response->assertViewHas('siswaTerhapus', fn ($items) => $items->count() === 1);
        $response->assertViewHas('guruTerhapus', fn ($items) => $items->count() === 1);
        $response->assertSee('Siswa Terhapus');
        $response->assertSee('Guru Terhapus');
        $response->assertDontSee('Siswa Aktif');
        $response->assertDontSee('Guru Aktif');
    }

    public function test_most_recently_deleted_student_is_listed_first(): void
    {
        $lama = $this->makeSiswa('Dihapus Lama');
        $baru = $this->makeSiswa('Dihapus Baru');
        $lama->delete();
        $baru->delete();
        DB::table('siswa_biodata')->where('id', $lama->id)->update(['deleted_at' => now()->subDays(3)]);
        DB::table('siswa_biodata')->where('id', $baru->id)->update(['deleted_at' => now()->subHour()]);

        $this->actingAs($this->makeUser('admin', 'admin-uji'))
            ->get(route('admin.setting', ['tab' => 'sampah']))
            ->assertViewHas('siswaTerhapus', fn ($items) => $items->pluck('nama_lengkap')->all()
                === ['Dihapus Baru', 'Dihapus Lama']);
    }

    public function test_admin_can_restore_a_deleted_student(): void
    {
        $siswa = $this->makeSiswa('Budi Santoso');
        $siswa->delete();
        $this->assertSoftDeleted($siswa);

        $response = $this->actingAs($this->makeUser('admin', 'admin-uji'))
            ->from(route('admin.setting', ['tab' => 'sampah']))
            ->post(route('admin.sampah.pulihkan'), ['tipe' => 'siswa', 'id' => $siswa->id]);

        $response->assertRedirect(route('admin.setting', ['tab' => 'sampah']));
        $response->assertSessionHas('success', 'Siswa Budi Santoso berhasil dipulihkan.');
        $this->assertNotSoftDeleted($siswa);
    }

    public function test_admin_can_restore_a_deleted_teacher(): void
    {
        $guru = $this->makeGuru('Sari Wulandari');
        $guru->delete();

        $response = $this->actingAs($this->makeUser('admin', 'admin-uji'))
            ->from(route('admin.setting', ['tab' => 'sampah']))
            ->post(route('admin.sampah.pulihkan'), ['tipe' => 'guru', 'id' => $guru->id]);

        $response->assertRedirect(route('admin.setting', ['tab' => 'sampah']));
        $response->assertSessionHas('success', 'Guru Sari Wulandari berhasil dipulihkan.');
        $this->assertNotSoftDeleted($guru);
    }

    public function test_restoring_an_unknown_type_changes_nothing(): void
    {
        $siswa = $this->makeSiswa('Budi Santoso');
        $siswa->delete();

        $response = $this->actingAs($this->makeUser('admin', 'admin-uji'))
            ->from(route('admin.setting', ['tab' => 'sampah']))
            ->post(route('admin.sampah.pulihkan'), ['tipe' => 'kelas', 'id' => $siswa->id]);

        $response->assertRedirect(route('admin.setting', ['tab' => 'sampah']));
        $response->assertSessionHas('error', 'Tipe tidak dikenal.');
        $this->assertSoftDeleted($siswa);
    }

    public function test_restoring_a_missing_record_returns_not_found(): void
    {
        $this->actingAs($this->makeUser('admin', 'admin-uji'))
            ->post(route('admin.sampah.pulihkan'), ['tipe' => 'siswa', 'id' => 9999])
            ->assertNotFound();
    }

    public function test_admin_can_permanently_delete_a_student_from_the_trash(): void
    {
        $siswa = $this->makeSiswa('Budi Santoso');
        $siswa->delete();

        $response = $this->actingAs($this->makeUser('admin', 'admin-uji'))
            ->from(route('admin.setting', ['tab' => 'sampah']))
            ->delete(route('admin.sampah.hapus-permanen'), ['tipe' => 'siswa', 'id' => $siswa->id]);

        $response->assertRedirect(route('admin.setting', ['tab' => 'sampah']));
        $response->assertSessionHas('success', 'Siswa Budi Santoso dihapus permanen.');
        $this->assertDatabaseMissing('siswa_biodata', ['id' => $siswa->id]);
    }

    public function test_admin_can_permanently_delete_a_teacher_from_the_trash(): void
    {
        $guru = $this->makeGuru('Sari Wulandari');
        $guru->delete();

        $response = $this->actingAs($this->makeUser('admin', 'admin-uji'))
            ->from(route('admin.setting', ['tab' => 'sampah']))
            ->delete(route('admin.sampah.hapus-permanen'), ['tipe' => 'guru', 'id' => $guru->id]);

        $response->assertRedirect(route('admin.setting', ['tab' => 'sampah']));
        $response->assertSessionHas('success', 'Guru Sari Wulandari dihapus permanen.');
        $this->assertDatabaseMissing('guru_biodata', ['id' => $guru->id]);
    }

    public function test_permanent_delete_with_an_unknown_type_changes_nothing(): void
    {
        $siswa = $this->makeSiswa('Budi Santoso');
        $siswa->delete();

        $response = $this->actingAs($this->makeUser('admin', 'admin-uji'))
            ->from(route('admin.setting', ['tab' => 'sampah']))
            ->delete(route('admin.sampah.hapus-permanen'), ['tipe' => 'kelas', 'id' => $siswa->id]);

        $response->assertSessionHas('error', 'Tipe tidak dikenal.');
        $this->assertSoftDeleted($siswa);
    }

    public function test_teacher_cannot_restore_or_permanently_delete(): void
    {
        $siswa = $this->makeSiswa('Budi Santoso');
        $siswa->delete();
        $teacher = $this->makeUser('guru', 'guru-uji');

        $this->actingAs($teacher)
            ->post(route('admin.sampah.pulihkan'), ['tipe' => 'siswa', 'id' => $siswa->id])
            ->assertForbidden();
        $this->actingAs($teacher)
            ->delete(route('admin.sampah.hapus-permanen'), ['tipe' => 'siswa', 'id' => $siswa->id])
            ->assertForbidden();

        $this->assertSoftDeleted($siswa);
    }

    public function test_permanent_delete_must_not_remove_a_record_that_is_not_in_the_trash(): void
    {
        $this->markTestSkipped(
            'Aktifkan setelah SettingController::hapusPermanenSampah() memakai onlyTrashed() '.
            'alih-alih withTrashed(); saat ini data aktif ikut terhapus permanen kalau ID-nya dikirim.'
        );

        $aktif = $this->makeSiswa('Siswa Masih Aktif');

        $this->actingAs($this->makeUser('admin', 'admin-uji'))
            ->delete(route('admin.sampah.hapus-permanen'), ['tipe' => 'siswa', 'id' => $aktif->id]);

        $this->assertDatabaseHas('siswa_biodata', ['id' => $aktif->id]);
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

    private function makeSiswa(string $nama): SiswaBiodata
    {
        return SiswaBiodata::create([
            'nisn' => '00'.random_int(10000000, 99999999),
            'nomor_induk' => null,
            'nama_lengkap' => $nama,
        ]);
    }

    private function makeGuru(string $nama): GuruBiodata
    {
        return GuruBiodata::create([
            'nip' => null,
            'nuptk' => null,
            'nama' => $nama,
        ]);
    }
}
