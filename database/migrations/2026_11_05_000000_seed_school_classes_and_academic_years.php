<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $classes = [];

        foreach (['X', 'XI', 'XII'] as $tingkat) {
            for ($rombel = 1; $rombel <= 9; $rombel++) {
                $classes[] = [
                    'nama' => "{$tingkat}-{$rombel}",
                    'tingkat' => $tingkat,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('sekolah_kelas')->insertOrIgnore($classes);

        DB::table('sekolah_tahun_ajaran')->insertOrIgnore([
            [
                'nama' => '2024/2025',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        // Keep these reference rows because student histories may already use them.
    }
};
