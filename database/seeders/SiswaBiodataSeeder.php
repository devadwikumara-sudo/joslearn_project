<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SiswaBiodataSeeder extends Seeder
{
    public function run(): void
    {
        $siswa = [
            ['nisn' => '9000000001', 'nama_lengkap' => 'Siswa Contoh 01'],
            ['nisn' => '9000000002', 'nama_lengkap' => 'Siswa Contoh 02'],
            ['nisn' => '9000000003', 'nama_lengkap' => 'Siswa Contoh 03'],
        ];

        foreach ($siswa as $data) {
            DB::table('siswa_biodata')->updateOrInsert(
                ['nisn' => $data['nisn']],
                [...$data, 'updated_at' => now()]
            );
        }

        $this->command?->info(count($siswa).' data biodata siswa sintetis berhasil diproses.');
    }
}
