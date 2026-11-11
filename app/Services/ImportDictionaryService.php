<?php

namespace App\Services;

use App\Models\Admin\ImportFormatTersimpan;
use App\Models\Admin\ImportKamusKolom;
use App\Models\Admin\ImportKamusMapel;
use App\Models\Auth\Pengguna;
use App\Models\SekolahMapel;
use Illuminate\Support\Collection;

class ImportDictionaryService
{
    private const TARGET_TO_FIELD = [
        'nama_lengkap' => 'nama',
        'nomor_induk' => 'nis',
        'jenis_kelamin' => 'jk',
        'tempat_lahir' => 'tmp',
        'tanggal_lahir' => 'tgl',
        'tanggal_diterima' => 'tgl_masuk',
        'alamat_jalan' => 'alamat',
        'alamat_rt_rw' => 'rt_rw',
        'alamat_desa' => 'kelurahan',
        'alamat_kecamatan' => 'kecamatan',
        'alamat_kabupaten' => 'kab',
        'alamat_provinsi' => 'prov',
        'alamat_kode_pos' => 'kodepos',
        'nomor_hp' => 'hp',
        'nama_ayah' => 'ayah',
        'nama_ibu' => 'ibu',
        'nama_wali' => 'wali',
        'nomor_peserta_psaj' => 'nopes',
        'sekolah_tujuan' => 'tujuan',
        'kegiatan_setelah_lulus' => 'lanjut',
    ];

    private const FIELD_TO_TARGET = [
        'nama' => 'nama_lengkap',
        'nis' => 'nomor_induk',
        'jk' => 'jenis_kelamin',
        'tmp' => 'tempat_lahir',
        'tgl' => 'tanggal_lahir',
        'tgl_masuk' => 'tanggal_diterima',
        'alamat' => 'alamat_jalan',
        'rt_rw' => 'alamat_rt_rw',
        'kelurahan' => 'alamat_desa',
        'kecamatan' => 'alamat_kecamatan',
        'kab' => 'alamat_kabupaten',
        'prov' => 'alamat_provinsi',
        'kodepos' => 'alamat_kode_pos',
        'hp' => 'nomor_hp',
        'ayah' => 'nama_ayah',
        'ibu' => 'nama_ibu',
        'wali' => 'nama_wali',
        'nopes' => 'nomor_peserta_psaj',
        'tujuan' => 'sekolah_tujuan',
        'lanjut' => 'kegiatan_setelah_lulus',
    ];

    public function normalizeHeader(string $header): string
    {
        $header = mb_strtolower(trim($header));
        $header = preg_replace('/\([^)]*\)/u', ' ', $header) ?? $header;
        $header = preg_replace('/[^a-z0-9]+/u', ' ', $header) ?? $header;

        return trim(preg_replace('/\s+/u', ' ', $header) ?? $header);
    }

    /**
     * @return array{columns: array<string, string>, subjects: array<string, string>}
     */
    public function clientDictionaries(string $type): array
    {
        $columns = [];
        ImportKamusKolom::query()
            ->where('jenis', $type)
            ->where('aktif', true)
            ->orderByRaw("CASE WHEN asal = 'admin' THEN 1 ELSE 0 END")
            ->get(['judul_di_excel', 'kolom_tujuan'])
            ->each(function (ImportKamusKolom $entry) use (&$columns): void {
                $header = $this->normalizeHeader($entry->judul_di_excel);
                $columns[$header] = self::TARGET_TO_FIELD[$entry->kolom_tujuan] ?? $entry->kolom_tujuan;
            });

        $subjects = [];
        ImportKamusMapel::query()
            ->join('sekolah_mapel', 'sekolah_mapel.id', '=', 'import_kamus_mapel.mapel_id')
            ->whereNotNull('sekolah_mapel.kode')
            ->get(['import_kamus_mapel.nama_di_excel', 'sekolah_mapel.kode'])
            ->each(function (object $entry) use (&$subjects): void {
                $alias = $this->normalizeHeader($entry->nama_di_excel);
                if ($alias !== '') {
                    $subjects[$alias] = strtolower($entry->kode);
                }
            });

        return ['columns' => $columns, 'subjects' => $subjects];
    }

    /**
     * @param  array<int, array{name?: string, headers: array<int, string>}>  $sheets
     * @return array<int, array{name: string, fingerprint: string, format_id: int|null, mapping: array<int|string, string>|null}>
     */
    public function findFormats(string $type, array $sheets): array
    {
        return collect($sheets)->map(function (array $sheet) use ($type): array {
            $fingerprint = $this->fingerprint($sheet['headers']);
            $format = ImportFormatTersimpan::query()
                ->where('jenis', $type)
                ->where('kode_struktur', $fingerprint)
                ->first();

            if ($format !== null) {
                $format->increment('dipakai_kali');
                $format->forceFill(['terakhir_dipakai_at' => now()])->save();
            }

            return [
                'name' => (string) ($sheet['name'] ?? ''),
                'fingerprint' => $fingerprint,
                'format_id' => $format?->id,
                'mapping' => $format?->pemetaan_kolom['mapping'] ?? null,
            ];
        })->all();
    }

    /**
     * @param  array<int, array{name?: string, headers: array<int, string>, mapping: array<int|string, string>, header_row?: int}>  $corrections
     * @return Collection<int, ImportFormatTersimpan>
     */
    public function learnCorrections(string $type, array $corrections, Pengguna $user, string $fileName): Collection
    {
        return collect($corrections)->map(function (array $correction) use ($type, $user, $fileName): ImportFormatTersimpan {
            $mapping = [];
            foreach ($correction['mapping'] as $index => $field) {
                if (is_numeric($index) && is_string($field)
                    && preg_match('/^(?:[a-z][a-z0-9_]{0,59}|m:[a-z0-9_]{1,57}|new)$/', $field)) {
                    $mapping[(string) (int) $index] = $field;
                }
            }

            $headers = array_map(fn ($header): string => trim((string) $header), $correction['headers']);
            $structure = [
                'headers' => array_map(fn (string $header): string => $this->normalizeHeader($header), $headers),
                'mapping' => $mapping,
            ];

            foreach ($correction['corrected'] ?? $mapping as $index => $field) {
                if (! is_string($field) || ! preg_match('/^[a-z][a-z0-9_]{0,59}$/', $field)) {
                    continue;
                }
                $header = $headers[(int) $index] ?? '';
                $normalizedHeader = $this->normalizeHeader($header);
                if ($normalizedHeader === '') {
                    continue;
                }

                ImportKamusKolom::query()->updateOrCreate(
                    [
                        'jenis' => $type,
                        'milik_siapa' => 'umum',
                        'judul_di_excel' => $normalizedHeader,
                    ],
                    [
                        'kolom_tujuan' => self::FIELD_TO_TARGET[$field] ?? $field,
                        'asal' => 'admin',
                        'aktif' => true,
                        'dibuat_oleh' => $user->id,
                    ]
                );
            }

            return ImportFormatTersimpan::query()->updateOrCreate(
                [
                    'jenis' => $type,
                    'kode_struktur' => $this->fingerprint($headers),
                ],
                [
                    'nama' => mb_substr($fileName !== '' ? $fileName : (string) ($correction['name'] ?? 'Format import'), 0, 100),
                    'nama_sheet' => mb_substr((string) ($correction['name'] ?? ''), 0, 100),
                    'baris_judul' => max(1, (int) ($correction['header_row'] ?? 1)),
                    'pemetaan_kolom' => $structure,
                    'dibuat_oleh' => $user->id,
                ]
            );
        });
    }

    public function learnSubjectAlias(string $name, SekolahMapel $subject): void
    {
        $alias = trim(preg_replace('/\s+/u', ' ', $name) ?? $name);
        if ($alias === '') {
            return;
        }

        ImportKamusMapel::query()->updateOrCreate(
            ['nama_di_excel' => mb_strtolower($alias)],
            ['mapel_id' => $subject->id, 'created_at' => now()]
        );
    }

    public function findSubject(string $name): ?SekolahMapel
    {
        $normalized = $this->normalizeHeader($name);
        if ($normalized === '') {
            return null;
        }

        $alias = ImportKamusMapel::query()
            ->join('sekolah_mapel', 'sekolah_mapel.id', '=', 'import_kamus_mapel.mapel_id')
            ->get(['import_kamus_mapel.nama_di_excel', 'sekolah_mapel.id'])
            ->first(fn (object $entry): bool => $this->normalizeHeader($entry->nama_di_excel) === $normalized);
        if ($alias !== null) {
            return SekolahMapel::find((int) $alias->id);
        }

        return SekolahMapel::query()
            ->whereRaw('LOWER(kode) = ?', [$normalized])
            ->orWhereRaw('LOWER(nama) = ?', [mb_strtolower(trim($name))])
            ->first();
    }

    public function fingerprint(array $headers): string
    {
        $normalizedHeaders = array_map(
            fn ($header): string => $this->normalizeHeader((string) $header),
            array_values($headers)
        );

        return hash('sha256', json_encode($normalizedHeaders, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]');
    }
}
