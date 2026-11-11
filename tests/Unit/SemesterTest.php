<?php

namespace Tests\Unit;

use App\Support\Semester;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SemesterTest extends TestCase
{
    public static function semesterPairs(): array
    {
        return [
            'X Ganjil' => [1, 'X', 'Ganjil'],
            'X Genap' => [2, 'X', 'Genap'],
            'XI Ganjil' => [3, 'XI', 'Ganjil'],
            'XI Genap' => [4, 'XI', 'Genap'],
            'XII Ganjil' => [5, 'XII', 'Ganjil'],
            'XII Genap' => [6, 'XII', 'Genap'],
        ];
    }

    #[DataProvider('semesterPairs')]
    public function test_semester_number_converts_in_both_directions(int $number, string $grade, string $term): void
    {
        self::assertSame($number, Semester::nomor($grade, $term));
        self::assertSame(['tingkat' => $grade, 'jenis' => $term], Semester::dari($number));
        self::assertSame($grade, Semester::tingkatDariNomor($number));
        self::assertSame($term, Semester::jenisDariNomor($number));
    }

    public function test_invalid_semester_values_throw_a_clear_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Nomor semester harus antara 1 dan 6');

        Semester::dari(7);
    }

    public function test_invalid_grade_or_term_throws_a_clear_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Tingkat 'XIII' atau jenis semester 'Ganjil' tidak valid");

        Semester::nomor('XIII', 'Ganjil');
    }

    public function test_grade_is_resolved_from_a_class_name(): void
    {
        self::assertSame('X', Semester::tingkatDariNamaKelas('X-1'));
        self::assertSame('XI', Semester::tingkatDariNamaKelas('xi-2'));
        self::assertSame('XII', Semester::tingkatDariNamaKelas('XII'));
        self::assertNull(Semester::tingkatDariNamaKelas('Kelas Khusus'));
    }

    public function test_academic_year_is_calculated_from_admission_year_and_grade(): void
    {
        self::assertSame('2023/2024', Semester::tahunAjaranDariTahunMasuk(2023, 'X'));
        self::assertSame('2024/2025', Semester::tahunAjaranDariTahunMasuk(2023, 'XI'));
        self::assertSame('2025/2026', Semester::tahunAjaranDariTahunMasuk(2023, 'XII'));
    }
}
