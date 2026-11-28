<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('siswa_biodata', function (Blueprint $table): void {
            $table->string('foto_masuk_2x3')->nullable();
            $table->string('foto_masuk_3x4')->nullable();
            $table->string('foto_keluar_2x3')->nullable();
            $table->string('foto_keluar_3x4')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('siswa_biodata', function (Blueprint $table): void {
            $table->dropColumn([
                'foto_masuk_2x3',
                'foto_masuk_3x4',
                'foto_keluar_2x3',
                'foto_keluar_3x4',
            ]);
        });
    }
};
