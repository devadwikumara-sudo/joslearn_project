<?php

namespace Tests\Feature;

use App\Models\Admin\CatatanCadangan;
use App\Models\Auth\Pengguna;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
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
