<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\DashboardAdminController;
use App\Http\Controllers\Admin\DataSiswaController;
use App\Http\Controllers\Admin\LaporanController;
use App\Models\Auth\Pengguna;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DataSiswaBulkActionsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

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
        Schema::create('sekolah_tahun_ajaran', function (Blueprint $table): void {
            $table->id();
            $table->string('nama');
            $table->boolean('sedang_berjalan')->default(false);
            $table->timestamps();
        });
        Schema::create('sekolah_kelas', function (Blueprint $table): void {
            $table->id();
            $table->string('nama');
            $table->string('tingkat');
            $table->timestamps();
        });
        Schema::create('siswa_biodata', function (Blueprint $table): void {
            $table->id();
            $table->string('nisn')->nullable();
            $table->string('nomor_induk')->nullable();
            $table->string('nama_lengkap');
            $table->boolean('foto_ada')->default(false);
            $table->string('no_skl')->nullable();
            $table->string('no_ijazah_sma')->nullable();
            $table->string('no_transkrip')->nullable();
            $table->string('jenis_kelamin')->nullable();
            $table->string('nik')->nullable();
            $table->string('tempat_lahir')->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->string('agama')->nullable();
            $table->string('nama_ayah')->nullable();
            $table->string('nama_ibu')->nullable();
            $table->string('alamat_jalan')->nullable();
            $table->string('tahun_lulus', 4)->nullable();
            $table->string('alasan_keluar')->nullable();
            $table->date('tanggal_keluar')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('siswa_riwayat_kelas', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('siswa_id');
            $table->unsignedBigInteger('tahun_ajaran_id');
            $table->unsignedBigInteger('kelas_id');
            $table->string('status');
            $table->softDeletes();
            $table->timestamps();
            $table->unique(['siswa_id', 'tahun_ajaran_id']);
        });
        Schema::create('siswa_berkas', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('siswa_id');
        });
        Schema::create('sekolah_mapel', function (Blueprint $table): void {
            $table->id();
            $table->string('kode')->nullable();
            $table->string('nama')->nullable();
        });
        Schema::create('siswa_nilai', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('riwayat_kelas_id');
            $table->unsignedBigInteger('mapel_id');
            $table->string('semester');
            $table->decimal('nilai_angka', 5, 2)->nullable();
        });
        Schema::create('siswa_kehadiran', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('riwayat_kelas_id');
            $table->unsignedInteger('sakit')->default(0);
            $table->unsignedInteger('izin')->default(0);
            $table->unsignedInteger('alpa')->default(0);
        });
        Schema::create('siswa_ekskul', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('riwayat_kelas_id');
        });
        Schema::create('catatan_aktivitas', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('pengguna_id')->nullable();
            $table->string('peran')->nullable();
            $table->string('aksi');
            $table->string('keterangan')->nullable();
            $table->string('objek_tipe')->nullable();
            $table->unsignedBigInteger('objek_id')->nullable();
            $table->string('alamat_ip')->nullable();
            $table->timestamp('created_at')->nullable();
        });
        Schema::create('guru_biodata', function (Blueprint $table): void {
            $table->id();
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('sekolah_identitas', function (Blueprint $table): void {
            $table->unsignedTinyInteger('id')->primary();
            $table->string('nama')->nullable();
        });

        DB::table('sekolah_tahun_ajaran')->insert([
            ['id' => 1, 'nama' => '2025/2026', 'sedang_berjalan' => false],
            ['id' => 2, 'nama' => '2026/2027', 'sedang_berjalan' => true],
        ]);
        DB::table('sekolah_kelas')->insert([
            ['id' => 1, 'nama' => 'X-1', 'tingkat' => 'X'],
            ['id' => 2, 'nama' => 'X-2', 'tingkat' => 'X'],
            ['id' => 3, 'nama' => 'XI-1', 'tingkat' => 'XI'],
        ]);
    }

    public function test_selected_delete_only_trashes_selected_students_inside_the_filter(): void
    {
        $admin = $this->createAccount('admin');
        $selectedStudentId = $this->createStudent('Dipilih', 2, 1);
        $otherClassStudentId = $this->createStudent('Kelas lain', 2, 2);
        $unselectedStudentId = $this->createStudent('Tidak dipilih', 2, 1);

        $response = $this->actingAs($admin)->from(route('admin.data-siswa'))
            ->delete(route('admin.data-siswa.bulk-delete'), [
                'tahun_ajaran' => '2',
                'kelas' => 'X-1',
                'konfirmasi' => 'hapus',
                'siswa_ids' => [$selectedStudentId, $otherClassStudentId],
            ]);

        $response->assertRedirect(route('admin.data-siswa'));
        $this->assertSoftDeleted('siswa_biodata', ['id' => $selectedStudentId]);
        $this->assertNotSoftDeleted('siswa_biodata', ['id' => $otherClassStudentId]);
        $this->assertNotSoftDeleted('siswa_biodata', ['id' => $unselectedStudentId]);
        $this->assertDatabaseHas('catatan_aktivitas', [
            'pengguna_id' => $admin->id,
            'peran' => 'admin',
            'aksi' => 'hapus_siswa_massal',
        ]);
    }

    public function test_delete_all_for_current_year_does_not_trash_students_from_other_years(): void
    {
        $this->actingAs($this->createAccount('admin'));
        $previousYearStudentId = $this->createStudent('Tahun sebelumnya', 1, 1);
        $currentYearStudentId = $this->createStudent('Tahun berjalan', 2, 1);
        $currentYearOtherClassStudentId = $this->createStudent('Kelas lain tahun ini', 2, 2);

        $response = $this->from(route('admin.data-siswa'))->delete(route('admin.data-siswa.bulk-delete'), [
            'tahun_ajaran' => '2',
            'kelas' => '*',
            'konfirmasi' => 'hapus',
        ]);

        $response->assertRedirect(route('admin.data-siswa'));
        $this->assertNotSoftDeleted('siswa_biodata', ['id' => $previousYearStudentId]);
        $this->assertSoftDeleted('siswa_biodata', ['id' => $currentYearStudentId]);
        $this->assertSoftDeleted('siswa_biodata', ['id' => $currentYearOtherClassStudentId]);
    }

    public function test_selected_class_move_only_updates_selected_students_and_logs_the_action(): void
    {
        $admin = $this->createAccount('admin');
        $selectedStudentId = $this->createStudent('Dipilih pindah satu', 2, 1);
        $secondSelectedStudentId = $this->createStudent('Dipilih pindah dua', 2, 1);
        $unselectedStudentId = $this->createStudent('Tetap di kelas', 2, 1);

        $response = $this->actingAs($admin)->from(route('admin.data-siswa'))
            ->post(route('admin.data-siswa.bulk-update'), [
                'tahun_ajaran' => 2,
                'kelas' => 'X-1',
                'target_kelas' => 'X-2',
                'siswa_ids' => [$selectedStudentId, $secondSelectedStudentId],
            ]);

        $response->assertRedirect(route('admin.data-siswa'));
        $this->assertDatabaseHas('siswa_riwayat_kelas', [
            'siswa_id' => $selectedStudentId,
            'tahun_ajaran_id' => 2,
            'kelas_id' => 2,
        ]);
        $this->assertDatabaseHas('siswa_riwayat_kelas', [
            'siswa_id' => $secondSelectedStudentId,
            'tahun_ajaran_id' => 2,
            'kelas_id' => 2,
        ]);
        $this->assertDatabaseHas('siswa_riwayat_kelas', [
            'siswa_id' => $unselectedStudentId,
            'tahun_ajaran_id' => 2,
            'kelas_id' => 1,
        ]);
        $this->assertDatabaseCount('siswa_riwayat_kelas', 3);
        $this->assertDatabaseHas('catatan_aktivitas', [
            'pengguna_id' => $admin->id,
            'aksi' => 'pindah_kelas_massal',
        ]);
        $this->assertDatabaseCount('catatan_aktivitas', 2);
        $response->assertSessionHas('bulk_move_results.0.berhasil', true);
        $response->assertSessionHas('bulk_move_results.1.berhasil', true);
    }

    public function test_bulk_move_rejects_a_class_at_a_different_level_from_existing_grade_semesters(): void
    {
        $admin = $this->createAccount('admin');
        $studentId = $this->createStudent('Nilai kelas X', 1, 1);
        $historyId = DB::table('siswa_riwayat_kelas')
            ->where('siswa_id', $studentId)
            ->where('tahun_ajaran_id', 1)
            ->value('id');
        DB::table('siswa_nilai')->insert([
            'riwayat_kelas_id' => $historyId,
            'mapel_id' => 1,
            'semester' => 'Ganjil',
        ]);

        $response = $this->actingAs($admin)->from(route('admin.data-siswa'))
            ->post(route('admin.data-siswa.bulk-update'), [
                'tahun_ajaran' => 1,
                'kelas' => 'X-1',
                'target_kelas' => 'XI-1',
                'siswa_ids' => [$studentId],
            ]);

        $response->assertRedirect(route('admin.data-siswa'));
        $response->assertSessionHas('bulk_move_results.0.berhasil', false);
        $this->assertDatabaseHas('siswa_riwayat_kelas', [
            'siswa_id' => $studentId,
            'tahun_ajaran_id' => 1,
            'kelas_id' => 1,
        ]);
        $this->assertDatabaseCount('catatan_aktivitas', 0);
    }

    public function test_bulk_move_does_not_create_a_history_when_the_selected_year_has_none(): void
    {
        $this->actingAs($this->createAccount('admin'));
        $studentId = $this->createStudent('Tidak ada riwayat tahun berjalan', 1, 1);

        $response = $this->from(route('admin.data-siswa'))->post(route('admin.data-siswa.bulk-update'), [
            'tahun_ajaran' => 2,
            'kelas' => '*',
            'target_kelas' => 'X-2',
            'siswa_ids' => [$studentId],
        ]);

        $response->assertRedirect(route('admin.data-siswa'));
        $response->assertSessionHas('bulk_move_results.0.berhasil', false);
        $this->assertDatabaseMissing('siswa_riwayat_kelas', [
            'siswa_id' => $studentId,
            'tahun_ajaran_id' => 2,
        ]);
    }

    #[DataProvider('nonActiveTargetStatuses')]
    public function test_bulk_move_rejects_non_active_target_statuses(string $targetStatus): void
    {
        $studentId = $this->createStudent('Siswa Aktif', 2, 1);

        $response = $this->actingAs($this->createAccount('admin'))
            ->post(route('admin.data-siswa.bulk-update'), [
                'tahun_ajaran' => 2,
                'kelas' => 'X-1',
                'target_kelas' => 'X-2',
                'target_status' => $targetStatus,
                'siswa_ids' => [$studentId],
            ]);

        $response->assertSessionHasErrors(['target_status']);
        $this->assertDatabaseHas('siswa_riwayat_kelas', [
            'siswa_id' => $studentId,
            'tahun_ajaran_id' => 2,
            'status' => 'Aktif',
            'kelas_id' => 1,
        ]);
    }

    /** @return array<string, array{string}> */
    public static function nonActiveTargetStatuses(): array
    {
        return [
            'lulus' => ['Lulus'],
            'pindah keluar' => ['Pindah keluar'],
            'keluar' => ['Keluar'],
        ];
    }

    public function test_admin_data_siswa_status_dropdown_only_offers_active(): void
    {
        $this->createStudent('Siswa Aktif', 2, 1);

        $response = $this->actingAs($this->createAccount('admin'))
            ->get(route('admin.data-siswa'));

        $response->assertOk()
            ->assertDontSee('<option value="Lulus"', false)
            ->assertDontSee('<option value="Pindah keluar"', false)
            ->assertDontSee('<option value="Keluar"', false);
    }

    public function test_data_siswa_renders_the_shared_create_form_in_a_side_panel(): void
    {
        $admin = $this->createAccount('admin');

        $response = $this->actingAs($admin)->get(route('admin.data-siswa', [
            'panel' => 'form',
            'mode' => 'create',
        ]));

        $response->assertOk()
            ->assertSee('id="student-form-panel"', false)
            ->assertSee('onclick="return closeStudentForm(event)"', false)
            ->assertSee('Tambah Siswa')
            ->assertSee('name="foto_file"', false)
            ->assertSee('name="berkas[kk]"', false)
            ->assertSee('Data Ayah Kandung')
            ->assertSee('Lampiran Berkas');
    }

    public function test_legacy_form_routes_redirect_to_data_siswa_panel(): void
    {
        $admin = $this->createAccount('admin');
        $studentId = $this->createStudent('Siswa Aktif', 2, 1);
        $createPanelUrl = route('admin.data-siswa', [
            'panel' => 'form',
            'mode' => 'create',
        ]);
        $editPanelUrl = route('admin.data-siswa', [
            'panel' => 'form',
            'mode' => 'edit',
            'siswa' => $studentId,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.form-pendataan'))
            ->assertRedirect($createPanelUrl);
        $this->get(route('admin.data-siswa.create'))
            ->assertRedirect($createPanelUrl);
        $this->get(route('admin.form-pendataan.edit', $studentId))
            ->assertRedirect($editPanelUrl);
        $this->get(route('admin.data-siswa.edit', $studentId))
            ->assertRedirect($editPanelUrl);
    }

    public function test_validation_error_reopens_form_panel_with_previous_input(): void
    {
        $admin = $this->createAccount('admin');
        $formUrl = route('admin.data-siswa', [
            'panel' => 'form',
            'mode' => 'create',
        ]);

        $this->actingAs($admin)
            ->from($formUrl)
            ->followingRedirects()
            ->post(route('admin.data-siswa.store'), [
                '_form_panel' => '1',
                '_form_mode' => 'create',
                'nama_lengkap' => 'Nama Tetap Tersimpan',
                'nisn' => 'invalid',
            ])
            ->assertOk()
            ->assertSee('id="student-form-panel"', false)
            ->assertSee('value="Nama Tetap Tersimpan"', false)
            ->assertSee('role="alert"', false);
    }

    public function test_bulk_delete_requires_explicit_confirmation(): void
    {
        $this->actingAs($this->createAccount('admin'));
        $studentId = $this->createStudent('Tidak terhapus', 1, 1);

        $response = $this->delete(route('admin.data-siswa.bulk-delete'), [
            'tahun_ajaran' => '1',
            'kelas' => 'X-1',
        ]);

        $response->assertSessionHasErrors(['konfirmasi']);
        $this->assertNotSoftDeleted('siswa_biodata', ['id' => $studentId]);
    }

    public function test_bulk_delete_rejects_an_unbounded_all_years_filter(): void
    {
        $this->actingAs($this->createAccount('admin'));
        $studentId = $this->createStudent('Tidak terhapus', 1, 1);

        $response = $this->from(route('admin.data-siswa'))->delete(route('admin.data-siswa.bulk-delete'), [
            'tahun_ajaran' => '*',
            'kelas' => '*',
            'konfirmasi' => 'hapus',
        ]);

        $response->assertRedirect(route('admin.data-siswa'));
        $response->assertSessionHas('error', 'Pilih tahun ajaran tertentu atau kelas tertentu agar penghapusan massal memiliki batas yang jelas.');
        $this->assertNotSoftDeleted('siswa_biodata', ['id' => $studentId]);
    }

    public function test_teacher_cannot_use_admin_bulk_delete_endpoint(): void
    {
        $studentId = $this->createStudent('Tetap aman', 1, 1);

        $this->actingAs($this->createAccount('guru'))
            ->delete(route('admin.data-siswa.bulk-delete'), [
                'tahun_ajaran' => '1',
                'kelas' => 'X-1',
                'konfirmasi' => 'hapus',
            ])
            ->assertForbidden();

        $this->assertNotSoftDeleted('siswa_biodata', ['id' => $studentId]);
    }

    public function test_default_data_siswa_list_includes_all_classes_for_the_active_year(): void
    {
        $firstStudentId = $this->createStudent('Kelas X-1', 2, 1);
        $secondStudentId = $this->createStudent('Kelas X-2', 2, 2);
        $previousYearStudentId = $this->createStudent('Tahun sebelumnya', 1, 1);
        $graduatedStudentId = $this->createStudent('Siswa Lulus', 2, 1);
        $transferredStudentId = $this->createStudent('Siswa Pindah', 2, 2);
        DB::table('siswa_biodata')->where('id', $graduatedStudentId)->update(['tahun_lulus' => '2026']);
        DB::table('siswa_riwayat_kelas')->where('siswa_id', $transferredStudentId)->update(['status' => 'Pindah keluar']);

        $view = app(DataSiswaController::class)->index(Request::create('/admin/data-siswa'));
        $siswaList = $view->getData()['siswaList'];
        $listedIds = $siswaList->getCollection()->pluck('id')->all();
        sort($listedIds);

        $this->assertSame(2, $siswaList->total());
        $this->assertSame([$firstStudentId, $secondStudentId], $listedIds);
        $this->assertNotContains($previousYearStudentId, $listedIds);
        $this->assertNotContains($graduatedStudentId, $listedIds);
        $this->assertNotContains($transferredStudentId, $listedIds);
    }

    public function test_admin_export_excludes_graduated_and_transferred_students(): void
    {
        $admin = $this->createAccount('admin');
        $this->createStudent('Siswa Aktif', 2, 1);
        $graduatedStudentId = $this->createStudent('Siswa Lulus', 2, 1);
        $transferredStudentId = $this->createStudent('Siswa Pindah', 2, 2);
        DB::table('siswa_biodata')->where('id', $graduatedStudentId)->update(['tahun_lulus' => '2026']);
        DB::table('siswa_riwayat_kelas')->where('siswa_id', $transferredStudentId)->update(['status' => 'Pindah keluar']);

        $response = $this->actingAs($admin)->get(route('admin.data-siswa.export'));
        $response->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $workbook = IOFactory::load($response->baseResponse->getFile()->getPathname());
        $sheet = $workbook->getActiveSheet();
        $exportedNames = array_column($sheet->toArray(), 2);

        $this->assertContains('Siswa Aktif', $exportedNames);
        $this->assertNotContains('Siswa Lulus', $exportedNames);
        $this->assertNotContains('Siswa Pindah', $exportedNames);
        $workbook->disconnectWorksheets();
    }

    public function test_dashboard_report_and_data_siswa_count_the_same_active_students(): void
    {
        $this->createStudent('Siswa Aktif Satu', 2, 1);
        $this->createStudent('Siswa Aktif Dua', 2, 2);
        $graduatedStudentId = $this->createStudent('Siswa Lulus', 2, 1);
        $transferredStudentId = $this->createStudent('Siswa Pindah', 2, 2);
        $exitedStudentId = $this->createStudent('Siswa Keluar', 2, 1);
        DB::table('siswa_biodata')->where('id', $graduatedStudentId)->update(['tahun_lulus' => '2026']);
        DB::table('siswa_riwayat_kelas')->where('siswa_id', $transferredStudentId)->update(['status' => 'Pindah keluar']);
        DB::table('siswa_biodata')->where('id', $exitedStudentId)->update(['tanggal_keluar' => '2026-10-01']);

        $dashboard = app(DashboardAdminController::class)->index();
        $report = app(LaporanController::class)->index(Request::create('/admin/laporan', 'GET', [
            'ta' => '2026/2027',
        ]));
        $dataSiswa = app(DataSiswaController::class)->index(Request::create('/admin/data-siswa'));

        $this->assertSame(2, $dashboard->getData()['totalSiswaAktif']);
        $this->assertSame(2, array_sum(array_column($dashboard->getData()['komposisiKelas'], 'total')));
        $this->assertSame(2, $report->getData()['totalSiswa']);
        $this->assertSame(2, $dataSiswa->getData()['siswaList']->total());
    }

    public function test_report_and_quality_tabs_render_only_the_active_view(): void
    {
        $admin = $this->createAccount('admin');
        $this->createStudent('Siswa Aktif', 2, 1);
        $graduatedStudentId = $this->createStudent('Siswa Lulus', 2, 1);
        DB::table('siswa_biodata')->where('id', $graduatedStudentId)->update(['tahun_lulus' => '2026']);

        $report = $this->actingAs($admin)->get(route('admin.laporan', [
            'tab' => 'laporan',
            'ta' => '2026/2027',
        ]));

        $report->assertOk()
            ->assertViewHas('tab', 'laporan')
            ->assertSee('Rekap 2026/2027')
            ->assertSee('Cetak laporan')
            ->assertDontSee('Siswa diperiksa');

        $quality = $this->get(route('admin.laporan', ['tab' => 'kualitas']));

        $quality->assertOk()
            ->assertViewHas('tab', 'kualitas')
            ->assertViewHas('totalSiswa', 1)
            ->assertSee('Siswa diperiksa')
            ->assertSee('Siswa Aktif')
            ->assertDontSee('Siswa Lulus')
            ->assertDontSee('Rata-rata nilai');
    }

    public function test_quality_duplicate_findings_link_each_active_student_for_repair(): void
    {
        $admin = $this->createAccount('admin');
        $firstStudentId = $this->createStudent('Duplikat aktif satu', 2, 1);
        $secondStudentId = $this->createStudent('Duplikat aktif dua', 2, 1);
        $graduatedStudentId = $this->createStudent('Duplikat lulus', 2, 1);
        DB::table('siswa_biodata')->where('id', $graduatedStudentId)->update(['tahun_lulus' => '2026']);

        $response = $this->actingAs($admin)->get(route('admin.laporan', ['tab' => 'kualitas']));

        $response->assertOk()
            ->assertSee('NISN duplikat — ditemukan pada 2 siswa')
            ->assertSee(route('admin.data-siswa.edit', $firstStudentId), false)
            ->assertSee(route('admin.data-siswa.edit', $secondStudentId), false)
            ->assertDontSee(route('admin.data-siswa.edit', $graduatedStudentId), false);
    }

    public function test_quality_excel_and_legacy_routes_redirect_to_the_quality_tab(): void
    {
        $admin = $this->createAccount('admin');
        $activeStudentId = $this->createStudent('Siswa Aktif', 2, 1);
        $graduatedStudentId = $this->createStudent('Siswa Lulus', 2, 1);
        DB::table('siswa_biodata')->whereIn('id', [$activeStudentId, $graduatedStudentId])->update(['nisn' => null]);
        DB::table('siswa_biodata')->where('id', $graduatedStudentId)->update(['tahun_lulus' => '2026']);

        $this->actingAs($admin)
            ->get(route('admin.cek-kualitas-data', ['jenis' => 'kesalahan']))
            ->assertRedirect(route('admin.laporan', ['tab' => 'kualitas', 'jenis' => 'kesalahan']));

        $this->get(route('admin.cek-kualitas-data.export', ['jenis' => 'kesalahan']))
            ->assertRedirect(route('admin.laporan', ['tab' => 'kualitas', 'jenis' => 'kesalahan', 'export' => 1]));

        $response = $this->get(route('admin.laporan', [
            'tab' => 'kualitas',
            'jenis' => 'kesalahan',
            'export' => 1,
        ]));

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $workbook = IOFactory::load($response->baseResponse->getFile()->getPathname());
        $sheet = $workbook->getActiveSheet();
        $exportedNames = array_column($sheet->toArray(), 2);

        $this->assertContains('Siswa Aktif', $exportedNames);
        $this->assertNotContains('Siswa Lulus', $exportedNames);
        $workbook->disconnectWorksheets();
    }

    private function createAccount(string $role): Pengguna
    {
        $id = DB::table('akun_pengguna')->insertGetId([
            'peran' => $role,
            'username' => $role.'-test',
            'email' => $role.'@example.test',
            'password' => 'password',
            'aktif' => true,
            'wajib_ganti_password' => false,
        ]);

        return Pengguna::findOrFail($id);
    }

    private function createStudent(string $name, int $yearId, int $classId): int
    {
        $studentId = DB::table('siswa_biodata')->insertGetId([
            'nisn' => '100000000'.(string) $yearId.(string) $classId,
            'nomor_induk' => '500'.$yearId.$classId,
            'nama_lengkap' => $name,
        ]);
        DB::table('siswa_riwayat_kelas')->insert([
            'siswa_id' => $studentId,
            'tahun_ajaran_id' => $yearId,
            'kelas_id' => $classId,
            'status' => 'Aktif',
        ]);

        return $studentId;
    }
}
