<?php

namespace App\Services;

use App\Models\SekolahKelas;
use App\Models\SiswaBiodata;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class PencocokSiswa
{
    /**
     * @param  array<string, mixed>  $row
     * @return array{
     *     student: ?SiswaBiodata,
     *     metode: 'nisn'|'nis'|'nama',
     *     level: 'ok'|'peringatan'|'error',
     *     pesan: ?string,
     *     kandidat?: array<int, array{nisn: ?string, kelas: string}>
     * }
     */
    public function cocokkan(
        array $row,
        ?int $tahunAjaranId = null,
        ?SekolahKelas $kelas = null,
        ?string $tingkat = null,
        bool $tahunAjaranDipilih = false,
        bool $aktifSaja = false
    ): array {
        $nisnInput = trim((string) ($row['nisn'] ?? ''));
        $nisn = $nisnInput === '' ? null : (preg_replace('/\D/', '', $nisnInput) ?? '');
        $nis = $this->clean($row['nis'] ?? $row['nomor_induk'] ?? null);
        $nama = $this->clean($row['nama'] ?? $row['nama_lengkap'] ?? null);

        if ($nisn !== null && ! preg_match('/^\d{10}$/', $nisn)) {
            return $this->hasil(null, 'nisn', 'error', 'NISN tidak valid; pencocokan dibatalkan agar data tidak masuk ke siswa yang salah.');
        }

        $nisnMatches = $nisn === null ? collect() : SiswaBiodata::query()->where('nisn', $nisn)->get();
        $nisMatches = $nis === null ? collect() : SiswaBiodata::query()->where('nomor_induk', $nis)->get();

        if ($nisnMatches->count() > 1) {
            return $this->hasil(null, 'nisn', 'error', 'NISN terdaftar pada lebih dari satu siswa; baris ini perlu diperiksa.');
        }

        if ($nisMatches->count() > 1) {
            return $this->hasil(null, 'nis', 'error', 'NIS terdaftar pada lebih dari satu siswa; baris ini perlu diperiksa.');
        }

        $siswaNisn = $nisnMatches->first();
        $siswaNis = $nisMatches->first();
        if ($siswaNisn !== null && $siswaNis !== null && $siswaNisn->id !== $siswaNis->id) {
            return $this->hasil(null, 'nisn', 'error', 'NISN dan NIS mengarah ke siswa berbeda; baris ini dilewati.');
        }

        $student = $siswaNisn ?? $siswaNis;
        if ($student !== null) {
            $method = $siswaNisn !== null ? 'nisn' : 'nis';
            $classError = $this->classError($student, $tahunAjaranId, $kelas);
            if ($classError !== null) {
                return $this->hasil(null, $method, 'error', $classError);
            }

            if ($nama === null) {
                return $this->hasil($student, $method, 'ok', null);
            }

            $similarity = $this->nameSimilarity($student->nama_lengkap, $nama);
            if ($similarity < 0.85) {
                return $this->hasil(null, $method, 'error', 'Nama tidak sesuai NISN/NIS; periksa kembali identitas pada file.');
            }

            if ($similarity < 1) {
                return $this->hasil($student, $method, 'peringatan', 'Nama berbeda dari data siswa, tetapi kemiripannya minimal 85%. Periksa kembali sebelum melanjutkan.');
            }

            return $this->hasil($student, $method, 'ok', null);
        }

        if ($nisn !== null || $nis !== null) {
            $method = $nisn !== null ? 'nisn' : 'nis';
            $label = $method === 'nisn' ? 'NISN' : 'NIS';

            return $this->hasil(null, $method, 'error', "{$label} tidak ditemukan; baris ini dilewati dan tidak dicocokkan dengan identitas lain.");
        }

        if ($nama === null) {
            return $this->hasil(null, 'nama', 'error', 'NISN, NIS, dan nama kosong; siswa tidak dapat diidentifikasi.');
        }

        $applyHistoryScope = function (Builder|Relation $historyQuery) use ($tahunAjaranId, $kelas, $tingkat, $tahunAjaranDipilih, $aktifSaja): void {
            if ($tahunAjaranId !== null) {
                $historyQuery->where('tahun_ajaran_id', $tahunAjaranId);
            } elseif ($tahunAjaranDipilih) {
                $historyQuery->whereRaw('1 = 0');
            }
            if ($aktifSaja) {
                $historyQuery->where('status', 'Aktif');
            }
            if ($kelas !== null) {
                $historyQuery->where('kelas_id', $kelas->id);
            }
            if ($tingkat !== null) {
                $historyQuery->whereHas('kelas', function (Builder $classQuery) use ($tingkat): void {
                    $classQuery->where('tingkat', $tingkat);
                });
            }
        };
        $hasHistoryScope = $tahunAjaranId !== null || $tahunAjaranDipilih || $kelas !== null || $tingkat !== null || $aktifSaja;
        $query = SiswaBiodata::query();
        if ($hasHistoryScope) {
            $query->whereHas('riwayatKelas', $applyHistoryScope);
        }

        $relations = $hasHistoryScope
            ? ['riwayatKelas' => $applyHistoryScope, 'riwayatKelas.kelas']
            : ['riwayatKelas.kelas'];
        $students = $query
            ->with($relations)
            ->get()
            ->filter(fn (SiswaBiodata $candidate): bool => $this->normalizeName($candidate->nama_lengkap) === $this->normalizeName($nama))
            ->values();

        if ($students->count() === 1) {
            return $this->hasil($students->first(), 'nama', 'ok', null);
        }

        if ($students->count() > 1) {
            $candidates = $students->map(fn (SiswaBiodata $candidate): array => [
                'nisn' => $candidate->nisn,
                'kelas' => $candidate->riwayatKelas
                    ->map(fn ($history): ?string => $history->kelas?->nama)
                    ->filter()
                    ->unique()
                    ->implode(', '),
            ])->all();

            return [
                ...$this->hasil(null, 'nama', 'error', 'Nama ambigu; lebih dari satu siswa memiliki nama yang sama. Tambahkan NISN atau NIS.'),
                'kandidat' => $candidates,
            ];
        }

        return $this->hasil(null, 'nama', 'error', 'Siswa dengan identitas tersebut tidak ditemukan.');
    }

    /**
     * @return array{
     *     student: ?SiswaBiodata,
     *     metode: 'nisn'|'nis'|'nama',
     *     level: 'ok'|'peringatan'|'error',
     *     pesan: ?string
     * }
     */
    public function hasilError(array $row, string $pesan): array
    {
        $nisn = trim((string) ($row['nisn'] ?? ''));
        $nis = $this->clean($row['nis'] ?? $row['nomor_induk'] ?? null);
        $method = $nisn !== '' ? 'nisn' : ($nis !== null ? 'nis' : 'nama');

        return $this->hasil(null, $method, 'error', $pesan);
    }

    private function classError(SiswaBiodata $student, ?int $tahunAjaranId, ?SekolahKelas $kelas): ?string
    {
        if ($tahunAjaranId === null || $kelas === null) {
            return null;
        }

        $history = $student->riwayatKelas()
            ->where('tahun_ajaran_id', $tahunAjaranId)
            ->first();

        if ($history !== null && (int) $history->kelas_id !== (int) $kelas->id) {
            return 'Kelas pada file berbeda dari riwayat siswa untuk tahun ajaran ini; data tidak dipindahkan otomatis.';
        }

        return null;
    }

    private function nameSimilarity(?string $storedName, string $importedName): float
    {
        $stored = $this->normalizeName($storedName);
        $imported = $this->normalizeName($importedName);
        if ($stored === '' || $imported === '') {
            return 0;
        }

        similar_text($stored, $imported, $percentage);

        return $percentage / 100;
    }

    private function normalizeName(?string $name): string
    {
        $name = mb_strtolower(trim((string) $name));
        $name = preg_replace(
            '/(?<!\p{L})(?:prof|drs?|dra|ir|h|hj|kh|ust|ustadz|ustadzah|s\.?pd(?:\.?sd|\.?i)?|m\.?pd(?:\.?sd|\.?i)?|s\.?si|m\.?si|s\.?e|m\.?e|s\.?kom|m\.?kom|s\.?h|m\.?h|s\.?ag|m\.?ag|s\.?sos|m\.?sos|a\.?md(?:\.?kom)?|s\.?ip|m\.?ip|s\.?kep|m\.?kes|s\.?th|m\.?th|s\.?t|m\.?t|s\.?psi|m\.?psi|s\.?ak|m\.?ak|s\.?s|m\.?s)\.?(?!\p{L})/u',
            ' ',
            $name
        ) ?? $name;
        $name = preg_replace('/[\p{P}\p{S}]+/u', ' ', $name) ?? $name;

        return trim(preg_replace('/\s+/u', ' ', $name) ?? $name);
    }

    private function clean(mixed $value): ?string
    {
        if ($value === null || ! is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' || in_array(mb_strtolower($value), ['-', 'x', 'n/a'], true)
            ? null
            : $value;
    }

    /**
     * @return array{
     *     student: ?SiswaBiodata,
     *     metode: 'nisn'|'nis'|'nama',
     *     level: 'ok'|'peringatan'|'error',
     *     pesan: ?string
     * }
     */
    private function hasil(?SiswaBiodata $student, string $metode, string $level, ?string $pesan): array
    {
        return [
            'student' => $student,
            'metode' => $metode,
            'level' => $level,
            'pesan' => $pesan,
        ];
    }
}
