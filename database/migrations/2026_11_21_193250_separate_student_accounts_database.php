<?php

use App\Models\Auth\AkunSiswa;
use App\Models\Auth\Pengguna;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['akun_pengguna', 'akun_notifikasi', 'akun_perangkat_login', 'personal_access_tokens'] as $table) {
            if (! Schema::hasTable($table)) {
                throw new RuntimeException("Tabel {$table} tidak ditemukan pada database utama.");
            }
        }

        if (! Schema::hasTable('akun_siswa')) {
            Schema::create('akun_siswa', function (Blueprint $table): void {
                $table->id();
                $table->string('username', 50)->unique();
                $table->string('email', 120)->nullable()->unique();
                $table->timestamp('email_diverifikasi_at')->nullable();
                $table->string('password');
                $table->boolean('wajib_ganti_password')->default(false);
                $table->boolean('aktif')->default(true);
                $table->foreignId('siswa_id')->unique()->constrained('siswa_biodata')->cascadeOnDelete();
                $table->timestamp('terakhir_login_at')->nullable();
                $table->rememberToken();
                $table->timestamps();
            });
        }

        $this->addStudentReferences('akun_notifikasi', 'fk_akun_notifikasi_akun_siswa');
        $this->addStudentReferences('akun_perangkat_login', 'fk_akun_perangkat_login_akun_siswa');

        DB::transaction(function (): void {
            $akunSiswa = DB::table('akun_pengguna')
                ->where('peran', 'siswa')
                ->orderBy('id')
                ->get();
            $accountIds = $akunSiswa->pluck('id')->map(fn ($id): int => (int) $id)->all();

            foreach ($akunSiswa as $akun) {
                $row = [
                    'id' => $akun->id,
                    'username' => $akun->username,
                    'email' => $akun->email,
                    'email_diverifikasi_at' => $akun->email_diverifikasi_at,
                    'password' => $akun->password,
                    'wajib_ganti_password' => $akun->wajib_ganti_password,
                    'aktif' => $akun->aktif,
                    'siswa_id' => $akun->siswa_id,
                    'terakhir_login_at' => $akun->terakhir_login_at,
                    'remember_token' => $akun->remember_token,
                    'created_at' => $akun->created_at,
                    'updated_at' => $akun->updated_at,
                ];

                DB::table('akun_siswa')->insertOrIgnore($row);
                $this->assertCopiedAccount($row);
            }

            if ($accountIds === []) {
                return;
            }

            foreach (['akun_notifikasi', 'akun_perangkat_login'] as $table) {
                DB::table($table)
                    ->whereIn('pengguna_id', $accountIds)
                    ->update(['akun_siswa_id' => DB::raw('pengguna_id')]);

                DB::table($table)
                    ->whereIn('pengguna_id', $accountIds)
                    ->update(['pengguna_id' => null]);
            }

            DB::table('personal_access_tokens')
                ->where('tokenable_type', Pengguna::class)
                ->whereIn('tokenable_id', $accountIds)
                ->update(['tokenable_type' => AkunSiswa::class]);

            DB::table('akun_pengguna')
                ->where('peran', 'siswa')
                ->whereIn('id', $accountIds)
                ->delete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('akun_siswa')) {
            return;
        }

        DB::transaction(function (): void {
            DB::table('akun_siswa')->orderBy('id')->chunkById(100, function ($accounts): void {
                foreach ($accounts as $account) {
                    if (DB::table('akun_pengguna')->where('username', $account->username)->exists()
                        || ($account->email !== null && DB::table('akun_pengguna')->where('email', $account->email)->exists())) {
                        throw new RuntimeException('Username atau email akun siswa berbenturan dengan akun admin atau guru.');
                    }

                    $penggunaId = DB::table('akun_pengguna')->insertGetId([
                        'peran' => 'siswa',
                        'username' => $account->username,
                        'email' => $account->email,
                        'email_diverifikasi_at' => $account->email_diverifikasi_at,
                        'password' => $account->password,
                        'wajib_ganti_password' => $account->wajib_ganti_password,
                        'aktif' => $account->aktif,
                        'guru_id' => null,
                        'siswa_id' => $account->siswa_id,
                        'terakhir_login_at' => $account->terakhir_login_at,
                        'remember_token' => $account->remember_token,
                        'created_at' => $account->created_at,
                        'updated_at' => $account->updated_at,
                    ]);

                    foreach (['akun_notifikasi', 'akun_perangkat_login'] as $table) {
                        DB::table($table)
                            ->where('akun_siswa_id', $account->id)
                            ->update([
                                'pengguna_id' => $penggunaId,
                                'akun_siswa_id' => null,
                            ]);
                    }

                    DB::table('personal_access_tokens')
                        ->where('tokenable_type', AkunSiswa::class)
                        ->where('tokenable_id', $account->id)
                        ->update([
                            'tokenable_type' => Pengguna::class,
                            'tokenable_id' => $penggunaId,
                        ]);
                }
            });
        });

        $this->removeStudentReferences('akun_perangkat_login', 'fk_akun_perangkat_login_akun_siswa');
        $this->removeStudentReferences('akun_notifikasi', 'fk_akun_notifikasi_akun_siswa');

        Schema::drop('akun_siswa');
    }

    private function addStudentReferences(string $tableName, string $foreignKeyName): void
    {
        Schema::table($tableName, function (Blueprint $table): void {
            $table->unsignedBigInteger('pengguna_id')->nullable()->change();
        });

        if (Schema::hasColumn($tableName, 'akun_siswa_id')) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($foreignKeyName): void {
            $table->unsignedBigInteger('akun_siswa_id')->nullable();
            $table->foreign('akun_siswa_id', $foreignKeyName)
                ->references('id')
                ->on('akun_siswa')
                ->cascadeOnDelete();
        });
    }

    private function removeStudentReferences(string $tableName, string $foreignKeyName): void
    {
        if (! Schema::hasColumn($tableName, 'akun_siswa_id')) {
            return;
        }

        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            $this->rebuildSqliteReferenceTable($tableName);

            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($foreignKeyName): void {
            $table->dropForeign($foreignKeyName);
            $table->dropColumn('akun_siswa_id');
            $table->unsignedBigInteger('pengguna_id')->nullable(false)->change();
        });
    }

    private function rebuildSqliteReferenceTable(string $tableName): void
    {
        $temporaryTable = $tableName.'_restored';

        Schema::dropIfExists($temporaryTable);
        Schema::create($temporaryTable, function (Blueprint $table) use ($tableName): void {
            $table->id();
            $table->unsignedBigInteger('pengguna_id');

            if ($tableName === 'akun_notifikasi') {
                $table->string('judul', 150);
                $table->string('isi', 500)->nullable();
                $table->string('jenis', 20)->default('info');
                $table->timestamp('dibaca_at')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->index(['pengguna_id', 'dibaca_at'], 'notifikasi_restored_pengguna_index');
            } else {
                $table->unsignedBigInteger('token_id')->nullable();
                $table->string('nama_perangkat', 120)->nullable();
                $table->string('alamat_ip', 45)->nullable();
                $table->timestamp('terakhir_aktif_at')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->index('pengguna_id', 'perangkat_restored_pengguna_index');
            }

            $table->foreign('pengguna_id')
                ->references('id')
                ->on('akun_pengguna')
                ->cascadeOnDelete();
        });

        $columns = $tableName === 'akun_notifikasi'
            ? ['id', 'pengguna_id', 'judul', 'isi', 'jenis', 'dibaca_at', 'created_at']
            : ['id', 'pengguna_id', 'token_id', 'nama_perangkat', 'alamat_ip', 'terakhir_aktif_at', 'created_at'];

        DB::table($tableName)->orderBy('id')->chunkById(100, function ($records) use ($columns, $temporaryTable): void {
            DB::table($temporaryTable)->insert(
                $records->map(fn (object $record): array => array_intersect_key(
                    (array) $record,
                    array_flip($columns)
                ))->all()
            );
        });

        Schema::drop($tableName);
        Schema::rename($temporaryTable, $tableName);
    }

    /**
     * @param  array<string, mixed>  $expected
     */
    private function assertCopiedAccount(array $expected): void
    {
        $actual = DB::table('akun_siswa')->where('id', $expected['id'])->first();

        if (! $actual) {
            throw new RuntimeException('Gagal memverifikasi pemindahan akun siswa.');
        }

        foreach ($expected as $column => $value) {
            $actualValue = $actual->{$column};

            if (($value === null && $actualValue !== null)
                || ($value !== null && (string) $actualValue !== (string) $value)) {
                throw new RuntimeException('Data akun siswa tidak cocok setelah dipindahkan.');
            }
        }
    }
};
