<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['siswa_nilai', 'siswa_kehadiran', 'siswa_ekskul'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->foreignId('import_unggahan_id')
                    ->nullable()
                    ->constrained('import_unggahan')
                    ->nullOnDelete();
                $table->foreignId('import_terakhir_unggahan_id')
                    ->nullable()
                    ->constrained('import_unggahan')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (['siswa_nilai', 'siswa_kehadiran', 'siswa_ekskul'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropConstrainedForeignId('import_terakhir_unggahan_id');
                $table->dropConstrainedForeignId('import_unggahan_id');
            });
        }
    }
};
