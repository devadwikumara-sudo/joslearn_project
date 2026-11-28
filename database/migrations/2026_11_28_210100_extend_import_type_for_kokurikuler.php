<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const TABLES = [
        'import_format_tersimpan',
        'import_unggahan',
        'import_kamus_kolom',
    ];

    private const TYPES = ['siswa', 'nilai', 'kehadiran', 'ekskul', 'guru', 'kokurikuler'];

    private const PREVIOUS_TYPES = ['siswa', 'nilai', 'kehadiran', 'ekskul', 'guru'];

    public function up(): void
    {
        $this->ensureMysql();

        foreach (self::TABLES as $table) {
            DB::statement("ALTER TABLE `{$table}` MODIFY `jenis` ENUM('".implode("','", self::TYPES)."') NOT NULL");
        }
    }

    public function down(): void
    {
        $this->ensureMysql();

        foreach (self::TABLES as $table) {
            if (DB::table($table)->where('jenis', 'kokurikuler')->exists()) {
                throw new RuntimeException("Tidak dapat membatalkan migrasi: masih ada data kokurikuler pada tabel {$table}.");
            }
        }

        foreach (self::TABLES as $table) {
            DB::statement("ALTER TABLE `{$table}` MODIFY `jenis` ENUM('".implode("','", self::PREVIOUS_TYPES)."') NOT NULL");
        }
    }

    private function ensureMysql(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            throw new RuntimeException('Migrasi jenis import kokurikuler hanya didukung pada MySQL.');
        }
    }
};
