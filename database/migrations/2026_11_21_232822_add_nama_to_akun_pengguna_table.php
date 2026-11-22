<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('akun_pengguna', function (Blueprint $table): void {
            $table->string('nama', 150)->nullable()->after('username');
        });
    }

    public function down(): void
    {
        Schema::table('akun_pengguna', function (Blueprint $table): void {
            $table->dropColumn('nama');
        });
    }
};
