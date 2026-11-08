<?php

namespace Tests\Feature;

use App\Models\SekolahKelas;
use App\Models\SiswaBiodata;
use App\Models\SiswaRiwayatKelas;
use Tests\TestCase;

class PdfSemesterNumberTest extends TestCase
{
    public function test_report_labels_each_grade_and_term_with_its_global_semester_number(): void
    {
        $siswa = new SiswaBiodata([
            'nama_lengkap' => 'Siswa Enam Semester',
            'nisn' => '1234567890',
            'nomor_induk' => '1234',
        ]);

        foreach ([
            ['X-1', 'X', 'Ganjil', 1],
            ['X-1', 'X', 'Genap', 2],
            ['XI-1', 'XI', 'Ganjil', 3],
            ['XI-1', 'XI', 'Genap', 4],
            ['XII-1', 'XII', 'Ganjil', 5],
            ['XII-1', 'XII', 'Genap', 6],
        ] as [$className, $grade, $term, $semesterNumber]) {
            $history = new SiswaRiwayatKelas();
            $history->setRelation('kelas', new SekolahKelas([
                'nama' => $className,
                'tingkat' => $grade,
            ]));

            $html = view('pdf.capaian', [
                'siswa' => $siswa,
                'sekolah' => (object) [],
                'riwayatKelas' => $history,
                'taNama' => '2025/2026',
                'filterSem' => $term,
                'nilaiMap' => [
                    'Ganjil' => ['mtk' => 81],
                    'Genap' => ['mtk' => 92],
                ],
                'kokurMap' => ['Ganjil' => null, 'Genap' => null],
                'ekskulList' => [],
                'hadirMap' => [
                    'Ganjil' => ['sakit' => 0, 'izin' => 0, 'alpa' => 0],
                    'Genap' => ['sakit' => 0, 'izin' => 0, 'alpa' => 0],
                ],
                'nextKelasMap' => [],
                'optNilai' => true,
                'optHadir' => false,
                'optEkskul' => false,
                'optNaik' => false,
            ])->render();

            $this->assertStringContainsString('Semester '.$semesterNumber, $html);
            $this->assertStringContainsString($term === 'Ganjil' ? '81' : '92', $html);
            $this->assertStringNotContainsString($term === 'Ganjil' ? '>92<' : '>81<', $html);
        }
    }
}
