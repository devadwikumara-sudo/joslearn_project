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
            'nama'                => 'SMA Negeri 1 Rejoso Nganjuk',
            'npsn'                => '20539744',
            'alamat'              => 'Jl. Raya Rejoso No. 1, Rejoso, Nganjuk',
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
            'email_diverifikasi_at'=> now(),
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        // Biodata Guru default
        $guruId = DB::table('guru_biodata')->where('nip', '198501152010011002')->value('id');
        if (! $guruId) {
            $guruId = DB::table('guru_biodata')->insertGetId([
                'nama'               => 'Budi Santoso, S.Pd.',
                'nip'                => '198501152010011002',
                'email'              => 'budi.santoso@sman1rejoso.sch.id',
                'jenis_kelamin'      => 'L',
                'status_kepegawaian' => 'PNS',
                'created_at'         => now(),
                'updated_at'         => now(),
            ]);
        }

        // Akun Guru default
        DB::table('akun_pengguna')->insertOrIgnore([
            'peran'               => 'guru',
            'username'            => '198501152010011002',
            'email'               => 'budi.santoso@sman1rejoso.sch.id',
            'password'            => Hash::make('guru123'),
            'guru_id'             => $guruId,
            'wajib_ganti_password'=> 0,
            'aktif'               => 1,
            'email_diverifikasi_at'=> now(),
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);
    }
}
