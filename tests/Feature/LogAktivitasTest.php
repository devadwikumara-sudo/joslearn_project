<?php

namespace Tests\Feature;

use App\Models\Admin\CatatanAktivitas;
use App\Models\Auth\Pengguna;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\TestResponse;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class LogAktivitasTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::purge();
        config(['app.key' => 'base64:'.base64_encode(str_repeat('j', 32))]);

        Schema::dropIfExists('catatan_aktivitas');
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

    protected function tearDown(): void
    {
        Schema::dropIfExists('catatan_aktivitas');
        Schema::dropIfExists('akun_pengguna');
        DB::purge();

        parent::tearDown();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.log-aktivitas'))->assertRedirect(route('login'));
    }

    public function test_teacher_cannot_open_or_export_the_activity_log(): void
    {
        $teacher = $this->makeUser('guru', 'guru-uji');

        $this->actingAs($teacher)
            ->get(route('admin.setting', ['tab' => 'log']))
            ->assertForbidden();
        $this->actingAs($teacher)->get(route('admin.log-aktivitas.export'))->assertForbidden();
    }

    public function test_admin_sees_the_logs_and_the_total_count(): void
    {
        $admin = $this->makeUser('admin', 'admin-uji');
        $this->makeLog(['keterangan' => 'Menambah siswa Budi']);
        $this->makeLog(['keterangan' => 'Menghapus guru Sari']);
        $this->makeLog(['keterangan' => 'Mengubah pengaturan sekolah']);

        $response = $this->actingAs($admin)->get(route('admin.setting', ['tab' => 'log']));

        $response->assertOk();
        $response->assertViewHas('totalLogs', 3);
        $response->assertSee('Menambah siswa Budi');
        $response->assertSee('Menghapus guru Sari');
    }

    public function test_newest_log_is_listed_first(): void
    {
        $admin = $this->makeUser('admin', 'admin-uji');
        $this->makeLog(['keterangan' => 'Catatan pertama']);
        $this->makeLog(['keterangan' => 'Catatan kedua']);

        $this->actingAs($admin)
            ->get(route('admin.setting', ['tab' => 'log']))
            ->assertOk()
            ->assertSeeInOrder(['Catatan kedua', 'Catatan pertama']);
    }

    public function test_description_falls_back_to_the_action_when_it_is_empty(): void
    {
        $admin = $this->makeUser('admin', 'admin-uji');
        $this->makeLog(['aksi' => 'login_berhasil', 'keterangan' => null]);

        $this->actingAs($admin)
            ->get(route('admin.setting', ['tab' => 'log']))
            ->assertOk()
            ->assertSee('login_berhasil');
    }

    public function test_logs_can_be_filtered_by_role(): void
    {
        $admin = $this->makeUser('admin', 'admin-uji');
        $this->makeLog(['peran' => 'admin', 'keterangan' => 'Aksi dari admin']);
        $this->makeLog(['peran' => 'guru', 'keterangan' => 'Aksi dari guru']);

        $response = $this->actingAs($admin)
            ->get(route('admin.setting', ['tab' => 'log', 'lf' => 'guru']));

        $response->assertOk();
        $response->assertViewHas('lf', 'guru');
        $response->assertViewHas('logs', fn ($logs) => $logs->total() === 1
            && $logs->first()->keterangan === 'Aksi dari guru');
        $response->assertDontSee('Aksi dari admin');
    }

    public function test_logs_can_be_filtered_by_username(): void
    {
        $admin = $this->makeUser('admin', 'admin-uji');
        $teacher = $this->makeUser('guru', 'bu-sari');
        $this->makeLog(['pengguna_id' => $admin->id, 'peran' => 'admin', 'keterangan' => 'Aksi admin uji']);
        $this->makeLog(['pengguna_id' => $teacher->id, 'peran' => 'guru', 'keterangan' => 'Aksi bu sari']);

        $response = $this->actingAs($admin)
            ->get(route('admin.setting', ['tab' => 'log', 'lf' => 'bu-sari']));

        $response->assertViewHas('logs', fn ($logs) => $logs->total() === 1
            && $logs->first()->keterangan === 'Aksi bu sari');
    }

    public function test_search_matches_the_action_and_the_description(): void
    {
        $admin = $this->makeUser('admin', 'admin-uji');
        $this->makeLog(['aksi' => 'import_nilai', 'keterangan' => 'Memuat berkas nilai kelas X']);
        $this->makeLog(['aksi' => 'hapus_siswa', 'keterangan' => 'Menghapus satu siswa']);

        $this->actingAs($admin)
            ->get(route('admin.setting', ['tab' => 'log', 'lq' => 'import']))
            ->assertViewHas('logs', fn ($logs) => $logs->total() === 1
                && $logs->first()->aksi === 'import_nilai');

        $this->actingAs($admin)
            ->get(route('admin.setting', ['tab' => 'log', 'lq' => 'Menghapus']))
            ->assertViewHas('logs', fn ($logs) => $logs->total() === 1
                && $logs->first()->aksi === 'hapus_siswa');
    }

    public function test_search_without_a_match_shows_the_empty_message(): void
    {
        $admin = $this->makeUser('admin', 'admin-uji');
        $this->makeLog(['keterangan' => 'Menambah siswa']);

        $this->actingAs($admin)
            ->get(route('admin.setting', ['tab' => 'log', 'lq' => 'tidak-ada-yang-cocok']))
            ->assertOk()
            ->assertViewHas('logs', fn ($logs) => $logs->total() === 0)
            ->assertSee('Belum ada catatan aktivitas yang cocok dengan kriteria pencarian.');
    }

    public function test_logs_are_paginated_by_twenty_five(): void
    {
        $admin = $this->makeUser('admin', 'admin-uji');
        for ($i = 1; $i <= 30; $i++) {
            $this->makeLog(['keterangan' => "Catatan nomor {$i}"]);
        }

        $this->actingAs($admin)
            ->get(route('admin.setting', ['tab' => 'log']))
            ->assertViewHas('logs', fn ($logs) => $logs->count() === 25 && $logs->total() === 30);

        $this->actingAs($admin)
            ->get(route('admin.setting', ['tab' => 'log', 'page' => 2]))
            ->assertViewHas('logs', fn ($logs) => $logs->count() === 5);
    }

    public function test_export_returns_an_xlsx_file_with_a_header_row(): void
    {
        $admin = $this->makeUser('admin', 'admin-uji');
        $this->makeLog(['pengguna_id' => $admin->id, 'peran' => 'admin', 'keterangan' => 'Mengubah pengaturan']);

        $response = $this->actingAs($admin)->get(route('admin.log-aktivitas.export'));

        $response->assertOk();
        $this->assertStringContainsString(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $response->headers->get('Content-Type')
        );
        $this->assertStringContainsString(
            'attachment; filename=log-aktivitas-',
            $response->headers->get('Content-Disposition')
        );

        $rows = $this->xlsxRows($response);
        $this->assertSame(['Waktu', 'Pengguna', 'Aktivitas'], $rows[0]);
        $this->assertSame(['admin-uji', 'Mengubah pengaturan'], array_slice($rows[1], 1));
    }

    public function test_export_names_the_actor_and_falls_back_to_role_then_system(): void
    {
        $admin = $this->makeUser('admin', 'admin-uji');
        $this->makeLog(['pengguna_id' => $admin->id, 'peran' => 'admin', 'keterangan' => 'Mengubah pengaturan']);
        $this->makeLog(['pengguna_id' => null, 'peran' => 'guru', 'aksi' => 'login_guru', 'keterangan' => null]);
        $this->makeLog(['pengguna_id' => null, 'peran' => null, 'aksi' => 'cadangan_otomatis', 'keterangan' => null]);

        $rows = $this->xlsxRows($this->actingAs($admin)->get(route('admin.log-aktivitas.export')));

        $this->assertSame(['Waktu', 'Pengguna', 'Aktivitas'], $rows[0]);
        $this->assertSame([
            ['Sistem', 'cadangan_otomatis'],
            ['guru', 'login_guru'],
            ['admin-uji', 'Mengubah pengaturan'],
        ], array_map(
            fn (array $row): array => [$row[1], $row[2]],
            array_slice($rows, 1)
        ));
    }

    public function test_export_respects_the_role_and_search_filters(): void
    {
        $admin = $this->makeUser('admin', 'admin-uji');
        $this->makeLog(['peran' => 'admin', 'keterangan' => 'Aksi admin']);
        $this->makeLog(['peran' => 'guru', 'keterangan' => 'Input nilai matematika']);
        $this->makeLog(['peran' => 'guru', 'keterangan' => 'Input kehadiran']);

        $byRole = $this->xlsxRows(
            $this->actingAs($admin)->get(route('admin.log-aktivitas.export', ['lf' => 'guru']))
        );
        $this->assertCount(3, $byRole);
        $this->assertSame(['Waktu', 'Pengguna', 'Aktivitas'], $byRole[0]);

        $bySearch = $this->xlsxRows(
            $this->actingAs($admin)->get(route('admin.log-aktivitas.export', ['lf' => 'guru', 'lq' => 'nilai']))
        );
        $this->assertCount(2, $bySearch);
        $this->assertSame(['Input nilai matematika'], array_slice($bySearch[1], 2));
    }

    /**
     * Baca isi XLSX hasil ekspor menjadi baris-baris, termasuk baris judul.
     *
     * @return array<int, array<int, string|null>>
     */
    private function xlsxRows(TestResponse $response): array
    {
        $response->assertOk();
        $spreadsheet = IOFactory::load($response->baseResponse->getFile()->getPathname());
        $rows = $spreadsheet->getActiveSheet()->toArray();
        $spreadsheet->disconnectWorksheets();

        return array_map('array_values', $rows);
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

    private function makeLog(array $attributes = []): CatatanAktivitas
    {
        return CatatanAktivitas::create(array_merge([
            'aksi' => 'aksi_uji',
            'keterangan' => 'Keterangan uji',
        ], $attributes));
    }
}
