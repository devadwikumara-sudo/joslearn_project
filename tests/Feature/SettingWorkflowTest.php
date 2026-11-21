<?php

namespace Tests\Feature;

use App\Models\Admin\CatatanCadangan;
use App\Models\Auth\Pengguna;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SettingWorkflowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchema();
    }

    public function test_saving_settings_sets_one_active_year_and_saves_school_identity_semester_and_kktp(): void
    {
        DB::table('sekolah_tahun_ajaran')->insert([
            ['id' => 1, 'nama' => '2024/2025', 'sedang_berjalan' => true],
            ['id' => 2, 'nama' => '2025/2026', 'sedang_berjalan' => false],
        ]);

        $response = $this->actingAs($this->user('admin', 1))->put(route('admin.setting.update'), [
            'nama' => 'SMA Contoh Negeri',
            'npsn' => '12345678',
            'alamat' => 'Jalan Pendidikan No. 10',
            'desa' => 'Desa Contoh',
            'kecamatan' => 'Kecamatan Contoh',
            'kabupaten' => 'Kabupaten Contoh',
            'provinsi' => 'Jawa Timur',
            'nama_kepala_sekolah' => 'Kepala Sekolah Uji',
            'nip_kepala_sekolah' => '198001012010011001',
            'tahun_ajaran_aktif_id' => 2,
            'semester_berjalan' => 'Genap',
            'kktp' => '78.5',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('sekolah_tahun_ajaran', [
            'id' => 1,
            'sedang_berjalan' => false,
        ]);
        $this->assertDatabaseHas('sekolah_tahun_ajaran', [
            'id' => 2,
            'sedang_berjalan' => true,
        ]);
        $this->assertSame(
            1,
            DB::table('sekolah_tahun_ajaran')->where('sedang_berjalan', true)->count()
        );
        $this->assertDatabaseHas('sekolah_identitas', [
            'nama' => 'SMA Contoh Negeri',
            'npsn' => '12345678',
            'alamat' => 'Jalan Pendidikan No. 10',
            'desa' => 'Desa Contoh',
            'kecamatan' => 'Kecamatan Contoh',
            'kabupaten' => 'Kabupaten Contoh',
            'provinsi' => 'Jawa Timur',
            'nama_kepala_sekolah' => 'Kepala Sekolah Uji',
            'nip_kepala_sekolah' => '198001012010011001',
        ]);
        $this->assertDatabaseHas('sekolah_pengaturan', [
            'kunci' => 'semester_berjalan',
            'isi' => 'Genap',
        ]);
        $this->assertDatabaseHas('sekolah_pengaturan', [
            'kunci' => 'kktp',
            'isi' => '78.5',
        ]);
        $this->assertDatabaseHas('catatan_aktivitas', [
            'aksi' => 'update_setting',
            'objek_tipe' => 'setting',
        ]);
    }

    #[DataProvider('settingTabs')]
    public function test_admin_setting_renders_only_the_requested_tab(string $tab, string $visibleMarker, string $hiddenMarker): void
    {
        $response = $this->actingAs($this->user('admin', 1))
            ->get(route('admin.setting', ['tab' => $tab]));

        $response->assertOk()
            ->assertViewHas('tab', $tab)
            ->assertSee($visibleMarker, false)
            ->assertDontSee($hiddenMarker, false);
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function settingTabs(): array
    {
        return [
            'profil' => ['profil', 'name="nama_kepala_sekolah"', 'name="backup_file"'],
            'tahun ajaran' => ['tahun-ajaran', 'name="nama_ta"', 'name="nama_kepala_sekolah"'],
            'cadangan' => ['cadangan', 'name="backup_file"', 'name="nama_ta"'],
            'tempat sampah' => ['sampah', 'Tempat sampah kosong.', 'name="backup_file"'],
            'log aktivitas' => ['log', 'placeholder="Cari aktivitas"', 'name="backup_file"'],
            'akun' => ['akun', 'Tambah Akun Pengguna', 'name="nama_kepala_sekolah"'],
        ];
    }

    public function test_admin_setting_rejects_an_unknown_tab(): void
    {
        $this->actingAs($this->user('admin', 1))
            ->get(route('admin.setting', ['tab' => 'unknown']))
            ->assertNotFound();
    }

    public function test_legacy_admin_pages_redirect_to_setting_tabs_and_keep_log_filters(): void
    {
        $this->actingAs($this->user('admin', 1))
            ->get(route('admin.log-aktivitas', ['lf' => 'admin', 'lq' => 'perubahan', 'page' => 2]))
            ->assertRedirect(route('admin.setting', [
                'tab' => 'log',
                'lf' => 'admin',
                'lq' => 'perubahan',
                'page' => 2,
            ]));

        $this->get(route('admin.sampah'))
            ->assertRedirect(route('admin.setting', ['tab' => 'sampah']));
    }

    public function test_log_tab_preserves_filters_and_paginates_activity_rows(): void
    {
        for ($index = 1; $index <= 26; $index++) {
            DB::table('catatan_aktivitas')->insert([
                'peran' => 'admin',
                'aksi' => 'ubah_setting',
                'keterangan' => 'Log cocok '.str_pad((string) $index, 2, '0', STR_PAD_LEFT),
                'created_at' => now()->addSeconds($index),
            ]);
        }

        $response = $this->actingAs($this->user('admin', 1))
            ->get(route('admin.setting', [
                'tab' => 'log',
                'lf' => 'admin',
                'lq' => 'cocok',
            ]));

        $response->assertOk()
            ->assertViewHas('lf', 'admin')
            ->assertViewHas('lq', 'cocok')
            ->assertViewHas('logs', fn ($logs): bool => $logs->total() === 26 && $logs->count() === 25)
            ->assertSee('Log cocok 26')
            ->assertDontSee('Log cocok 01')
            ->assertSee('page=2', false)
            ->assertSee('tab=log', false)
            ->assertSee('lf=admin', false)
            ->assertSee('lq=cocok', false);

        $response = $this->get(route('admin.log-aktivitas.export', ['lf' => 'admin', 'lq' => 'cocok']))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $workbook = IOFactory::load($response->baseResponse->getFile()->getPathname());
        $sheet = $workbook->getActiveSheet();

        $this->assertSame(['Waktu', 'Pengguna', 'Aktivitas'], $sheet->toArray()[0]);
        $this->assertSame('A1:C27', $sheet->getAutoFilter()->getRange());
        $this->assertSame('A2', $sheet->getFreezePane());
        $workbook->disconnectWorksheets();
    }

    public function test_year_tab_changes_the_active_year(): void
    {
        DB::table('sekolah_tahun_ajaran')->insert([
            ['id' => 1, 'nama' => '2024/2025', 'sedang_berjalan' => true],
            ['id' => 2, 'nama' => '2025/2026', 'sedang_berjalan' => false],
        ]);

        $this->actingAs($this->user('admin', 1))
            ->get(route('admin.setting', ['tab' => 'tahun-ajaran']))
            ->assertOk()
            ->assertSee('name="ta_id"', false)
            ->assertSee(route('admin.setting.tahun-ajaran.aktif'), false);

        $this->post(route('admin.setting.tahun-ajaran.aktif'), ['ta_id' => 2])
            ->assertRedirect();

        $this->assertDatabaseHas('sekolah_tahun_ajaran', ['id' => 1, 'sedang_berjalan' => false]);
        $this->assertDatabaseHas('sekolah_tahun_ajaran', ['id' => 2, 'sedang_berjalan' => true]);
    }

    public function test_trash_tab_lists_records_and_keeps_restore_and_permanent_delete_actions(): void
    {
        foreach ([
            ['siswa_biodata', 11, ['nama_lengkap' => 'Siswa Pulih', 'nisn' => '1234567890', 'nomor_induk' => '1001']],
            ['siswa_biodata', 12, ['nama_lengkap' => 'Siswa Hapus Permanen', 'nisn' => '1234567891', 'nomor_induk' => '1002']],
            ['guru_biodata', 21, ['nama' => 'Guru Pulih', 'nip' => '198001012010011001', 'nuptk' => '001']],
            ['guru_biodata', 22, ['nama' => 'Guru Hapus Permanen', 'nip' => '198001012010011002', 'nuptk' => '002']],
        ] as [$table, $id, $attributes]) {
            DB::table($table)->insert([
                'id' => $id,
                ...$attributes,
                'deleted_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->actingAs($this->user('admin', 1))
            ->get(route('admin.setting', ['tab' => 'sampah']))
            ->assertOk()
            ->assertSee('Siswa Pulih')
            ->assertSee('Guru Pulih')
            ->assertSee(route('admin.sampah.pulihkan'), false)
            ->assertSee(route('admin.sampah.hapus-permanen'), false);

        foreach ([['siswa', 11], ['guru', 21]] as [$type, $id]) {
            $this->post(route('admin.sampah.pulihkan'), ['tipe' => $type, 'id' => $id])
                ->assertRedirect();
        }

        foreach ([['siswa', 12], ['guru', 22]] as [$type, $id]) {
            $this->delete(route('admin.sampah.hapus-permanen'), ['tipe' => $type, 'id' => $id])
                ->assertRedirect();
        }

        $this->assertDatabaseHas('siswa_biodata', ['id' => 11, 'deleted_at' => null]);
        $this->assertDatabaseHas('guru_biodata', ['id' => 21, 'deleted_at' => null]);
        $this->assertDatabaseMissing('siswa_biodata', ['id' => 12]);
        $this->assertDatabaseMissing('guru_biodata', ['id' => 22]);
    }

    public function test_downloaded_backup_contains_core_data_without_passwords_or_tokens(): void
    {
        Storage::fake('local');
        $this->seedBackupData();
        DB::table('akun_pengguna')->insert([
            'id' => 1,
            'peran' => 'admin',
            'username' => 'admin-uji',
            'password' => 'password-hash-secret',
        ]);
        DB::table('personal_access_tokens')->insert([
            'id' => 1,
            'token' => 'access-token-secret',
        ]);

        $response = $this->actingAs($this->user('admin', 1))
            ->post(route('admin.setting.cadangan.unduh'));

        $backup = CatatanCadangan::query()->firstOrFail();
        $response->assertDownload($backup->nama_file);
        $this->assertDatabaseHas('catatan_cadangan', [
            'nama_file' => $backup->nama_file,
            'dibuat_oleh' => 1,
        ]);
        Storage::disk('local')->assertExists($backup->lokasi);

        $contents = Storage::disk('local')->get($backup->lokasi);
        $payload = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        $this->assertArrayHasKey('siswa_biodata', $payload['tables']);
        $this->assertArrayHasKey('siswa_nilai', $payload['tables']);
        $this->assertArrayHasKey('guru_biodata', $payload['tables']);
        $this->assertArrayNotHasKey('akun_pengguna', $payload['tables']);
        $this->assertArrayNotHasKey('personal_access_tokens', $payload['tables']);
        $this->assertStringNotContainsString('password-hash-secret', $contents);
        $this->assertStringNotContainsString('access-token-secret', $contents);
    }

    public function test_restoring_backup_replaces_changed_data_without_changing_login_credentials(): void
    {
        Storage::fake('local');
        $this->seedBackupData();
        DB::table('akun_pengguna')->insert([
            'id' => 1,
            'peran' => 'admin',
            'username' => 'admin-uji',
            'password' => 'password-hash-secret',
            'guru_id' => 51,
            'siswa_id' => 11,
        ]);

        $this->actingAs($this->user('admin', 1))
            ->post(route('admin.setting.cadangan.unduh'));
        $backup = CatatanCadangan::query()->firstOrFail();
        $contents = Storage::disk('local')->get($backup->lokasi);

        DB::table('siswa_biodata')->where('id', 11)->update(['nama_lengkap' => 'Nama Setelah Perubahan']);
        DB::table('siswa_biodata')->insert([
            'id' => 12,
            'nama_lengkap' => 'Siswa Tambahan',
            'nisn' => '1234567891',
        ]);
        DB::table('siswa_riwayat_kelas')->insert([
            'id' => 22,
            'siswa_id' => 12,
            'tahun_ajaran_id' => 2025,
            'kelas_id' => 41,
            'status' => 'Aktif',
        ]);
        DB::table('siswa_nilai')->delete();

        $response = $this->actingAs($this->user('admin', 1))
            ->post(route('admin.setting.cadangan.pulihkan'), [
                'backup_file' => UploadedFile::fake()->createWithContent('josbook.json', $contents),
                'konfirmasi' => 'PULIHKAN',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('siswa_biodata', [
            'id' => 11,
            'nama_lengkap' => 'Siswa Contoh',
        ]);
        $this->assertDatabaseMissing('siswa_biodata', ['id' => 12]);
        $this->assertDatabaseHas('siswa_nilai', [
            'id' => 31,
            'nilai_angka' => 88,
        ]);
        $this->assertDatabaseHas('akun_pengguna', [
            'id' => 1,
            'password' => 'password-hash-secret',
            'guru_id' => 51,
            'siswa_id' => 11,
        ]);
        $this->assertSame(2, CatatanCadangan::query()->count());
        $this->assertDatabaseHas('catatan_aktivitas', [
            'aksi' => 'pulihkan_cadangan',
            'objek_tipe' => 'cadangan',
        ]);
    }

    public function test_restore_rejects_an_invalid_backup_structure_without_mutating_data(): void
    {
        Storage::fake('local');
        $this->seedBackupData();

        $response = $this->actingAs($this->user('admin', 1))
            ->post(route('admin.setting.cadangan.pulihkan'), [
                'backup_file' => UploadedFile::fake()->createWithContent('invalid.json', '{"format":"unknown"}'),
                'konfirmasi' => 'PULIHKAN',
            ]);

        $response->assertSessionHasErrors('backup_file');
        $this->assertDatabaseHas('siswa_biodata', [
            'id' => 11,
            'nama_lengkap' => 'Siswa Contoh',
        ]);
        $this->assertSame(0, CatatanCadangan::query()->count());
    }

    public function test_year_deletion_requires_the_exact_confirmation_before_creating_a_backup(): void
    {
        Storage::fake('local');
        $this->seedBackupData();

        $response = $this->actingAs($this->user('admin', 1))
            ->delete(route('admin.setting.tahun-ajaran.hapus-data'), [
                'tahun_ajaran_id' => 2025,
                'konfirmasi' => 'HAPUS',
            ]);

        $response->assertSessionHasErrors('konfirmasi');
        $this->assertDatabaseHas('siswa_riwayat_kelas', ['id' => 21]);
        $this->assertSame(0, CatatanCadangan::query()->count());
    }

    public function test_year_deletion_creates_a_backup_before_removing_academic_records(): void
    {
        Storage::fake('local');
        $this->seedBackupData();

        $response = $this->actingAs($this->user('admin', 1))
            ->delete(route('admin.setting.tahun-ajaran.hapus-data'), [
                'tahun_ajaran_id' => 2025,
                'konfirmasi' => 'HAPUS 2025/2026',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('sekolah_tahun_ajaran', ['id' => 2025, 'nama' => '2025/2026']);
        $this->assertDatabaseMissing('siswa_riwayat_kelas', ['id' => 21]);
        $this->assertDatabaseMissing('siswa_nilai', ['id' => 31]);
        $this->assertDatabaseMissing('siswa_kehadiran', ['id' => 32]);
        $this->assertDatabaseMissing('siswa_ekskul', ['id' => 33]);

        $backup = CatatanCadangan::query()->firstOrFail();
        Storage::disk('local')->assertExists($backup->lokasi);
        $payload = json_decode(Storage::disk('local')->get($backup->lokasi), true, 512, JSON_THROW_ON_ERROR);
        $this->assertCount(1, $payload['tables']['siswa_riwayat_kelas']);
        $this->assertDatabaseHas('catatan_aktivitas', [
            'aksi' => 'hapus_data_tahun_ajaran',
            'objek_id' => 2025,
        ]);
    }

    public function test_non_admin_cannot_download_a_backup(): void
    {
        Storage::fake('local');

        $response = $this->actingAs($this->user('guru', 2))
            ->post(route('admin.setting.cadangan.unduh'));

        $response->assertForbidden();
        $this->assertSame(0, CatatanCadangan::query()->count());
    }

    private function user(string $role, int $id): Pengguna
    {
        $user = new Pengguna([
            'peran' => $role,
            'username' => $role.'-uji',
            'aktif' => true,
        ]);
        $user->setAttribute('id', $id);

        return $user;
    }

    private function seedBackupData(): void
    {
        DB::table('sekolah_identitas')->insert([
            'id' => 1,
            'nama' => 'SMA Contoh Negeri',
            'npsn' => '12345678',
            'alamat' => 'Jalan Pendidikan',
            'desa' => 'Desa Contoh',
            'kecamatan' => 'Kecamatan Contoh',
            'kabupaten' => 'Kabupaten Contoh',
            'provinsi' => 'Jawa Timur',
        ]);
        DB::table('sekolah_pengaturan')->insert([
            ['kunci' => 'semester_berjalan', 'isi' => 'Ganjil'],
            ['kunci' => 'kktp', 'isi' => '75'],
        ]);
        DB::table('sekolah_tahun_ajaran')->insert([
            ['id' => 2025, 'nama' => '2025/2026', 'sedang_berjalan' => true],
        ]);
        DB::table('sekolah_kelas')->insert([
            ['id' => 41, 'nama' => 'X-1', 'tingkat' => 'X'],
        ]);
        DB::table('sekolah_mapel')->insert([
            ['id' => 61, 'kode' => 'mtk', 'nama' => 'Matematika'],
        ]);
        DB::table('sekolah_mapel_per_tingkat')->insert([
            ['id' => 71, 'tingkat' => 'X', 'mapel_id' => 61, 'kelompok_mapel' => 'Wajib'],
        ]);
        DB::table('guru_biodata')->insert([
            'id' => 51,
            'nama' => 'Guru Contoh',
            'nip' => '198001012010011001',
        ]);
        DB::table('guru_wali_kelas')->insert([
            'id' => 52,
            'tahun_ajaran_id' => 2025,
            'kelas_id' => 41,
            'guru_id' => 51,
        ]);
        DB::table('guru_mengajar')->insert([
            'id' => 53,
            'tahun_ajaran_id' => 2025,
            'guru_id' => 51,
            'kelas_id' => 41,
            'mapel_id' => 61,
        ]);
        DB::table('siswa_biodata')->insert([
            'id' => 11,
            'nama_lengkap' => 'Siswa Contoh',
            'nisn' => '1234567890',
            'nomor_induk' => '1001',
        ]);
        DB::table('siswa_riwayat_kelas')->insert([
            'id' => 21,
            'siswa_id' => 11,
            'tahun_ajaran_id' => 2025,
            'kelas_id' => 41,
            'status' => 'Aktif',
        ]);
        DB::table('siswa_nilai')->insert([
            'id' => 31,
            'riwayat_kelas_id' => 21,
            'semester' => 'Ganjil',
            'mapel_id' => 61,
            'nilai_angka' => 88,
        ]);
        DB::table('siswa_kehadiran')->insert([
            'id' => 32,
            'riwayat_kelas_id' => 21,
            'semester' => 'Ganjil',
            'sakit' => 1,
            'izin' => 0,
            'alpa' => 0,
        ]);
        DB::table('siswa_ekskul')->insert([
            'id' => 33,
            'riwayat_kelas_id' => 21,
            'semester' => 'Ganjil',
            'nama_kegiatan' => 'Pramuka',
            'predikat' => 'Baik',
        ]);
    }

    private function createSchema(): void
    {
        $tableNames = [
            'sekolah_identitas',
            'sekolah_pengaturan',
            'sekolah_tahun_ajaran',
            'sekolah_kelas',
            'sekolah_mapel',
            'sekolah_mapel_per_tingkat',
            'catatan_tempat_sampah',
            'guru_biodata',
            'guru_wali_kelas',
            'guru_mengajar',
            'siswa_biodata',
            'siswa_berkas',
            'siswa_riwayat_kelas',
            'siswa_riwayat_mutasi',
            'siswa_nilai',
            'siswa_kehadiran',
            'siswa_ekskul',
            'siswa_pelacakan_alumni',
            'catatan_kenaikan_kelas',
            'import_format_tersimpan',
            'import_kamus_kolom',
            'import_kamus_mapel',
            'catatan_cadangan',
            'catatan_aktivitas',
            'akun_pengguna',
            'akun_siswa',
            'personal_access_tokens',
        ];

        foreach (array_reverse($tableNames) as $tableName) {
            Schema::dropIfExists($tableName);
        }

        $columnsByTable = [
            'sekolah_identitas' => [
                'nama' => 'string', 'npsn' => 'string', 'alamat' => 'string', 'desa' => 'string',
                'kecamatan' => 'string', 'kabupaten' => 'string', 'provinsi' => 'string',
                'nama_kepala_sekolah' => 'string', 'nip_kepala_sekolah' => 'string',
            ],
            'sekolah_pengaturan' => ['kunci' => 'string', 'isi' => 'string'],
            'sekolah_tahun_ajaran' => ['nama' => 'string', 'sedang_berjalan' => 'boolean'],
            'sekolah_kelas' => ['nama' => 'string', 'tingkat' => 'string'],
            'sekolah_mapel' => ['kode' => 'string', 'nama' => 'string'],
            'sekolah_mapel_per_tingkat' => [
                'tingkat' => 'string', 'mapel_id' => 'integer', 'kelompok_mapel' => 'string',
            ],
            'catatan_tempat_sampah' => [
                'keterangan' => 'string', 'jenis' => 'string', 'dihapus_oleh' => 'integer',
                'dipulihkan_at' => 'timestamp',
            ],
            'guru_biodata' => [
                'nama' => 'string', 'nip' => 'string', 'nuptk' => 'string',
                'tempat_sampah_id' => 'integer', 'deleted_at' => 'timestamp',
            ],
            'guru_wali_kelas' => [
                'tahun_ajaran_id' => 'integer', 'kelas_id' => 'integer', 'guru_id' => 'integer',
            ],
            'guru_mengajar' => [
                'tahun_ajaran_id' => 'integer', 'guru_id' => 'integer', 'kelas_id' => 'integer',
                'mapel_id' => 'integer',
            ],
            'siswa_biodata' => [
                'nama_lengkap' => 'string', 'nisn' => 'string', 'nomor_induk' => 'string',
                'tempat_sampah_id' => 'integer', 'deleted_at' => 'timestamp',
            ],
            'siswa_berkas' => [
                'siswa_id' => 'integer', 'jenis' => 'string', 'nama_file' => 'string', 'lokasi' => 'string',
            ],
            'siswa_riwayat_kelas' => [
                'siswa_id' => 'integer', 'tahun_ajaran_id' => 'integer', 'kelas_id' => 'integer',
                'status' => 'string', 'tempat_sampah_id' => 'integer', 'deleted_at' => 'timestamp',
            ],
            'siswa_riwayat_mutasi' => [
                'siswa_id' => 'integer', 'riwayat_kelas_id' => 'integer',
                'dicatat_oleh' => 'integer', 'kelas_asal_id' => 'integer', 'kelas_tujuan_id' => 'integer',
            ],
            'siswa_nilai' => [
                'riwayat_kelas_id' => 'integer', 'semester' => 'string',
                'mapel_id' => 'integer', 'nilai_angka' => 'decimal',
            ],
            'siswa_kehadiran' => [
                'riwayat_kelas_id' => 'integer', 'semester' => 'string',
                'sakit' => 'integer', 'izin' => 'integer', 'alpa' => 'integer', 'kokurikuler' => 'text',
            ],
            'siswa_ekskul' => [
                'riwayat_kelas_id' => 'integer', 'semester' => 'string',
                'nama_kegiatan' => 'string', 'predikat' => 'string',
            ],
            'siswa_pelacakan_alumni' => [
                'siswa_id' => 'integer', 'jenis' => 'string', 'nama_tempat' => 'string',
                'keterangan' => 'string', 'tahun' => 'string',
            ],
            'catatan_kenaikan_kelas' => [
                'pengguna_id' => 'integer', 'kelas_asal_id' => 'integer',
                'tahun_ajaran_asal_id' => 'integer', 'tahun_ajaran_tujuan_id' => 'integer',
                'riwayat_baru' => 'text', 'data_sebelumnya' => 'text',
            ],
            'import_format_tersimpan' => [
                'nama' => 'string', 'jenis' => 'string', 'kode_struktur' => 'string',
                'pemetaan_kolom' => 'text', 'dibuat_oleh' => 'integer',
            ],
            'import_kamus_kolom' => [
                'jenis' => 'string', 'judul_di_excel' => 'string', 'kolom_tujuan' => 'string',
                'dibuat_oleh' => 'integer',
            ],
            'import_kamus_mapel' => [
                'mapel_id' => 'integer', 'nama_di_excel' => 'string',
            ],
            'catatan_cadangan' => [
                'nama_file' => 'string', 'lokasi' => 'string', 'ukuran_byte' => 'integer',
                'dibuat_oleh' => 'integer',
            ],
            'catatan_aktivitas' => [
                'pengguna_id' => 'integer', 'peran' => 'string', 'aksi' => 'string',
                'keterangan' => 'string', 'objek_tipe' => 'string', 'objek_id' => 'integer',
                'alamat_ip' => 'string',
            ],
            'akun_siswa' => [
                'username' => 'string', 'email' => 'string', 'siswa_id' => 'integer',
            ],
        ];

        foreach ($columnsByTable as $tableName => $columns) {
            Schema::create($tableName, function (Blueprint $table) use ($columns, $tableName): void {
                $table->id();

                foreach ($columns as $name => $type) {
                    $column = match ($type) {
                        'integer' => $table->unsignedBigInteger($name),
                        'boolean' => $table->boolean($name),
                        'decimal' => $table->decimal($name, 5, 2),
                        'text' => $table->text($name),
                        'timestamp' => $table->timestamp($name),
                        default => $table->string($name),
                    };
                    $column->nullable();
                }

                if ($tableName === 'sekolah_pengaturan') {
                    $table->unique('kunci');
                }

                if ($tableName !== 'catatan_cadangan') {
                    $table->timestamps();
                } else {
                    $table->timestamp('created_at')->nullable();
                }
            });
        }

        Schema::create('akun_pengguna', function (Blueprint $table): void {
            $table->id();
            $table->string('peran');
            $table->string('username');
            $table->string('password');
            $table->unsignedBigInteger('guru_id')->nullable();
            $table->unsignedBigInteger('siswa_id')->nullable();
        });

        Schema::create('personal_access_tokens', function (Blueprint $table): void {
            $table->id();
            $table->string('token')->nullable();
        });
    }
}
