<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class JoslearnInitialDataSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        DB::table('roles')->insertOrIgnore([
            ['name' => 'admin', 'label' => 'Admin', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'guru',  'label' => 'Guru',  'created_at' => $now, 'updated_at' => $now],
            ['name' => 'siswa', 'label' => 'Siswa', 'created_at' => $now, 'updated_at' => $now],
        ]);

        $settings = [
            ['key' => 'attendance.qr_ttl_seconds', 'value' => '30', 'description' => 'Masa berlaku token QR yang berputar (detik)'],
            ['key' => 'attendance.checkin_open', 'value' => '06:00', 'description' => 'Jam QR masuk mulai bisa dipindai'],
            ['key' => 'attendance.checkin_late_after', 'value' => '07:15', 'description' => 'Batas terlambat absen masuk (sesuaikan kebijakan sekolah)'],
            ['key' => 'attendance.allow_manual_fix', 'value' => '1', 'description' => 'Izinkan koreksi absensi manual oleh guru'],
            ['key' => 'grade.min_score', 'value' => '0', 'description' => 'Nilai minimum'],
            ['key' => 'grade.max_score', 'value' => '100', 'description' => 'Nilai maksimum'],
            ['key' => 'report.require_all_subjects', 'value' => '1', 'description' => 'Rapor tidak bisa terbit jika ada nilai wajib kosong'],
        ];

        foreach ($settings as $setting) {
            DB::table('settings')->updateOrInsert(
                ['key' => $setting['key']],
                $setting + ['updated_at' => $now]
            );
        }
    }
}
