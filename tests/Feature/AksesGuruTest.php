<?php

namespace Tests\Feature;

use App\Models\Auth\Pengguna;
use App\Models\Guru\GuruBiodata;
use App\Models\Guru\GuruMengajar;
use App\Models\Guru\GuruWaliKelas;
use App\Models\SekolahTahunAjaran;
use App\Services\AksesGuru;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tests\TestCase;

#[RunTestsInSeparateProcesses]
class AksesGuruTest extends TestCase
{
    private const TAHUN_LAMA = 1;

    private const TAHUN_BERJALAN = 2;

    private const KELAS_X1 = 1;

    private const KELAS_XI1 = 2;

    private const MAPEL_MATEMATIKA = 1;

    private const MAPEL_FISIKA = 2;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'guru_mengajar',
            'guru_wali_kelas',
            'guru_biodata',
            'sekolah_mapel',
            'sekolah_kelas',
            'sekolah_tahun_ajaran',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('sekolah_tahun_ajaran', function (Blueprint $table): void {
            $table->id();
            $table->string('nama', 9);
            $table->boolean('sedang_berjalan')->default(false);
            $table->timestamps();
        });
        Schema::create('sekolah_kelas', function (Blueprint $table): void {
            $table->id();
            $table->string('nama', 30);
            $table->string('tingkat', 3);
            $table->timestamps();
        });
        Schema::create('sekolah_mapel', function (Blueprint $table): void {
            $table->id();
            $table->string('kode', 3);
            $table->string('nama', 150);
            $table->timestamps();
        });
        Schema::create('guru_biodata', function (Blueprint $table): void {
            $table->id();
            $table->string('nama', 150);
            $table->string('nip', 30)->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });
        Schema::create('guru_mengajar', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tahun_ajaran_id');
            $table->unsignedBigInteger('guru_id');
            $table->unsignedBigInteger('kelas_id');
            $table->unsignedBigInteger('mapel_id');
            $table->timestamps();
        });
        Schema::create('guru_wali_kelas', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tahun_ajaran_id');
            $table->unsignedBigInteger('guru_id');
            $table->unsignedBigInteger('kelas_id');
            $table->timestamps();
        });

        DB::table('sekolah_tahun_ajaran')->insert([
            ['id' => self::TAHUN_LAMA, 'nama' => '2024/2025', 'sedang_berjalan' => false],
            ['id' => self::TAHUN_BERJALAN, 'nama' => '2025/2026', 'sedang_berjalan' => true],
        ]);
        DB::table('sekolah_kelas')->insert([
            ['id' => self::KELAS_X1, 'nama' => 'X-1', 'tingkat' => 'X'],
            ['id' => self::KELAS_XI1, 'nama' => 'XI-1', 'tingkat' => 'XI'],
        ]);
        DB::table('sekolah_mapel')->insert([
            ['id' => self::MAPEL_MATEMATIKA, 'kode' => 'mtk', 'nama' => 'Matematika'],
            ['id' => self::MAPEL_FISIKA, 'kode' => 'fis', 'nama' => 'Fisika'],
        ]);
    }

    protected function tearDown(): void
    {
        foreach ([
            'guru_mengajar',
            'guru_wali_kelas',
            'guru_biodata',
            'sekolah_mapel',
            'sekolah_kelas',
            'sekolah_tahun_ajaran',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_teacher_can_enter_only_assigned_subjects_and_classes_for_current_year(): void
    {
        $guru = $this->buatGuru('Guru Mapel');
        $this->tugaskanMapel($guru, self::TAHUN_BERJALAN, self::KELAS_X1, self::MAPEL_MATEMATIKA);
        $akses = app(AksesGuru::class);

        $this->assertTrue($akses->bolehIsiNilai($guru, self::KELAS_X1, self::MAPEL_MATEMATIKA));
        $this->assertFalse($akses->bolehIsiNilai($guru, self::KELAS_X1, self::MAPEL_FISIKA));
        $this->assertFalse($akses->bolehIsiNilai($guru, self::KELAS_XI1, self::MAPEL_MATEMATIKA));
        $this->assertSame(
            [self::MAPEL_MATEMATIKA],
            $akses->mapelDiajar($guru, self::KELAS_X1)->modelKeys()
        );
    }

    public function test_homeroom_teacher_can_manage_only_assigned_class(): void
    {
        $guru = $this->buatGuru('Wali Kelas');
        $this->tugaskanWali($guru, self::TAHUN_BERJALAN, self::KELAS_X1);
        $akses = app(AksesGuru::class);

        $this->assertTrue($akses->bolehIsiKehadiranEkskul($guru, self::KELAS_X1));
        $this->assertTrue($akses->bolehKenaikanKelas($guru, self::KELAS_X1));
        $this->assertFalse($akses->bolehIsiKehadiranEkskul($guru, self::KELAS_XI1));
        $this->assertFalse($akses->bolehKenaikanKelas($guru, self::KELAS_XI1));
        $this->assertTrue($akses->adalahWali($guru));
    }

    public function test_teacher_with_both_assignments_has_both_sets_of_permissions(): void
    {
        $guru = $this->buatGuru('Guru Rangkap');
        $this->tugaskanMapel($guru, self::TAHUN_BERJALAN, self::KELAS_X1, self::MAPEL_MATEMATIKA);
        $this->tugaskanWali($guru, self::TAHUN_BERJALAN, self::KELAS_X1);
        $akses = app(AksesGuru::class);

        $this->assertTrue($akses->adalahGuruMapel($guru));
        $this->assertTrue($akses->adalahWali($guru));
        $this->assertTrue($akses->bolehIsiNilai($guru, self::KELAS_X1, self::MAPEL_MATEMATIKA));
        $this->assertTrue($akses->bolehIsiKehadiranEkskul($guru, self::KELAS_X1));
        $this->assertTrue($akses->bolehKenaikanKelas($guru, self::KELAS_X1));
    }

    public function test_assignments_from_previous_year_do_not_grant_access(): void
    {
        $guru = $this->buatGuru('Guru Lama');
        $this->tugaskanMapel($guru, self::TAHUN_LAMA, self::KELAS_X1, self::MAPEL_MATEMATIKA);
        $this->tugaskanWali($guru, self::TAHUN_LAMA, self::KELAS_X1);
        $akses = app(AksesGuru::class);

        $this->assertFalse($akses->adalahGuruMapel($guru));
        $this->assertFalse($akses->adalahWali($guru));
        $this->assertFalse($akses->bolehIsiNilai($guru, self::KELAS_X1, self::MAPEL_MATEMATIKA));
        $this->assertFalse($akses->bolehIsiKehadiranEkskul($guru, self::KELAS_X1));
        $this->assertFalse($akses->bolehKenaikanKelas($guru, self::KELAS_X1));
        $this->assertEmpty($akses->kelasDiajar($guru));
        $this->assertEmpty($akses->kelasWali($guru));
    }

    public function test_no_current_school_year_denies_teacher_but_admin_bypasses_checks(): void
    {
        $guru = $this->buatGuru('Guru Tanpa Tahun Aktif');
        $this->tugaskanMapel($guru, self::TAHUN_BERJALAN, self::KELAS_X1, self::MAPEL_MATEMATIKA);
        $this->tugaskanWali($guru, self::TAHUN_BERJALAN, self::KELAS_X1);
        SekolahTahunAjaran::query()->where('sedang_berjalan', true)->update(['sedang_berjalan' => false]);
        $admin = new Pengguna([
            'peran' => 'admin',
        ]);
        $akses = app(AksesGuru::class);

        $this->assertFalse($akses->bolehIsiNilai($guru, self::KELAS_X1, self::MAPEL_MATEMATIKA));
        $this->assertFalse($akses->bolehIsiKehadiranEkskul($guru, self::KELAS_X1));
        $this->assertFalse($akses->bolehKenaikanKelas($guru, self::KELAS_X1));
        $this->assertTrue($akses->bolehIsiNilai($admin, 999, 999));
        $this->assertTrue($akses->bolehIsiKehadiranEkskul($admin, 999));
        $this->assertTrue($akses->bolehKenaikanKelas($admin, 999));
        $this->assertTrue($akses->adalahGuruMapel($admin));
        $this->assertTrue($akses->adalahWali($admin));
    }

    public function test_logged_in_teacher_resolves_to_linked_biodata_and_other_roles_resolve_to_null(): void
    {
        $guru = $this->buatGuru('Guru Tertaut');
        $penggunaGuru = new Pengguna([
            'peran' => 'guru',
            'guru_id' => $guru->id,
        ]);
        $penggunaGuru->setRelation('guru', $guru);
        $penggunaAdmin = new Pengguna([
            'peran' => 'admin',
        ]);
        $akses = app(AksesGuru::class);

        $this->assertSame($guru->id, $akses->guruDariPengguna($penggunaGuru)?->id);
        $this->assertNull($akses->guruDariPengguna($penggunaAdmin));
        $this->assertNull($akses->guruDariPengguna(null));
    }

    private function buatGuru(string $nama): GuruBiodata
    {
        return GuruBiodata::create([
            'nama' => $nama,
            'nip' => 'NIP-'.str_replace(' ', '-', $nama),
        ]);
    }

    private function tugaskanMapel(GuruBiodata $guru, int $tahunAjaranId, int $kelasId, int $mapelId): void
    {
        GuruMengajar::create([
            'tahun_ajaran_id' => $tahunAjaranId,
            'guru_id' => $guru->id,
            'kelas_id' => $kelasId,
            'mapel_id' => $mapelId,
        ]);
    }

    private function tugaskanWali(GuruBiodata $guru, int $tahunAjaranId, int $kelasId): void
    {
        GuruWaliKelas::create([
            'tahun_ajaran_id' => $tahunAjaranId,
            'guru_id' => $guru->id,
            'kelas_id' => $kelasId,
        ]);
    }
}
