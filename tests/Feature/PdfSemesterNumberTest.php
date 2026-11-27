<?php

namespace Tests\Feature;

use App\Models\SekolahKelas;
use App\Models\SiswaBiodata;
use App\Models\SiswaRiwayatKelas;
use Tests\TestCase;

class PdfSemesterNumberTest extends TestCase
{
    public function test_pdf_replaces_placeholder_school_name_with_actual_school_name(): void
    {
        $html = view('pdf.cover', [
            'sekolah' => (object) [
                'nama' => 'SMA Negeri 1 Contoh',
                'npsn' => '20538396',
            ],
            'kelasNama' => 'XII-1',
            'angkatan' => 2023,
        ])->render();

        $this->assertStringContainsString('SMA NEGERI 1 REJOSO NGANJUK', $html);
        $this->assertStringNotContainsString('CONTOH', $html);
    }

    public function test_graduation_school_name_is_written_in_full_as_sma_negeri_1_rejoso(): void
    {
        $siswa = new SiswaBiodata([
            'nama_lengkap' => 'Siswa Kelulusan',
            'nisn' => '1234567890',
            'nomor_induk' => '1234',
        ]);

        $history = new SiswaRiwayatKelas;
        $history->keputusan_kenaikan = 'LULUS';
        $history->tanggal_keputusan_kenaikan = '2026-05-04';
        $history->setRelation('kelas', new SekolahKelas([
            'nama' => 'XII-1',
            'tingkat' => 'XII',
        ]));

        $html = view('pdf.capaian', [
            'siswa' => $siswa,
            'sekolah' => (object) [],
            'riwayatKelas' => $history,
            'taNama' => '2025/2026',
            'nilaiMap' => ['Ganjil' => [], 'Genap' => []],
            'kokurMap' => ['Ganjil' => null, 'Genap' => null],
            'ekskulList' => [],
            'hadirMap' => [
                'Ganjil' => ['sakit' => 0, 'izin' => 0, 'alpa' => 0],
                'Genap' => ['sakit' => 0, 'izin' => 0, 'alpa' => 0],
            ],
            'nextKelasMap' => [],
            'tingkatMapel' => null,
            'slotSemesters' => ['Ganjil', 'Genap'],
            'emptySemesters' => [],
            'optNilai' => false,
            'optHadir' => false,
            'optEkskul' => false,
            'optNaik' => true,
        ])->render();

        $this->assertStringContainsString('SMA NEGERI 1 REJOSO', $html);
        $this->assertStringNotContainsString('SMA NEGERI 1 REJOSO NGANJUK', $html);
    }

    public function test_cover_uses_the_actual_class_and_cohort_year(): void
    {
        $html = view('pdf.cover', [
            'sekolah' => (object) [
                'nama' => 'SMA Negeri 1 Rejoso Nganjuk',
                'npsn' => '20538396',
                'alamat' => 'Jalan Yos Sudarso',
                'desa' => 'Sidokare',
                'kecamatan' => 'Rejoso',
                'kabupaten' => 'Nganjuk',
                'provinsi' => 'Jawa Timur',
            ],
            'kelasNama' => 'X-1',
            'angkatan' => 2024,
        ])->render();

        $this->assertStringContainsString('KELAS X-1', $html);
        $this->assertStringContainsString('TAHUN ANGKATAN 2024', $html);
        $this->assertStringContainsString('TAHUN LULUS 2027', $html);
        $this->assertStringNotContainsString('KELAS XII-1', $html);
    }

    public function test_report_does_not_invent_promotion_decision_and_marks_unoffered_subjects_as_x(): void
    {
        $siswa = new SiswaBiodata([
            'nama_lengkap' => 'Siswa Tanpa Keputusan',
            'nisn' => '1234567890',
            'nomor_induk' => '1234',
        ]);

        $history = new SiswaRiwayatKelas;
        $history->setRelation('kelas', new SekolahKelas([
            'nama' => 'XII-1',
            'tingkat' => 'XII',
        ]));

        $html = view('pdf.capaian', [
            'siswa' => $siswa,
            'sekolah' => (object) [],
            'riwayatKelas' => $history,
            'taNama' => '2025/2026',
            'nilaiMap' => [
                'Ganjil' => [],
                'Genap' => [],
            ],
            'kokurMap' => ['Ganjil' => null, 'Genap' => null],
            'ekskulList' => [],
            'hadirMap' => [
                'Ganjil' => ['sakit' => 0, 'izin' => 0, 'alpa' => 0],
                'Genap' => ['sakit' => 0, 'izin' => 0, 'alpa' => 0],
            ],
            'nextKelasMap' => [],
            'tingkatMapel' => null,
            'emptySemesters' => ['Ganjil', 'Genap'],
            'slotSemesters' => ['Ganjil', 'Genap'],
            'optNilai' => true,
            'optHadir' => false,
            'optEkskul' => false,
            'optNaik' => true,
        ])->render();

        $this->assertStringContainsString('X : Tidak diajarkan pada semester tersebut', $html);
        $this->assertStringContainsString('Peserta Didik ditetapkan :', $html);
        $this->assertStringNotContainsString('LULUS / TIDAK LULUS', $html);
    }

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
            ['XI-1', 'XI', 'Ganjil', 1],
            ['XI-1', 'XI', 'Genap', 2],
            ['XII-1', 'XII', 'Ganjil', 1],
            ['XII-1', 'XII', 'Genap', 2],
        ] as [$className, $grade, $term, $semesterNumber]) {
            $history = new SiswaRiwayatKelas;
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
