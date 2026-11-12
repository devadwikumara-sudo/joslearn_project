<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('sekolah_identitas')->insertOrIgnore([
            'id' => 1,
            'nama' => 'SMA Negeri 1 Rejoso Nganjuk',
            'npsn' => '20539744',
            'alamat' => 'Jl. Raya Rejoso No. 1, Rejoso, Nganjuk',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if (DB::table('akun_pengguna')->where('peran', 'admin')->exists()) {
            return;
        }

        $password = config('auth.admin_initial_password');
        if (! is_string($password) || $password === '') {
            if (app()->environment('production')) {
                throw new RuntimeException('Atur ADMIN_INITIAL_PASSWORD sebelum menjalankan seeder produksi.');
            }

            return;
        }

        if (strlen($password) < 12) {
            throw new RuntimeException('ADMIN_INITIAL_PASSWORD harus memiliki minimal 12 karakter.');
        }

        DB::table('akun_pengguna')->insert([
            'peran' => 'admin',
            'username' => config('auth.admin_initial_username', 'admin'),
            'email' => config('auth.admin_initial_email') ?: null,
            'password' => Hash::make($password),
            'wajib_ganti_password' => true,
            'aktif' => true,
            'email_diverifikasi_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
