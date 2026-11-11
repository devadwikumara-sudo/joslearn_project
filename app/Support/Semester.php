<?php

namespace App\Support;

use InvalidArgumentException;

class Semester
{
    private const TINGKAT = ['X', 'XI', 'XII'];

    private const JENIS = ['Ganjil', 'Genap'];

    /**
     * @return array{tingkat: string, jenis: string}
     */
    public static function dari(int $nomor): array
    {
        if ($nomor < 1 || $nomor > 6) {
            throw new InvalidArgumentException("Nomor semester harus antara 1 dan 6, {$nomor} diberikan.");
        }

        return [
            'tingkat' => self::TINGKAT[intdiv($nomor - 1, 2)],
            'jenis' => self::JENIS[($nomor - 1) % 2],
        ];
    }

    public static function nomor(string $tingkat, string $jenis): int
    {
        $tingkat = strtoupper(trim($tingkat));
        $jenis = ucfirst(strtolower(trim($jenis)));
        $indeksTingkat = array_search($tingkat, self::TINGKAT, true);
        $indeksJenis = array_search($jenis, self::JENIS, true);

        if ($indeksTingkat === false || $indeksJenis === false) {
            throw new InvalidArgumentException("Tingkat '{$tingkat}' atau jenis semester '{$jenis}' tidak valid.");
        }

        return ($indeksTingkat * 2) + $indeksJenis + 1;
    }

    public static function jenisDariNomor(int $nomor): string
    {
        return self::dari($nomor)['jenis'];
    }

    public static function tingkatDariNomor(int $nomor): string
    {
        return self::dari($nomor)['tingkat'];
    }

    public static function tingkatDariNamaKelas(string $nama): ?string
    {
        return preg_match('/^(XII|XI|X)(?:-|$)/i', trim($nama), $matches)
            ? strtoupper($matches[1])
            : null;
    }

    public static function tahunAjaranDariTahunMasuk(int $tahunMasuk, string $tingkat): string
    {
        $tingkat = strtoupper(trim($tingkat));
        $indeksTingkat = array_search($tingkat, self::TINGKAT, true);

        if ($indeksTingkat === false) {
            throw new InvalidArgumentException("Tingkat '{$tingkat}' tidak valid.");
        }
        if ($tahunMasuk < 1000 || $tahunMasuk > 9998) {
            throw new InvalidArgumentException("Tahun masuk harus berupa tahun empat digit yang valid, {$tahunMasuk} diberikan.");
        }

        $tahunMulai = $tahunMasuk + $indeksTingkat;

        return $tahunMulai.'/'.($tahunMulai + 1);
    }
}
