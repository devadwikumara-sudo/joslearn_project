<?php
namespace Database\Seeders;

use App\Models\Auth\Pengguna;
use App\Models\SiswaBiodata;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

/**
 * Membuat akun login untuk semua siswa yang belum punya akun.
 *   username = NISN
 *   password awal = tanggal lahir format ddmmyyyy (contoh 05032008)
 *   wajib_ganti_password = 1 (siswa diminta ganti password)
 *
 * Jalankan:  php artisan db:seed --class=AkunSiswaSeeder
 * Aman dijalankan berulang: siswa yang sudah punya akun dilewati.
 */
class AkunSiswaSeeder extends Seeder
{
    public function run(): void
    {
        $dibuat = 0;
        $dilewati = 0;

        SiswaBiodata::query()->orderBy('id')->chunkById(100, function ($kumpulan) use (&$dibuat, &$dilewati) {
            foreach ($kumpulan as $siswa) {
                $sudahAda = Pengguna::where('siswa_id', $siswa->id)
                    ->orWhere('username', $siswa->nisn)
                    ->exists();

                if (empty($siswa->tanggal_lahir) || $sudahAda) {
                    $dilewati++;
                    continue;
                }

                Pengguna::create([
                    'peran'                => 'siswa',
                    'username'             => $siswa->nisn,
                    'email'                => null,
                    'password'             => Hash::make(Carbon::parse($siswa->tanggal_lahir)->format('dmY')),
                    'aktif'                => true,
                    'siswa_id'             => $siswa->id,
                    'wajib_ganti_password' => true,
                ]);

                $dibuat++;
            }
        });

        $this->command?->info("Akun siswa dibuat: {$dibuat}, dilewati: {$dilewati}");
    }
}