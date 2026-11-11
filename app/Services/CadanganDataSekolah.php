<?php

namespace App\Services;

use App\Models\Admin\CatatanCadangan;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use JsonException;
use RuntimeException;

class CadanganDataSekolah
{
    private const FORMAT = 'josbook';

    private const VERSION = 1;

    private const RETAINED_BACKUPS = 7;

    private const TABLES = [
        'sekolah_identitas',
        'sekolah_pengaturan',
        'sekolah_tahun_ajaran',
        'sekolah_kelas',
        'sekolah_mapel',
        'sekolah_mapel_per_tingkat',
        'catatan_tempat_sampah',
        'guru_biodata',
        'guru_wali_kelas',
        'guru_mengajar',
        'siswa_biodata',
        'siswa_berkas',
        'siswa_riwayat_kelas',
        'siswa_riwayat_mutasi',
        'siswa_nilai',
        'siswa_kehadiran',
        'siswa_ekskul',
        'siswa_pelacakan_alumni',
        'catatan_kenaikan_kelas',
        'import_format_tersimpan',
        'import_kamus_kolom',
        'import_kamus_mapel',
    ];

    private const GENERATED_COLUMNS = [
        'sekolah_tahun_ajaran' => ['penanda_berjalan'],
        'guru_biodata' => ['nip_aktif', 'nuptk_aktif'],
        'siswa_biodata' => ['nisn_aktif', 'nomor_induk_aktif'],
        'siswa_riwayat_kelas' => ['siswa_tahun_aktif'],
    ];

    public function buatCadangan(?int $penggunaId = null): CatatanCadangan
    {
        $tables = [];

        foreach (self::TABLES as $table) {
            if (! Schema::hasTable($table)) {
                throw new RuntimeException("Tabel {$table} tidak tersedia untuk dibuat cadangannya.");
            }

            $columns = $this->columnsFor($table);
            $tables[$table] = DB::table($table)
                ->get($columns)
                ->map(function (object $record) use ($table): array {
                    $row = (array) $record;
                    $actorColumn = match ($table) {
                        'catatan_tempat_sampah' => 'dihapus_oleh',
                        'siswa_riwayat_mutasi' => 'dicatat_oleh',
                        'catatan_kenaikan_kelas' => 'pengguna_id',
                        default => null,
                    };

                    if ($actorColumn !== null && array_key_exists($actorColumn, $row)) {
                        $row[$actorColumn] = null;
                    }

                    return $row;
                })
                ->all();
        }

        $contents = json_encode([
            'format' => self::FORMAT,
            'version' => self::VERSION,
            'created_at' => now()->toIso8601String(),
            'tables' => $tables,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        $filename = 'josbook-cadangan-'.now()->format('Ymd-His').'-'.Str::uuid().'.json';
        $path = 'cadangan/'.$filename;

        if (! Storage::disk('local')->put($path, $contents)) {
            throw new RuntimeException('File cadangan gagal disimpan.');
        }

        $cadangan = CatatanCadangan::query()->create([
            'nama_file' => $filename,
            'lokasi' => $path,
            'ukuran_byte' => strlen($contents),
            'dibuat_oleh' => $penggunaId,
            'created_at' => now(),
        ]);

        $this->hapusCadanganLama();

        return $cadangan;
    }

    public function pulihkan(string $contents, ?int $penggunaId = null): CatatanCadangan
    {
        $tables = $this->decodeAndValidate($contents);
        $restorePoint = $this->buatCadangan($penggunaId);

        DB::transaction(function () use ($tables): void {
            foreach (array_reverse(self::TABLES) as $table) {
                $this->hapusBarisDiLuarCadangan($table, $tables[$table]);
            }

            foreach (self::TABLES as $table) {
                foreach (array_chunk($tables[$table], 200) as $rows) {
                    $columns = array_keys($rows[0]);
                    DB::table($table)->upsert(
                        $rows,
                        ['id'],
                        array_values(array_diff($columns, ['id']))
                    );
                }
            }
        });

        return $restorePoint;
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function decodeAndValidate(string $contents): array
    {
        try {
            $snapshot = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw ValidationException::withMessages([
                'backup_file' => 'File cadangan bukan JSON yang valid.',
            ]);
        }

        if (! is_array($snapshot) || array_is_list($snapshot)) {
            $this->invalidBackup();
        }

        $expectedKeys = ['format', 'version', 'created_at', 'tables'];
        $actualKeys = array_keys($snapshot);
        sort($expectedKeys);
        sort($actualKeys);

        if (
            $actualKeys !== $expectedKeys
            || $snapshot['format'] !== self::FORMAT
            || $snapshot['version'] !== self::VERSION
            || ! is_string($snapshot['created_at'])
            || ! is_array($snapshot['tables'])
        ) {
            $this->invalidBackup();
        }

        $expectedTables = self::TABLES;
        $actualTables = array_keys($snapshot['tables']);
        sort($expectedTables);
        sort($actualTables);

        if ($actualTables !== $expectedTables) {
            $this->invalidBackup();
        }

        foreach (self::TABLES as $table) {
            $rows = $snapshot['tables'][$table];
            if (! is_array($rows) || ! array_is_list($rows)) {
                $this->invalidBackup();
            }

            $expectedColumns = $this->columnsFor($table);
            sort($expectedColumns);
            $seenIds = [];

            foreach ($rows as $row) {
                if (! is_array($row) || array_is_list($row)) {
                    $this->invalidBackup();
                }

                $actualColumns = array_keys($row);
                sort($actualColumns);

                if ($actualColumns !== $expectedColumns || ! array_key_exists('id', $row)) {
                    $this->invalidBackup();
                }

                foreach ($row as $value) {
                    if (! is_scalar($value) && $value !== null) {
                        $this->invalidBackup();
                    }
                }

                $id = (string) $row['id'];
                if (isset($seenIds[$id])) {
                    $this->invalidBackup();
                }
                $seenIds[$id] = true;
            }
        }

        return $snapshot['tables'];
    }

    private function columnsFor(string $table): array
    {
        $generatedColumns = self::GENERATED_COLUMNS[$table] ?? [];

        return array_values(array_filter(
            Schema::getColumnListing($table),
            static fn (string $column): bool => ! in_array($column, $generatedColumns, true)
                && preg_match('/password|token|secret|hash/i', $column) !== 1
        ));
    }

    /**
     * @param  array<int, array<string, mixed>>  $snapshotRows
     */
    private function hapusBarisDiLuarCadangan(string $table, array $snapshotRows): void
    {
        $snapshotIds = array_fill_keys(
            array_map(static fn (array $row): string => (string) $row['id'], $snapshotRows),
            true
        );
        $query = DB::table($table);
        $this->preserveDataForAccounts($query, $table);
        $this->preserveExternalReferences($query, $table);

        $query->select('id')->chunkById(500, function ($records) use ($snapshotIds, $table): void {
            $idsToDelete = $records->pluck('id')
                ->reject(static fn ($id): bool => isset($snapshotIds[(string) $id]))
                ->values();

            if ($idsToDelete->isNotEmpty()) {
                DB::table($table)->whereIn('id', $idsToDelete->all())->delete();
            }
        });
    }

    private function preserveExternalReferences(Builder $query, string $table): void
    {
        $references = match ($table) {
            'sekolah_tahun_ajaran' => [
                ['siswa_riwayat_kelas', 'tahun_ajaran_id'],
                ['guru_wali_kelas', 'tahun_ajaran_id'],
                ['guru_mengajar', 'tahun_ajaran_id'],
                ['catatan_kenaikan_kelas', 'tahun_ajaran_asal_id'],
                ['catatan_kenaikan_kelas', 'tahun_ajaran_tujuan_id'],
                ['import_unggahan', 'tahun_ajaran_id'],
            ],
            'sekolah_kelas' => [
                ['siswa_riwayat_kelas', 'kelas_id'],
                ['guru_wali_kelas', 'kelas_id'],
                ['guru_mengajar', 'kelas_id'],
                ['siswa_riwayat_mutasi', 'kelas_asal_id'],
                ['siswa_riwayat_mutasi', 'kelas_tujuan_id'],
                ['catatan_kenaikan_kelas', 'kelas_asal_id'],
                ['import_unggahan', 'kelas_id'],
            ],
            'sekolah_mapel' => [
                ['sekolah_mapel_per_tingkat', 'mapel_id'],
                ['siswa_nilai', 'mapel_id'],
                ['guru_mengajar', 'mapel_id'],
                ['import_kamus_mapel', 'mapel_id'],
            ],
            'import_format_tersimpan' => [
                ['import_unggahan', 'format_tersimpan_id'],
            ],
            default => [],
        };

        foreach ($references as [$referencingTable, $foreignKey]) {
            if (
                Schema::hasTable($referencingTable)
                && Schema::hasColumn($referencingTable, $foreignKey)
            ) {
                $query->whereNotIn(
                    'id',
                    DB::table($referencingTable)
                        ->whereNotNull($foreignKey)
                        ->select($foreignKey)
                );
            }
        }
    }

    private function preserveDataForAccounts(Builder $query, string $table): void
    {
        if (! Schema::hasTable('akun_pengguna')) {
            return;
        }

        $studentIds = static fn (): Builder => DB::table('akun_pengguna')
            ->whereNotNull('siswa_id')
            ->select('siswa_id');
        $teacherIds = static fn (): Builder => DB::table('akun_pengguna')
            ->whereNotNull('guru_id')
            ->select('guru_id');
        $studentHistoryIds = static fn (): Builder => DB::table('siswa_riwayat_kelas')
            ->whereIn('siswa_id', $studentIds())
            ->select('id');

        match ($table) {
            'siswa_biodata' => $query->whereNotIn('id', $studentIds()),
            'guru_biodata' => $query->whereNotIn('id', $teacherIds()),
            'siswa_riwayat_kelas' => $query->whereNotIn('siswa_id', $studentIds()),
            'siswa_berkas', 'siswa_riwayat_mutasi', 'siswa_pelacakan_alumni' => $query
                ->whereNotIn('siswa_id', $studentIds()),
            'siswa_nilai', 'siswa_kehadiran', 'siswa_ekskul' => $query
                ->whereNotIn('riwayat_kelas_id', $studentHistoryIds()),
            'guru_wali_kelas', 'guru_mengajar' => $query->whereNotIn('guru_id', $teacherIds()),
            'catatan_tempat_sampah' => $query
                ->whereNotIn('id', DB::table('siswa_biodata')
                    ->whereIn('id', $studentIds())
                    ->whereNotNull('tempat_sampah_id')
                    ->select('tempat_sampah_id'))
                ->whereNotIn('id', DB::table('guru_biodata')
                    ->whereIn('id', $teacherIds())
                    ->whereNotNull('tempat_sampah_id')
                    ->select('tempat_sampah_id'))
                ->whereNotIn('id', DB::table('siswa_riwayat_kelas')
                    ->whereIn('siswa_id', $studentIds())
                    ->whereNotNull('tempat_sampah_id')
                    ->select('tempat_sampah_id')),
            default => null,
        };
    }

    private function hapusCadanganLama(): void
    {
        $retainedIds = CatatanCadangan::query()
            ->orderByDesc('id')
            ->limit(self::RETAINED_BACKUPS)
            ->select('id');
        $cadanganLama = CatatanCadangan::query()
            ->whereNotIn('id', $retainedIds)
            ->get();

        foreach ($cadanganLama as $cadangan) {
            if (
                Storage::disk('local')->exists($cadangan->lokasi)
                && ! Storage::disk('local')->delete($cadangan->lokasi)
            ) {
                throw new RuntimeException("File cadangan lama {$cadangan->nama_file} gagal dihapus.");
            }

            $cadangan->delete();
        }
    }

    private function invalidBackup(): never
    {
        throw ValidationException::withMessages([
            'backup_file' => 'Struktur file cadangan tidak sesuai atau tidak didukung.',
        ]);
    }
}
