<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $guruId = DB::table('guru_biodata')->where('nip', '0000000001')->value('id');
        if (! $guruId) {
            $guruId = DB::table('guru_biodata')->insertGetId([
                'nama' => 'Guru Contoh',
                'nip' => '0000000001',
                'email' => 'guru@example.test',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('akun_pengguna')->insertOrIgnore([
            'peran' => 'admin',
            'username' => 'admin',
            'email' => 'admin@example.test',
            'password' => Hash::make('admin123'),
            'wajib_ganti_password' => false,
            'aktif' => true,
            'email_diverifikasi_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('akun_pengguna')->insertOrIgnore([
            'peran' => 'guru',
            'username' => '0000000001',
            'email' => 'guru@example.test',
            'password' => Hash::make('guru123'),
            'guru_id' => $guruId,
            'wajib_ganti_password' => false,
            'aktif' => true,
            'email_diverifikasi_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->call(SiswaBiodataSeeder::class);
    }
}
