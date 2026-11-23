<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\AlumniController;
use App\Http\Controllers\Admin\DataSiswaController;
use App\Models\Auth\Pengguna;
use App\Models\SiswaBiodata;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tests\TestCase;

#[RunTestsInSeparateProcesses]
class TeacherClassPromotionTest extends TestCase
{
    private const TABLES = [
        'catatan_aktivitas',
        'catatan_kenaikan_kelas',
        'siswa_berkas',
        'siswa_nilai',
        'siswa_kehadiran',
        'siswa_ekskul',
        'siswa_riwayat_kelas',
        'siswa_biodata',
        'siswa_pelacakan_alumni',
        'guru_wali_kelas',
        'guru_mengajar',
        'sekolah_mapel',
        'guru_biodata',
        'sekolah_kelas',
        'sekolah_tahun_ajaran',
        'akun_pengguna',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        foreach (self::TABLES as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('akun_pengguna', function (Blueprint $table): void {
            $table->id();
            $table->string('peran');
            $table->string('username')->unique();
            $table->string('email')->nullable();
            $table->timestamp('email_diverifikasi_at')->nullable();
            $table->string('password');
            $table->boolean('aktif')->default(true);
            $table->unsignedBigInteger('guru_id')->nullable();
            $table->unsignedBigInteger('siswa_id')->nullable();
            $table->boolean('wajib_ganti_password')->default(false);
            $table->string('remember_token')->nullable();
            $table->timestamps();
        });

        Schema::create('sekolah_tahun_ajaran', function (Blueprint $table): void {
            $table->id();
            $table->string('nama', 9)->unique();
            $table->boolean('sedang_berjalan')->default(false);
            $table->timestamps();
        });

        Schema::create('sekolah_kelas', function (Blueprint $table): void {
            $table->id();
            $table->string('nama')->unique();
            $table->string('tingkat');
            $table->timestamps();
        });

        Schema::create('sekolah_mapel', function (Blueprint $table): void {
            $table->id();
            $table->string('kode')->unique();
            $table->string('nama');
            $table->string('tingkat')->nullable();
            $table->timestamps();
        });

        Schema::create('guru_biodata', function (Blueprint $table): void {
            $table->id();
            $table->string('nama');
            $table->string('nip')->nullable();
            $table->string('nuptk')->nullable();
            $table->string('nik')->nullable();
            $table->string('jenis_kelamin')->nullable();
            $table->string('tempat_lahir')->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->string('pendidikan_terakhir')->nullable();
            $table->string('status_kepegawaian')->nullable();
            $table->unsignedSmallInteger('mulai_bertugas')->nullable();
            $table->string('nomor_hp')->nullable();
            $table->string('email')->nullable();
            $table->string('alamat')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('guru_wali_kelas', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tahun_ajaran_id');
            $table->unsignedBigInteger('kelas_id');
            $table->unsignedBigInteger('guru_id');
            $table->timestamps();
            $table->unique(['tahun_ajaran_id', 'kelas_id']);
            $table->unique(['tahun_ajaran_id', 'guru_id']);
        });

        Schema::create('guru_mengajar', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tahun_ajaran_id');
            $table->unsignedBigInteger('guru_id');
            $table->unsignedBigInteger('kelas_id');
            $table->unsignedBigInteger('mapel_id');
            $table->timestamp('created_at')->nullable();
            $table->unique(['tahun_ajaran_id', 'kelas_id', 'mapel_id']);
        });

        Schema::create('siswa_biodata', function (Blueprint $table): void {
            $table->id();
            $table->string('nama_lengkap');
            $table->string('nisn')->nullable();
            $table->string('tahun_lulus', 4)->nullable();
            $table->string('alasan_keluar')->nullable();
            $table->string('kelas_keluar')->nullable();
            $table->string('sekolah_tujuan')->nullable();
            $table->string('jenis_registrasi')->nullable();
            $table->string('sekolah_asal')->nullable();
            $table->string('pindahan_dari_sekolah')->nullable();
            $table->date('tanggal_keluar')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('siswa_riwayat_kelas', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('siswa_id');
            $table->unsignedBigInteger('tahun_ajaran_id');
            $table->unsignedBigInteger('kelas_id');
            $table->string('status')->default('Aktif');
            $table->string('keputusan_kenaikan')->nullable();
            $table->unsignedBigInteger('kelas_tujuan_id')->nullable();
            $table->date('tanggal_keputusan_kenaikan')->nullable();
            $table->unsignedBigInteger('wali_guru_id')->nullable();
            $table->string('nama_wali_kelas')->nullable();
            $table->string('nip_wali_kelas')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('siswa_berkas', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('siswa_id');
        });
        Schema::create('siswa_pelacakan_alumni', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('siswa_id');
            $table->string('jenis');
            $table->string('nama_tempat')->nullable();
            $table->string('keterangan')->nullable();
            $table->string('tahun', 4)->nullable();
            $table->timestamps();
        });
        Schema::create('siswa_nilai', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('riwayat_kelas_id');
            $table->unsignedBigInteger('mapel_id');
        });
        Schema::create('siswa_kehadiran', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('riwayat_kelas_id');
        });
        Schema::create('siswa_ekskul', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('riwayat_kelas_id');
        });

        Schema::create('catatan_kenaikan_kelas', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('pengguna_id')->nullable();
            $table->unsignedBigInteger('kelas_asal_id');
            $table->unsignedBigInteger('tahun_ajaran_asal_id');
            $table->unsignedBigInteger('tahun_ajaran_tujuan_id')->nullable();
            $table->unsignedSmallInteger('jumlah_siswa')->default(0);
            $table->text('riwayat_baru')->nullable();
            $table->text('data_sebelumnya')->nullable();
            $table->timestamp('dibatalkan_at')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('catatan_aktivitas', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('pengguna_id')->nullable();
            $table->string('peran', 10)->nullable();
            $table->string('aksi');
            $table->string('keterangan', 500)->nullable();
            $table->string('objek_tipe')->nullable();
            $table->unsignedBigInteger('objek_id')->nullable();
            $table->string('alamat_ip', 45)->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    protected function tearDown(): void
    {
        foreach (self::TABLES as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_wali_can_promote_and_cancel_only_their_class_with_destination_homeroom_snapshot(): void
    {
        [$year, $nextYear, $sourceClass, $destinationClass, $teacher, $student] = $this->seedPromotionData();
        $targetHomeroom = $this->createTeacher('Guru Tujuan', '1987654321');
        DB::table('guru_wali_kelas')->insert([
            'tahun_ajaran_id' => $nextYear,
            'kelas_id' => $destinationClass,
            'guru_id' => $targetHomeroom,
        ]);
        $user = $this->createUser('guru', 'guru-x1', $teacher);

        $this->actingAs($user)->post(route('guru.kenaikan-kelas.proses'), [
            'ta_aktif' => '2024/2025',
            'ta_berikutnya' => '2030/2031',
            'kelas_asal' => 'X-1',
            'keputusan' => [$student => 'Naik'],
            'kelas_tujuan' => [$student => 'XI-1'],
        ])->assertRedirect(route('guru.kenaikan-mutasi', [
            'tab' => 'kenaikan',
            'tahun_ajaran' => $year,
            'kelas' => 'X-1',
        ]).'#riwayat');

        $this->assertDatabaseHas('siswa_riwayat_kelas', [
            'siswa_id' => $student,
            'tahun_ajaran_id' => $nextYear,
            'kelas_id' => $destinationClass,
            'wali_guru_id' => $targetHomeroom,
            'nama_wali_kelas' => 'Guru Tujuan',
            'nip_wali_kelas' => '1987654321',
        ]);
        $this->assertDatabaseHas('siswa_riwayat_kelas', [
            'siswa_id' => $student,
            'tahun_ajaran_id' => $year,
            'keputusan_kenaikan' => 'NAIK',
            'kelas_tujuan_id' => $destinationClass,
        ]);
        $this->assertDatabaseHas('catatan_aktivitas', ['aksi' => 'kenaikan_kelas']);
        $this->assertDatabaseHas('siswa_riwayat_kelas', [
            'siswa_id' => $student,
            'tahun_ajaran_id' => $year,
            'kelas_id' => $sourceClass,
        ]);
        $this->actingAs($user)
            ->get(route('guru.data-siswa.wali'))
            ->assertOk()
            ->assertViewHas('filters', fn (array $filters): bool => $filters['tahun_ajaran'] === $nextYear)
            ->assertViewHas('siswa', fn ($students): bool => $students->contains(
                fn (SiswaBiodata $record): bool => $record->id === $student
                    && $record->riwayatKelas->first()?->kelas?->nama === 'XI-1'
            ));
        $this->actingAs($user)
            ->get(route('guru.data-siswa.wali', ['tahun_ajaran' => $nextYear]))
            ->assertOk()
            ->assertViewHas('siswa', fn ($students): bool => $students->contains(
                fn (SiswaBiodata $record): bool => $record->id === $student
                    && $record->riwayatKelas->first()?->kelas?->nama === 'XI-1'
            ));
        $this->actingAs($user)
            ->get(route('guru.data-siswa.wali'))
            ->assertOk()
            ->assertViewHas('filters', fn (array $filters): bool => $filters['tahun_ajaran'] === $nextYear)
            ->assertViewHas('siswa', fn ($students): bool => $students->contains(
                fn (SiswaBiodata $record): bool => $record->id === $student
                    && $record->riwayatKelas->first()?->kelas?->nama === 'XI-1'
            ));

        $batchId = DB::table('catatan_kenaikan_kelas')->value('id');
        $pageUrl = route('guru.kenaikan-mutasi', [
            'tab' => 'kenaikan',
            'tahun_ajaran' => $year,
            'kelas' => 'X-1',
        ]);
        $this->actingAs($user)->get($pageUrl)
            ->assertOk()
            ->assertDontSee('name="keputusan['.$student.']"', false)
            ->assertViewHas('riwayatKenaikanSiswa', fn ($histories): bool => $histories->contains(
                fn (array $history): bool => $history['nama_siswa'] === 'Siswa Uji'
                    && $history['kelas_tujuan'] === 'XI-1'
            ));

        $this->from($pageUrl)->actingAs($user)
            ->post(route('guru.kenaikan-kelas.batalkan', $batchId))
            ->assertRedirect($pageUrl);

        $this->assertTrue(DB::table('siswa_riwayat_kelas')
            ->where('siswa_id', $student)
            ->where('tahun_ajaran_id', $nextYear)
            ->whereNotNull('deleted_at')
            ->exists());
        $this->assertDatabaseHas('siswa_riwayat_kelas', [
            'siswa_id' => $student,
            'tahun_ajaran_id' => $year,
            'keputusan_kenaikan' => null,
        ]);
        $this->assertDatabaseHas('catatan_aktivitas', ['aksi' => 'batalkan_kenaikan']);
        $this->actingAs($user)->get($pageUrl)
            ->assertOk()
            ->assertSee('name="keputusan['.$student.']"', false)
            ->assertViewHas('riwayatKenaikanSiswa', fn ($histories): bool => $histories->isEmpty());
        $this->actingAs($user)->get(route('guru.data-siswa.wali'))
            ->assertOk()
            ->assertViewHas('filters', fn (array $filters): bool => $filters['tahun_ajaran'] === $year)
            ->assertViewHas('siswa', fn ($students): bool => $students->contains(
                fn (SiswaBiodata $record): bool => $record->id === $student
                    && $record->riwayatKelas->first()?->kelas?->nama === 'X-1'
            ));
    }

    public function test_admin_promotion_stays_on_kenaikan_page_and_records_student_in_history(): void
    {
        [$year, $nextYear, $sourceClass, $destinationClass, , $student] = $this->seedPromotionData();
        DB::table('sekolah_kelas')->where('id', $destinationClass)->update(['nama' => 'XI-6']);
        DB::table('sekolah_kelas')->insert([
            'nama' => 'X-2',
            'tingkat' => 'X',
        ]);
        $admin = $this->createUser('admin', 'admin-promosi', null);

        $this->actingAs($admin)->post(route('admin.kenaikan-kelas.proses'), [
            'ta_aktif' => '2025/2026',
            'ta_berikutnya' => '2026/2027',
            'kelas_asal' => 'X-1',
            'keputusan' => [$student => 'Naik'],
            'kelas_tujuan' => [$student => 'XI-6'],
        ])->assertRedirect(route('admin.kenaikan-mutasi', [
            'tab' => 'kenaikan',
            'tahun_ajaran' => $year,
            'kelas' => 'X-1',
        ]).'#riwayat');

        $this->actingAs($admin)
            ->get(route('admin.data-siswa'))
            ->assertOk()
            ->assertViewHas('tahunAjaranTerpilihId', fn (int|string $yearId): bool => (int) $yearId === $nextYear)
            ->assertViewHas('siswaList', fn ($students): bool => $students->contains(
                fn (SiswaBiodata $record): bool => $record->id === $student
                    && $record->riwayatKelas->first()?->kelas?->nama === 'XI-6'
            ));

        $this->assertDatabaseHas('siswa_riwayat_kelas', [
            'siswa_id' => $student,
            'tahun_ajaran_id' => $year,
            'kelas_id' => $sourceClass,
            'keputusan_kenaikan' => 'NAIK',
        ]);
        $this->actingAs($admin)
            ->get(route('admin.kenaikan-mutasi', [
                'tab' => 'kenaikan',
                'tahun_ajaran' => $year,
                'kelas' => 'X-1',
            ]))
            ->assertOk()
            ->assertSee('Cari per kelas')
            ->assertSee('Riwayat Kenaikan Kelas')
            ->assertDontSee('Riwayat Mutasi Siswa')
            ->assertViewHas('riwayatKenaikanSiswa', fn ($histories): bool => $histories->contains(
                fn (array $history): bool => $history['nama_siswa'] === 'Siswa Uji'
                    && $history['keputusan'] === 'Naik'
                    && $history['kelas_tujuan'] === 'XI-6'
                    && $history['tahun_tujuan'] === '2026/2027'
            ));
        $this->actingAs($admin)
            ->get(route('admin.kenaikan-mutasi', [
                'tab' => 'kenaikan',
                'tahun_ajaran' => $year,
                'kelas' => 'XI-6',
            ]))
            ->assertOk()
            ->assertViewHas('riwayatKenaikanSiswa', fn ($histories): bool => $histories->contains(
                fn (array $history): bool => $history['nama_siswa'] === 'Siswa Uji'
                    && $history['kelas_tujuan'] === 'XI-6'
            ))
            ->assertSee(route('admin.data-siswa', [
                'tahun_ajaran' => $nextYear,
                'kelas' => 'XI-6',
            ]));
        $this->actingAs($admin)
            ->get(route('admin.kenaikan-mutasi', [
                'tab' => 'mutasi',
                'tahun_ajaran' => $year,
                'kelas' => 'X-1',
            ]))
            ->assertOk()
            ->assertSee('Riwayat Mutasi Siswa')
            ->assertDontSee('Riwayat Kenaikan Kelas Terakhir')
            ->assertDontSee('Rincian Riwayat Kenaikan Kelas');
        $this->actingAs($admin)
            ->get(route('admin.kenaikan-mutasi', [
                'tab' => 'kenaikan',
                'tahun_ajaran' => $year,
                'kelas' => 'X-2',
            ]))
            ->assertOk()
            ->assertViewHas('riwayatKenaikanSiswa', fn ($histories): bool => $histories->isEmpty());
        $this->actingAs($admin)
            ->get(route('admin.data-siswa', ['tahun_ajaran' => $nextYear]))
            ->assertOk()
            ->assertViewHas('siswaList', fn ($students): bool => $students->contains(
                fn (SiswaBiodata $record): bool => $record->id === $student
                    && $record->riwayatKelas->first()?->kelas?->nama === 'XI-6'
            ));
        $this->actingAs($admin)
            ->get(route('admin.data-siswa'))
            ->assertOk()
            ->assertViewHas('tahunAjaranTerpilihId', fn (int|string $yearId): bool => (int) $yearId === $nextYear)
            ->assertViewHas('siswaList', fn ($students): bool => $students->contains(
                fn (SiswaBiodata $record): bool => $record->id === $student
                    && $record->riwayatKelas->first()?->kelas?->nama === 'XI-6'
            ));
        $this->assertDatabaseHas('siswa_riwayat_kelas', [
            'siswa_id' => $student,
            'tahun_ajaran_id' => $nextYear,
            'kelas_id' => $destinationClass,
            'status' => 'Aktif',
        ]);
    }

    public function test_admin_can_delete_one_promotion_history_entry_and_restore_only_that_student(): void
    {
        [$year, $nextYear, $sourceClass, $destinationClass, , $firstStudent] = $this->seedPromotionData();
        $secondStudent = DB::table('siswa_biodata')->insertGetId([
            'nama_lengkap' => 'Siswa Uji Kedua',
            'nisn' => '0000000003',
        ]);
        DB::table('siswa_riwayat_kelas')->insert([
            'siswa_id' => $secondStudent,
            'tahun_ajaran_id' => $year,
            'kelas_id' => $sourceClass,
            'status' => 'Aktif',
        ]);
        $previousTargetClass = DB::table('sekolah_kelas')->insertGetId([
            'nama' => 'XII-9',
            'tingkat' => 'XII',
        ]);
        $secondTargetHistoryId = DB::table('siswa_riwayat_kelas')->insertGetId([
            'siswa_id' => $secondStudent,
            'tahun_ajaran_id' => $nextYear,
            'kelas_id' => $previousTargetClass,
            'status' => 'Keluar',
        ]);
        $admin = $this->createUser('admin', 'admin-hapus-riwayat', null);

        $this->actingAs($admin)->post(route('admin.kenaikan-kelas.proses'), [
            'ta_aktif' => '2025/2026',
            'ta_berikutnya' => '2026/2027',
            'kelas_asal' => 'X-1',
            'keputusan' => [
                $firstStudent => 'Naik',
                $secondStudent => 'Naik',
            ],
            'kelas_tujuan' => [
                $firstStudent => 'XI-1',
                $secondStudent => 'XI-1',
            ],
        ])->assertRedirect(route('admin.kenaikan-mutasi', [
            'tab' => 'kenaikan',
            'tahun_ajaran' => $year,
            'kelas' => 'X-1',
        ]).'#riwayat');

        $batchId = (int) DB::table('catatan_kenaikan_kelas')->value('id');
        $firstTargetHistoryId = (int) DB::table('siswa_riwayat_kelas')
            ->where('siswa_id', $firstStudent)
            ->where('tahun_ajaran_id', $nextYear)
            ->value('id');
        $historyUrl = route('admin.kenaikan-mutasi', [
            'tab' => 'kenaikan',
            'tahun_ajaran' => $year,
            'kelas' => 'XI-1',
        ]);

        $this->actingAs($admin)->get($historyUrl)
            ->assertOk()
            ->assertSee('Hapus')
            ->assertViewHas('riwayatKenaikanSiswa', fn ($histories): bool => $histories->count() === 2);

        $this->actingAs($admin)
            ->delete(route('admin.kenaikan-kelas.siswa.batalkan', [$batchId, $firstStudent]))
            ->assertRedirect(route('admin.kenaikan-mutasi', [
                'tab' => 'kenaikan',
                'tahun_ajaran' => $year,
                'kelas' => 'X-1',
            ]).'#riwayat');

        $this->assertDatabaseHas('siswa_riwayat_kelas', [
            'siswa_id' => $firstStudent,
            'tahun_ajaran_id' => $year,
            'kelas_id' => $sourceClass,
            'status' => 'Aktif',
            'keputusan_kenaikan' => null,
        ]);
        $this->assertTrue(DB::table('siswa_riwayat_kelas')
            ->where('id', $firstTargetHistoryId)
            ->whereNotNull('deleted_at')
            ->exists());
        $this->assertDatabaseHas('siswa_riwayat_kelas', [
            'siswa_id' => $secondStudent,
            'tahun_ajaran_id' => $year,
            'keputusan_kenaikan' => 'NAIK',
        ]);
        $this->assertDatabaseHas('siswa_riwayat_kelas', [
            'id' => $secondTargetHistoryId,
            'kelas_id' => $destinationClass,
            'status' => 'Aktif',
            'deleted_at' => null,
        ]);
        $this->assertDatabaseHas('catatan_kenaikan_kelas', [
            'id' => $batchId,
            'jumlah_siswa' => 1,
            'dibatalkan_at' => null,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.kenaikan-kelas.siswa.batalkan', [$batchId, $secondStudent]))
            ->assertRedirect(route('admin.kenaikan-mutasi', [
                'tab' => 'kenaikan',
                'tahun_ajaran' => $year,
                'kelas' => 'X-1',
            ]).'#riwayat');

        $this->assertDatabaseHas('siswa_riwayat_kelas', [
            'siswa_id' => $secondStudent,
            'tahun_ajaran_id' => $year,
            'kelas_id' => $sourceClass,
            'status' => 'Aktif',
            'keputusan_kenaikan' => null,
        ]);
        $this->assertDatabaseHas('siswa_riwayat_kelas', [
            'id' => $secondTargetHistoryId,
            'kelas_id' => $previousTargetClass,
            'status' => 'Keluar',
            'deleted_at' => null,
        ]);
        $this->assertDatabaseHas('catatan_kenaikan_kelas', [
            'id' => $batchId,
            'jumlah_siswa' => 0,
            'riwayat_baru' => '[]',
            'data_sebelumnya' => '[]',
        ]);
        $this->assertTrue(DB::table('catatan_kenaikan_kelas')
            ->where('id', $batchId)
            ->whereNotNull('dibatalkan_at')
            ->exists());
        $this->assertDatabaseCount('catatan_aktivitas', 3);
        $this->assertDatabaseHas('catatan_aktivitas', ['aksi' => 'batalkan_kenaikan_siswa']);
        $this->actingAs($admin)->get($historyUrl)
            ->assertOk()
            ->assertViewHas('riwayatKenaikanSiswa', fn ($histories): bool => $histories->isEmpty());
        $this->actingAs($admin)->get(route('admin.data-siswa'))
            ->assertOk()
            ->assertViewHas('tahunAjaranTerpilihId', fn (int|string $yearId): bool => (int) $yearId === $year)
            ->assertViewHas('siswaList', fn ($students): bool => $students->contains(
                fn (SiswaBiodata $record): bool => in_array($record->id, [$firstStudent, $secondStudent], true)
                    && $record->riwayatKelas->first()?->kelas?->nama === 'X-1'
            ));
    }

    public function test_teacher_cannot_delete_promotion_history_for_an_unassigned_class(): void
    {
        [$year, $nextYear, , , , $student] = $this->seedPromotionData();
        $admin = $this->createUser('admin', 'admin-buat-batch', null);

        $this->actingAs($admin)->post(route('admin.kenaikan-kelas.proses'), [
            'ta_aktif' => '2025/2026',
            'ta_berikutnya' => '2026/2027',
            'kelas_asal' => 'X-1',
            'keputusan' => [$student => 'Naik'],
            'kelas_tujuan' => [$student => 'XI-1'],
        ])->assertRedirect(route('admin.kenaikan-mutasi', [
            'tab' => 'kenaikan',
            'tahun_ajaran' => $year,
            'kelas' => 'X-1',
        ]).'#riwayat');

        $batchId = (int) DB::table('catatan_kenaikan_kelas')->value('id');
        $unassignedTeacher = $this->createTeacher('Guru Tidak Ditugaskan', '9876543210');
        $user = $this->createUser('guru', 'guru-tidak-ditugaskan', $unassignedTeacher);

        $this->actingAs($user)
            ->delete(route('guru.kenaikan-kelas.siswa.batalkan', [$batchId, $student]))
            ->assertForbidden();

        $this->assertDatabaseHas('siswa_riwayat_kelas', [
            'siswa_id' => $student,
            'tahun_ajaran_id' => $year,
            'keputusan_kenaikan' => 'NAIK',
        ]);
        $this->assertDatabaseHas('siswa_riwayat_kelas', [
            'siswa_id' => $student,
            'tahun_ajaran_id' => $nextYear,
            'deleted_at' => null,
        ]);
        $this->assertDatabaseHas('catatan_kenaikan_kelas', [
            'id' => $batchId,
            'jumlah_siswa' => 1,
            'dibatalkan_at' => null,
        ]);
        $this->assertDatabaseMissing('catatan_aktivitas', ['aksi' => 'batalkan_kenaikan_siswa']);
    }

    public function test_final_class_graduation_is_excluded_from_the_active_student_scope(): void
    {
        [$year, , $sourceClass, , $teacher, $student] = $this->seedPromotionData();
        DB::table('sekolah_kelas')->where('id', $sourceClass)->update([
            'nama' => 'XII-1',
            'tingkat' => 'XII',
        ]);
        $user = $this->createUser('guru', 'guru-xii', $teacher);

        $this->actingAs($user)->post(route('guru.kenaikan-kelas.proses'), [
            'kelas_asal' => 'XII-1',
            'keputusan' => [$student => 'Lulus'],
        ])->assertRedirect(route('guru.kenaikan-mutasi', [
            'tab' => 'kenaikan',
            'tahun_ajaran' => $year,
            'kelas' => 'XII-1',
        ]).'#riwayat');

        $this->assertDatabaseHas('siswa_riwayat_kelas', [
            'siswa_id' => $student,
            'tahun_ajaran_id' => $year,
            'status' => 'Lulus',
        ]);
        $this->assertDatabaseHas('siswa_biodata', [
            'id' => $student,
            'tahun_lulus' => '2026',
            'alasan_keluar' => 'Lulus',
        ]);
        $this->assertFalse(SiswaBiodata::aktif()->whereKey($student)->exists());

        $dataSiswa = app(DataSiswaController::class)->index(Request::create('/admin/data-siswa'));
        $alumni = app(AlumniController::class)->index(Request::create('/admin/alumni'));

        $this->assertSame(0, $dataSiswa->getData()['siswaList']->total());
        $this->assertSame([$student], $alumni->getData()['alumni']->getCollection()->pluck('id')->all());
    }

    public function test_graduation_is_rejected_before_grade_twelve(): void
    {
        [$year, , , , $teacher, $student] = $this->seedPromotionData();
        $user = $this->createUser('guru', 'guru-x1', $teacher);
        $pageUrl = route('guru.kenaikan-mutasi', [
            'tab' => 'kenaikan',
            'tahun_ajaran' => $year,
            'kelas' => 'X-1',
        ]);

        $this->from($pageUrl)->actingAs($user)
            ->post(route('guru.kenaikan-kelas.proses'), [
                'kelas_asal' => 'X-1',
                'keputusan' => [$student => 'Lulus'],
            ])
            ->assertRedirect($pageUrl)
            ->assertSessionHasErrors(["keputusan.{$student}"]);

        $this->assertDatabaseHas('siswa_riwayat_kelas', [
            'siswa_id' => $student,
            'tahun_ajaran_id' => $year,
            'status' => 'Aktif',
        ]);
        $this->assertDatabaseMissing('catatan_kenaikan_kelas', ['kelas_asal_id' => 1]);
        $this->assertDatabaseMissing('catatan_aktivitas', ['aksi' => 'kenaikan_kelas']);
    }

    public function test_grade_twelve_cannot_be_promoted(): void
    {
        [$year, , $sourceClass, , $teacher, $student] = $this->seedPromotionData();
        DB::table('sekolah_kelas')->where('id', $sourceClass)->update([
            'nama' => 'XII-1',
            'tingkat' => 'XII',
        ]);
        $user = $this->createUser('guru', 'guru-xii', $teacher);
        $pageUrl = route('guru.kenaikan-mutasi', [
            'tab' => 'kenaikan',
            'tahun_ajaran' => $year,
            'kelas' => 'XII-1',
        ]);

        $this->from($pageUrl)->actingAs($user)
            ->post(route('guru.kenaikan-kelas.proses'), [
                'kelas_asal' => 'XII-1',
                'keputusan' => [$student => 'Naik'],
                'kelas_tujuan' => [$student => 'XI-1'],
            ])
            ->assertRedirect($pageUrl)
            ->assertSessionHasErrors(["keputusan.{$student}"]);

        $this->assertDatabaseHas('siswa_riwayat_kelas', [
            'siswa_id' => $student,
            'tahun_ajaran_id' => $year,
            'status' => 'Aktif',
        ]);
        $this->assertDatabaseMissing('catatan_aktivitas', ['aksi' => 'kenaikan_kelas']);
    }

    public function test_promotion_requires_a_valid_next_grade_destination(): void
    {
        [$year, , , , , $student] = $this->seedPromotionData();
        DB::table('sekolah_kelas')->insert([
            ['nama' => 'XII-1', 'tingkat' => 'XII'],
        ]);
        $admin = $this->createUser('admin', 'admin', null);
        $pageUrl = route('admin.kenaikan-mutasi', [
            'tab' => 'kenaikan',
            'tahun_ajaran' => $year,
            'kelas' => 'X-1',
        ]);
        $route = route('admin.kenaikan-kelas.proses');
        $basePayload = [
            'kelas_asal' => 'X-1',
            'keputusan' => [$student => 'Naik'],
        ];

        $this->from($pageUrl)->actingAs($admin)
            ->post($route, $basePayload)
            ->assertRedirect($pageUrl)
            ->assertSessionHasErrors(["kelas_tujuan.{$student}"]);

        $this->from($pageUrl)->actingAs($admin)
            ->post($route, $basePayload + ['kelas_tujuan' => [$student => 'XII-1']])
            ->assertRedirect($pageUrl)
            ->assertSessionHasErrors(["kelas_tujuan.{$student}"]);

        $this->from($pageUrl)->actingAs($admin)
            ->post($route, $basePayload + ['kelas_tujuan' => [$student => 'kelas-bebas']])
            ->assertRedirect($pageUrl)
            ->assertSessionHasErrors(["kelas_tujuan.{$student}"]);

        $this->assertDatabaseMissing('sekolah_kelas', ['nama' => 'kelas-bebas']);
        $this->assertDatabaseHas('siswa_riwayat_kelas', [
            'siswa_id' => $student,
            'tahun_ajaran_id' => $year,
            'status' => 'Aktif',
        ]);
        $this->assertDatabaseMissing('catatan_aktivitas', ['aksi' => 'kenaikan_kelas']);
    }

    public function test_promotion_validation_errors_are_displayed_after_redirect(): void
    {
        [$year, , , , , $student] = $this->seedPromotionData();
        $admin = $this->createUser('admin', 'admin', null);
        $pageUrl = route('admin.kenaikan-mutasi', [
            'tab' => 'kenaikan',
            'tahun_ajaran' => $year,
            'kelas' => 'X-1',
        ]);

        $this->from($pageUrl)->actingAs($admin)->followingRedirects()
            ->post(route('admin.kenaikan-kelas.proses'), [
                'kelas_asal' => 'X-1',
                'keputusan' => [$student => 'Naik'],
            ])
            ->assertOk()
            ->assertSee('Periksa kembali data yang dikirim:')
            ->assertSee('Kelas tujuan wajib');
    }

    public function test_cancelling_graduation_restores_the_student_to_active_data(): void
    {
        [$year, , $sourceClass, , $teacher, $student] = $this->seedPromotionData();
        DB::table('sekolah_kelas')->where('id', $sourceClass)->update([
            'nama' => 'XII-1',
            'tingkat' => 'XII',
        ]);
        $user = $this->createUser('guru', 'guru-xii', $teacher);
        $pageUrl = route('guru.kenaikan-mutasi', [
            'tab' => 'kenaikan',
            'tahun_ajaran' => $year,
            'kelas' => 'XII-1',
        ]);

        $this->from($pageUrl)->actingAs($user)
            ->post(route('guru.kenaikan-kelas.proses'), [
                'kelas_asal' => 'XII-1',
                'keputusan' => [$student => 'Lulus'],
            ])
            ->assertRedirect($pageUrl.'#riwayat');

        $batchId = DB::table('catatan_kenaikan_kelas')->value('id');
        $this->assertNotNull($batchId);
        $this->assertFalse(SiswaBiodata::aktif()->whereKey($student)->exists());

        $this->from($pageUrl)->actingAs($user)
            ->post(route('guru.kenaikan-kelas.batalkan', $batchId))
            ->assertRedirect($pageUrl);

        $this->assertDatabaseHas('siswa_riwayat_kelas', [
            'siswa_id' => $student,
            'tahun_ajaran_id' => $year,
            'status' => 'Aktif',
            'keputusan_kenaikan' => null,
        ]);
        $this->assertDatabaseHas('siswa_biodata', [
            'id' => $student,
            'tahun_lulus' => null,
            'alasan_keluar' => null,
            'kelas_keluar' => null,
            'tanggal_keluar' => null,
        ]);
        $this->assertTrue(SiswaBiodata::aktif()->whereKey($student)->exists());
        $this->assertDatabaseHas('catatan_aktivitas', ['aksi' => 'batalkan_kenaikan']);

        $alumni = app(AlumniController::class)->index(Request::create('/admin/alumni'));
        $this->assertFalse($alumni->getData()['alumni']->getCollection()->contains('id', $student));
    }

    public function test_promotion_form_offers_only_classes_in_the_next_grade(): void
    {
        [$year, , , , , $student] = $this->seedPromotionData();
        DB::table('sekolah_kelas')->insert([
            ['nama' => 'XI-2', 'tingkat' => 'XI'],
            ['nama' => 'XII-2', 'tingkat' => 'XII'],
        ]);
        $admin = $this->createUser('admin', 'admin', null);

        $response = $this->actingAs($admin)->get(route('admin.kenaikan-mutasi', [
            'tahun_ajaran' => $year,
            'kelas' => 'X-1',
        ]));
        $html = (string) $response->assertOk()->getContent();
        preg_match('/<select name="kelas_tujuan\['.$student.'\]"[\s\S]*?<\/select>/', $html, $matches);

        $this->assertNotEmpty($matches);
        $this->assertStringContainsString('value="XI-1"', $matches[0]);
        $this->assertStringContainsString('value="XI-2"', $matches[0]);
        $this->assertStringNotContainsString('value="X-1"', $matches[0]);
        $this->assertStringNotContainsString('value="XII-1"', $matches[0]);
    }

    public function test_promotion_form_preserves_choices_and_shows_mass_actions_for_the_selected_grade(): void
    {
        [$year, , , , , $student] = $this->seedPromotionData();
        DB::table('sekolah_kelas')->insert([
            ['nama' => 'XI-2', 'tingkat' => 'XI'],
        ]);
        $admin = $this->createUser('admin', 'admin', null);
        $pageUrl = route('admin.kenaikan-mutasi', [
            'tahun_ajaran' => $year,
            'kelas' => 'X-1',
        ]);

        $response = $this->withSession([
            '_old_input' => [
                'keputusan' => [$student => 'Tinggal'],
                'kelas_tujuan' => [$student => 'XI-2'],
            ],
        ])->actingAs($admin)->get($pageUrl);
        $html = (string) $response->assertOk()->getContent();

        $this->assertStringContainsString('value="Tinggal" selected', $html);
        $this->assertStringContainsString('value="XI-2" selected', $html);
        $this->assertStringContainsString('Naikkan Semua', $html);
        $this->assertStringNotContainsString('Luluskan Semua', $html);
        $this->assertStringContainsString('targetSelect.disabled = !isNaik;', $html);
        $this->assertStringNotContainsString('wrap.innerHTML', $html);

        DB::table('sekolah_kelas')->where('nama', 'X-1')->update([
            'nama' => 'XII-1',
            'tingkat' => 'XII',
        ]);
        $graduationResponse = $this->withSession(['_old_input' => []])
            ->actingAs($admin)
            ->get(route('admin.kenaikan-mutasi', [
                'tahun_ajaran' => $year,
                'kelas' => 'XII-1',
            ]));
        $graduationHtml = (string) $graduationResponse->assertOk()->getContent();

        $this->assertStringContainsString('Luluskan Semua', $graduationHtml);
        $this->assertStringNotContainsString('Naikkan Semua', $graduationHtml);
        $this->assertStringContainsString('value="Lulus" selected', $graduationHtml);
        $this->assertStringNotContainsString('value="Naik"', $graduationHtml);
    }

    public function test_staying_students_are_not_counted_as_promoted(): void
    {
        [$year, $nextYear, $sourceClass, $destinationClass, $teacher, $student] = $this->seedPromotionData();
        $stayingStudent = DB::table('siswa_biodata')->insertGetId([
            'nama_lengkap' => 'Siswa Tetap',
            'nisn' => '0000000002',
        ]);
        DB::table('siswa_riwayat_kelas')->insert([
            'siswa_id' => $stayingStudent,
            'tahun_ajaran_id' => $year,
            'kelas_id' => $sourceClass,
            'status' => 'Aktif',
        ]);
        $user = $this->createUser('guru', 'guru-x1', $teacher);

        $this->actingAs($user)->post(route('guru.kenaikan-kelas.proses'), [
            'kelas_asal' => 'X-1',
            'keputusan' => [
                $student => 'Naik',
                $stayingStudent => 'Tinggal',
            ],
            'kelas_tujuan' => [$student => 'XI-1'],
        ])->assertRedirect(route('guru.kenaikan-mutasi', [
            'tab' => 'kenaikan',
            'tahun_ajaran' => $year,
            'kelas' => 'X-1',
        ]).'#riwayat')
            ->assertSessionHas(
                'success',
                'Proses kenaikan kelas X-1 berhasil: 1 naik ke 2026/2027, 1 tinggal di kelas asal, 0 lulus, 0 keluar.'
            );

        $this->assertDatabaseHas('siswa_riwayat_kelas', [
            'siswa_id' => $stayingStudent,
            'tahun_ajaran_id' => $nextYear,
            'kelas_id' => $sourceClass,
            'status' => 'Aktif',
        ]);
        $this->assertDatabaseHas('catatan_aktivitas', [
            'aksi' => 'kenaikan_kelas',
            'keterangan' => 'Kenaikan kelas X-1 (2025/2026 -> 2026/2027): 1 naik, 1 tinggal, 0 lulus, 0 keluar.',
        ]);
        $this->assertDatabaseHas('siswa_riwayat_kelas', [
            'siswa_id' => $student,
            'tahun_ajaran_id' => $nextYear,
            'kelas_id' => $destinationClass,
            'status' => 'Aktif',
        ]);
    }

    public function test_promotion_list_excludes_a_graduate_with_an_active_history(): void
    {
        [, , $sourceClass, , $teacher, $activeStudent] = $this->seedPromotionData();
        $graduatedStudent = DB::table('siswa_biodata')->insertGetId([
            'nama_lengkap' => 'Siswa Lulus',
            'nisn' => '0000000002',
            'tahun_lulus' => '2025',
        ]);
        DB::table('siswa_riwayat_kelas')->insert([
            'siswa_id' => $graduatedStudent,
            'tahun_ajaran_id' => 1,
            'kelas_id' => $sourceClass,
            'status' => 'Aktif',
        ]);

        $response = $this->actingAs($this->createUser('guru', 'guru-x1', $teacher))
            ->get(route('guru.kenaikan-mutasi', ['kelas' => 'X-1']));

        $response->assertOk()
            ->assertViewHas('siswaList', fn ($students): bool => $students->modelKeys() === [$activeStudent]);
    }

    public function test_wali_cannot_promote_or_cancel_another_class(): void
    {
        [$year, , , , $teacher, $student] = $this->seedPromotionData();
        $otherClass = DB::table('sekolah_kelas')->insertGetId([
            'nama' => 'XI-2',
            'tingkat' => 'XI',
        ]);
        $user = $this->createUser('guru', 'guru-x1', $teacher);

        $this->actingAs($user)->post(route('guru.kenaikan-kelas.proses'), [
            'kelas_asal' => 'XI-1',
            'keputusan' => [$student => 'Naik'],
        ])->assertForbidden();

        $batchId = DB::table('catatan_kenaikan_kelas')->insertGetId([
            'pengguna_id' => $user->id,
            'kelas_asal_id' => $otherClass,
            'tahun_ajaran_asal_id' => $year,
            'jumlah_siswa' => 1,
            'created_at' => now(),
        ]);
        $this->actingAs($user)->post(route('guru.kenaikan-kelas.batalkan', $batchId))
            ->assertForbidden();
        $this->assertDatabaseHas('catatan_kenaikan_kelas', [
            'id' => $batchId,
            'dibatalkan_at' => null,
        ]);
    }

    public function test_wali_only_sees_the_promotion_page_for_their_assigned_class(): void
    {
        [, , , , $teacher] = $this->seedPromotionData();
        $user = $this->createUser('guru', 'guru-x1', $teacher);

        $pageUrl = route('guru.kenaikan-mutasi', ['tab' => 'kenaikan']);
        $this->actingAs($user)->get($pageUrl)
            ->assertOk()
            ->assertSee('Kenaikan &amp; Mutasi', false)
            ->assertSee('Kenaikan Kelas')
            ->assertDontSee('tab=mutasi')
            ->assertDontSee('name="siswa_id"', false);
        $this->actingAs($user)->get(route('guru.kenaikan-mutasi', ['tab' => 'mutasi']))
            ->assertForbidden();
        $this->actingAs($user)->get(route('guru.kenaikan-mutasi', ['kelas' => 'XI-1']))
            ->assertForbidden();
        $this->actingAs($user)->get(route('guru.kenaikan-kelas', ['kelas' => 'X-1']))
            ->assertRedirect(route('guru.kenaikan-mutasi', ['kelas' => 'X-1', 'tab' => 'kenaikan']));
    }

    public function test_admin_legacy_pages_redirect_to_the_matching_combined_tabs(): void
    {
        [$year] = $this->seedPromotionData();
        $admin = $this->createUser('admin', 'admin', null);

        $this->actingAs($admin)
            ->get(route('admin.kenaikan-kelas', ['kelas' => 'X-1', 'tahun_ajaran' => $year]))
            ->assertRedirect(route('admin.kenaikan-mutasi', [
                'kelas' => 'X-1',
                'tahun_ajaran' => $year,
                'tab' => 'kenaikan',
            ]));

        $this->get(route('admin.mutasi-siswa'))
            ->assertRedirect(route('admin.kenaikan-mutasi', ['tab' => 'mutasi']));
    }

    public function test_admin_combined_page_filters_active_students_and_mutation_history_by_year_and_class(): void
    {
        [$year, , $sourceClass, $destinationClass, , $activeStudent] = $this->seedPromotionData();
        $otherClassStudent = DB::table('siswa_biodata')->insertGetId([
            'nama_lengkap' => 'Siswa Kelas Lain',
        ]);
        DB::table('siswa_riwayat_kelas')->insert([
            'siswa_id' => $otherClassStudent,
            'tahun_ajaran_id' => $year,
            'kelas_id' => $destinationClass,
            'status' => 'Aktif',
        ]);
        $graduateStudent = DB::table('siswa_biodata')->insertGetId([
            'nama_lengkap' => 'Siswa Lulus',
            'tahun_lulus' => '2025',
        ]);
        DB::table('siswa_riwayat_kelas')->insert([
            'siswa_id' => $graduateStudent,
            'tahun_ajaran_id' => $year,
            'kelas_id' => $sourceClass,
            'status' => 'Aktif',
        ]);
        $exitStudent = DB::table('siswa_biodata')->insertGetId([
            'nama_lengkap' => 'Siswa Pindah',
            'sekolah_tujuan' => 'SMA Tujuan',
        ]);
        DB::table('siswa_riwayat_kelas')->insert([
            'siswa_id' => $exitStudent,
            'tahun_ajaran_id' => $year,
            'kelas_id' => $sourceClass,
            'status' => 'Pindah keluar',
        ]);
        $otherClassExit = DB::table('siswa_biodata')->insertGetId([
            'nama_lengkap' => 'Siswa Pindah Kelas Lain',
        ]);
        DB::table('siswa_riwayat_kelas')->insert([
            'siswa_id' => $otherClassExit,
            'tahun_ajaran_id' => $year,
            'kelas_id' => $destinationClass,
            'status' => 'Pindah keluar',
        ]);
        $admin = $this->createUser('admin', 'admin', null);

        $mutasiUrl = route('admin.kenaikan-mutasi', [
            'tab' => 'mutasi',
            'tahun_ajaran' => $year,
            'kelas' => 'X-1',
        ]);
        $this->actingAs($admin)
            ->get($mutasiUrl)
            ->assertOk()
            ->assertViewHas('siswaAktif', fn ($students): bool => $students->modelKeys() === [$activeStudent])
            ->assertViewHas('riwayatMutasi', fn ($students): bool => $students->modelKeys() === [$exitStudent])
            ->assertSee('name="siswa_id"', false)
            ->assertDontSee('name="keputusan[', false);

        $this->get(route('admin.kenaikan-mutasi', [
            'tab' => 'kenaikan',
            'tahun_ajaran' => $year,
            'kelas' => 'X-1',
        ]))
            ->assertOk()
            ->assertSee('name="keputusan[', false)
            ->assertDontSee('name="siswa_id"', false);

        $this->get(route('admin.kenaikan-mutasi', [
            'tab' => 'mutasi',
            'tahun_ajaran' => $year,
        ]))
            ->assertOk()
            ->assertViewHas('siswaAktif', function ($students) use ($activeStudent, $otherClassStudent): bool {
                $studentIds = $students->modelKeys();
                sort($studentIds);

                return $studentIds === [$activeStudent, $otherClassStudent];
            })
            ->assertViewHas('riwayatMutasi', fn ($students): bool => $students->count() === 2);
    }

    public function test_mutation_records_an_exit_for_a_student_active_in_the_selected_class_and_year(): void
    {
        [$year, , , , , $student] = $this->seedPromotionData();
        $admin = $this->createUser('admin', 'admin', null);

        $this->actingAs($admin)
            ->post(route('admin.mutasi-siswa.store'), [
                'siswa_id' => $student,
                'jenis' => 'Pindah keluar',
                'tanggal' => '2026-03-01',
                'sekolah_tujuan' => 'SMA Tujuan',
                'tahun_ajaran' => $year,
                'kelas' => 'X-1',
            ])
            ->assertRedirect(route('admin.kenaikan-mutasi', [
                'tab' => 'mutasi',
                'tahun_ajaran' => $year,
                'kelas' => 'X-1',
            ]));

        $this->assertDatabaseHas('siswa_biodata', [
            'id' => $student,
            'tanggal_keluar' => '2026-03-01',
            'alasan_keluar' => 'Pindah keluar',
            'sekolah_tujuan' => 'SMA Tujuan',
            'kelas_keluar' => 'X-1',
        ]);
        $this->assertDatabaseHas('siswa_riwayat_kelas', [
            'siswa_id' => $student,
            'tahun_ajaran_id' => $year,
            'status' => 'Pindah keluar',
        ]);
        $this->assertDatabaseHas('catatan_aktivitas', [
            'pengguna_id' => $admin->id,
            'aksi' => 'mutasi_siswa',
            'objek_id' => $student,
        ]);
    }

    public function test_mutation_rejects_a_graduate_with_an_active_history(): void
    {
        [$year, , $sourceClass] = $this->seedPromotionData();
        $graduate = DB::table('siswa_biodata')->insertGetId([
            'nama_lengkap' => 'Siswa Lulus',
            'tahun_lulus' => '2025',
        ]);
        DB::table('siswa_riwayat_kelas')->insert([
            'siswa_id' => $graduate,
            'tahun_ajaran_id' => $year,
            'kelas_id' => $sourceClass,
            'status' => 'Aktif',
        ]);
        $admin = $this->createUser('admin', 'admin', null);
        $pageUrl = route('admin.kenaikan-mutasi', [
            'tab' => 'mutasi',
            'tahun_ajaran' => $year,
            'kelas' => 'X-1',
        ]);

        $this->from($pageUrl)->actingAs($admin)
            ->post(route('admin.mutasi-siswa.store'), [
                'siswa_id' => $graduate,
                'jenis' => 'Keluar',
                'tanggal' => '2026-03-01',
                'tahun_ajaran' => $year,
                'kelas' => 'X-1',
            ])
            ->assertRedirect($pageUrl)
            ->assertSessionHasErrors(['siswa_id']);

        $this->assertDatabaseHas('siswa_biodata', [
            'id' => $graduate,
            'tahun_lulus' => '2025',
            'tanggal_keluar' => null,
        ]);
        $this->assertDatabaseMissing('catatan_aktivitas', [
            'aksi' => 'mutasi_siswa',
            'objek_id' => $graduate,
        ]);
    }

    public function test_assigning_a_new_homeroom_teacher_updates_only_histories_in_that_year(): void
    {
        [$year, , $sourceClass, , $teacher, $student] = $this->seedPromotionData();
        $oldYear = DB::table('sekolah_tahun_ajaran')->insertGetId([
            'nama' => '2024/2025',
            'sedang_berjalan' => false,
        ]);
        DB::table('siswa_riwayat_kelas')->insert([
            'siswa_id' => $student,
            'tahun_ajaran_id' => $oldYear,
            'kelas_id' => $sourceClass,
            'status' => 'Aktif',
            'wali_guru_id' => $teacher,
            'nama_wali_kelas' => 'Guru Lama',
            'nip_wali_kelas' => '1234567890',
        ]);
        $admin = $this->createUser('admin', 'admin', null);

        $this->actingAs($admin)->post(route('admin.data-guru.store'), [
            'nama' => 'Guru Pengganti',
            'nip' => '1098765432',
            'is_wali' => '1',
            'wali_kelas' => 'X-1',
        ])->assertRedirect(route('admin.guru-master', ['tab' => 'guru']));

        $newTeacherId = DB::table('guru_biodata')->where('nip', '1098765432')->value('id');
        $this->assertDatabaseHas('siswa_riwayat_kelas', [
            'siswa_id' => $student,
            'tahun_ajaran_id' => $year,
            'wali_guru_id' => $newTeacherId,
            'nama_wali_kelas' => 'Guru Pengganti',
            'nip_wali_kelas' => '1098765432',
        ]);
        $this->assertDatabaseHas('siswa_riwayat_kelas', [
            'siswa_id' => $student,
            'tahun_ajaran_id' => $oldYear,
            'wali_guru_id' => $teacher,
            'nama_wali_kelas' => 'Guru Lama',
            'nip_wali_kelas' => '1234567890',
        ]);
    }

    public function test_updating_teacher_replaces_current_year_assignments_and_keeps_old_year_snapshots(): void
    {
        [$year, $nextYear, $sourceClass, $destinationClass, $teacher, $student] = $this->seedPromotionData();
        DB::table('sekolah_mapel')->insert([
            ['id' => 1, 'kode' => 'mtk', 'nama' => 'Matematika'],
            ['id' => 2, 'kode' => 'ind', 'nama' => 'Bahasa Indonesia'],
        ]);
        DB::table('guru_mengajar')->insert([
            [
                'tahun_ajaran_id' => $year,
                'guru_id' => $teacher,
                'kelas_id' => $sourceClass,
                'mapel_id' => 1,
            ],
            [
                'tahun_ajaran_id' => $nextYear,
                'guru_id' => $teacher,
                'kelas_id' => $sourceClass,
                'mapel_id' => 1,
            ],
        ]);
        DB::table('siswa_riwayat_kelas')->where('id', function ($query) use ($student, $year): void {
            $query->select('id')
                ->from('siswa_riwayat_kelas')
                ->where('siswa_id', $student)
                ->where('tahun_ajaran_id', $year);
        })->update([
            'wali_guru_id' => $teacher,
            'nama_wali_kelas' => 'Guru X1',
            'nip_wali_kelas' => '1234567890',
        ]);

        $previousYear = DB::table('sekolah_tahun_ajaran')->insertGetId([
            'nama' => '2024/2025',
            'sedang_berjalan' => false,
        ]);
        DB::table('siswa_riwayat_kelas')->insert([
            'siswa_id' => $student,
            'tahun_ajaran_id' => $previousYear,
            'kelas_id' => $sourceClass,
            'status' => 'Aktif',
            'wali_guru_id' => $teacher,
            'nama_wali_kelas' => 'Guru X1',
            'nip_wali_kelas' => '1234567890',
        ]);
        $targetStudent = DB::table('siswa_biodata')->insertGetId([
            'nama_lengkap' => 'Siswa Kelas Tujuan',
            'nisn' => '0000000002',
        ]);
        DB::table('siswa_riwayat_kelas')->insert([
            'siswa_id' => $targetStudent,
            'tahun_ajaran_id' => $year,
            'kelas_id' => $destinationClass,
            'status' => 'Aktif',
        ]);
        $admin = $this->createUser('admin', 'admin', null);

        $this->actingAs($admin)->put(route('admin.data-guru.update', $teacher), [
            'nama' => 'Guru X1 Diperbarui',
            'nip' => '1234567890',
            'is_wali' => '1',
            'wali_kelas' => 'XI-1',
            'is_mapel' => '1',
            'penugasan_mengajar' => [
                ['kelas' => 'XI-1', 'mapel' => 'ind'],
            ],
        ])->assertRedirect(route('admin.guru-master', ['tab' => 'guru']));

        $this->assertDatabaseHas('guru_wali_kelas', [
            'tahun_ajaran_id' => $year,
            'kelas_id' => $destinationClass,
            'guru_id' => $teacher,
        ]);
        $this->assertDatabaseMissing('guru_wali_kelas', [
            'tahun_ajaran_id' => $year,
            'kelas_id' => $sourceClass,
            'guru_id' => $teacher,
        ]);
        $this->assertDatabaseHas('guru_mengajar', [
            'tahun_ajaran_id' => $year,
            'guru_id' => $teacher,
            'kelas_id' => $destinationClass,
            'mapel_id' => 2,
        ]);
        $this->assertDatabaseMissing('guru_mengajar', [
            'tahun_ajaran_id' => $year,
            'guru_id' => $teacher,
            'kelas_id' => $sourceClass,
            'mapel_id' => 1,
        ]);
        $this->assertDatabaseHas('guru_mengajar', [
            'tahun_ajaran_id' => $nextYear,
            'guru_id' => $teacher,
            'kelas_id' => $sourceClass,
            'mapel_id' => 1,
        ]);
        $this->assertDatabaseHas('siswa_riwayat_kelas', [
            'siswa_id' => $targetStudent,
            'tahun_ajaran_id' => $year,
            'wali_guru_id' => $teacher,
            'nama_wali_kelas' => 'Guru X1 Diperbarui',
            'nip_wali_kelas' => '1234567890',
        ]);
        $this->assertDatabaseHas('siswa_riwayat_kelas', [
            'siswa_id' => $student,
            'tahun_ajaran_id' => $previousYear,
            'nama_wali_kelas' => 'Guru X1',
            'nip_wali_kelas' => '1234567890',
        ]);
        $this->assertDatabaseHas('catatan_aktivitas', [
            'pengguna_id' => $admin->id,
            'aksi' => 'edit_guru',
            'objek_id' => $teacher,
        ]);
    }

    public function test_teacher_form_provides_homeroom_and_paired_teaching_assignment_choices(): void
    {
        $this->seedPromotionData();
        $admin = $this->createUser('admin', 'admin', null);

        $this->actingAs($admin)->get(route('admin.guru-master', ['tab' => 'guru']))
            ->assertOk()
            ->assertSee('Pilih kelas wali')
            ->assertSee('Tambah penugasan kelas dan mapel')
            ->assertSee('penugasan_mengajar[');
    }

    public function test_combined_teacher_and_master_tabs_render_only_the_active_section(): void
    {
        [$year, , $sourceClass, , $teacher] = $this->seedPromotionData();
        DB::table('sekolah_mapel')->insert([
            'id' => 1,
            'kode' => 'mtk',
            'nama' => 'Matematika',
            'tingkat' => 'X,XI,XII',
        ]);
        DB::table('guru_mengajar')->insert([
            'tahun_ajaran_id' => $year,
            'guru_id' => $teacher,
            'kelas_id' => $sourceClass,
            'mapel_id' => 1,
        ]);
        $admin = $this->createUser('admin', 'admin', null);

        $this->actingAs($admin)
            ->get(route('admin.guru-master', ['tab' => 'guru']))
            ->assertOk()
            ->assertSee('Guru X1')
            ->assertSee('Guru Mapel')
            ->assertSee('Matematika')
            ->assertDontSee('Tambah kelas')
            ->assertDontSee('Tambah mapel');

        $this->get(route('admin.guru-master', ['tab' => 'kelas']))
            ->assertOk()
            ->assertSee('X-1')
            ->assertSee('Tambah kelas')
            ->assertDontSee('Tambah mapel')
            ->assertDontSee('Pilih kelas wali');

        $this->get(route('admin.guru-master', ['tab' => 'mapel']))
            ->assertOk()
            ->assertSee('Matematika')
            ->assertSee('Tambah mapel')
            ->assertDontSee('Tambah kelas')
            ->assertDontSee('Pilih kelas wali');
    }

    public function test_legacy_teacher_and_master_pages_redirect_to_their_tabs(): void
    {
        $admin = $this->createUser('admin', 'admin', null);

        $this->actingAs($admin)
            ->get(route('admin.data-guru'))
            ->assertRedirect(route('admin.guru-master', ['tab' => 'guru']));

        $this->get(route('admin.master-kelas'))
            ->assertRedirect(route('admin.guru-master', ['tab' => 'kelas']));
    }

    public function test_class_and_subject_forms_keep_saving_to_their_tabs(): void
    {
        $this->seedPromotionData();
        $admin = $this->createUser('admin', 'admin', null);

        $this->actingAs($admin)
            ->post(route('admin.master-kelas.store'), [
                'type' => 'kelas',
                'nama_kelas' => 'X-2',
            ])
            ->assertRedirect(route('admin.guru-master', ['tab' => 'kelas']));

        $this->assertDatabaseHas('sekolah_kelas', [
            'nama' => 'X-2',
            'tingkat' => 'X',
        ]);

        $this->post(route('admin.master-kelas.store'), [
            'type' => 'mapel',
            'nama_mapel' => 'Fisika',
            'kode_mapel' => 'fis',
            'tingkat_mapel' => ['X', 'XI'],
        ])->assertRedirect(route('admin.guru-master', ['tab' => 'mapel']));

        $this->assertDatabaseHas('sekolah_mapel', [
            'kode' => 'fis',
            'nama' => 'Fisika',
            'tingkat' => 'X,XI',
        ]);
    }

    private function seedPromotionData(): array
    {
        $year = DB::table('sekolah_tahun_ajaran')->insertGetId([
            'nama' => '2025/2026',
            'sedang_berjalan' => true,
        ]);
        $nextYear = DB::table('sekolah_tahun_ajaran')->insertGetId([
            'nama' => '2026/2027',
            'sedang_berjalan' => false,
        ]);
        $sourceClass = DB::table('sekolah_kelas')->insertGetId(['nama' => 'X-1', 'tingkat' => 'X']);
        $destinationClass = DB::table('sekolah_kelas')->insertGetId(['nama' => 'XI-1', 'tingkat' => 'XI']);
        $teacher = $this->createTeacher('Guru X1', '1234567890');
        DB::table('guru_wali_kelas')->insert([
            'tahun_ajaran_id' => $year,
            'kelas_id' => $sourceClass,
            'guru_id' => $teacher,
        ]);
        $student = DB::table('siswa_biodata')->insertGetId([
            'nama_lengkap' => 'Siswa Uji',
            'nisn' => '0000000001',
        ]);
        DB::table('siswa_riwayat_kelas')->insert([
            'siswa_id' => $student,
            'tahun_ajaran_id' => $year,
            'kelas_id' => $sourceClass,
            'status' => 'Aktif',
        ]);

        return [$year, $nextYear, $sourceClass, $destinationClass, $teacher, $student];
    }

    private function createTeacher(string $name, string $nip): int
    {
        return (int) DB::table('guru_biodata')->insertGetId([
            'nama' => $name,
            'nip' => $nip,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createUser(string $role, string $username, ?int $teacherId): Pengguna
    {
        return Pengguna::create([
            'peran' => $role,
            'username' => $username,
            'password' => 'test-password',
            'aktif' => true,
            'guru_id' => $teacherId,
            'wajib_ganti_password' => false,
        ]);
    }
}
