<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Identitas sekolah awal
        DB::table('sekolah_identitas')->insertOrIgnore([
            'id'                  => 1,
            'nama'                => 'SMA Negeri 1 Contoh',
            'npsn'                => '12345678',
            'alamat'              => 'Jl. Pendidikan No. 1',
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        // Akun Admin
        DB::table('akun_pengguna')->insertOrIgnore([
            'peran'               => 'admin',
            'username'            => 'admin',
            'email'               => 'admin@josbook.test',
            'password'            => Hash::make('admin123'),
            'wajib_ganti_password'=> 0,
            'aktif'               => 1,
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);
    }
}
