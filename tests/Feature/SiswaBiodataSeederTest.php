<?php

namespace Tests\Feature;

use Database\Seeders\SiswaBiodataSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SiswaBiodataSeederTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('siswa_biodata');
        Schema::create('siswa_biodata', function (Blueprint $table): void {
            $table->id();
            $table->string('nisn')->unique();
            $table->string('nama_lengkap');
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('siswa_biodata');

        parent::tearDown();
    }

    public function test_demo_seeder_inserts_only_synthetic_student_records(): void
    {
        app(SiswaBiodataSeeder::class)->run();

        $students = DB::table('siswa_biodata')
            ->orderBy('nisn')
            ->get(['nisn', 'nama_lengkap'])
            ->map(fn (object $student): array => [$student->nisn, $student->nama_lengkap])
            ->all();

        $this->assertSame([
            ['9000000001', 'Siswa Contoh 01'],
            ['9000000002', 'Siswa Contoh 02'],
            ['9000000003', 'Siswa Contoh 03'],
        ], $students);
    }
}
