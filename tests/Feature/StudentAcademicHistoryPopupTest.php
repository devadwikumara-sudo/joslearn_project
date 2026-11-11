<?php

namespace Tests\Feature;

use App\Models\SekolahKelas;
use App\Models\SekolahMapel;
use App\Models\SekolahTahunAjaran;
use App\Models\SiswaBiodata;
use App\Models\SiswaEkskul;
use App\Models\SiswaKehadiran;
use App\Models\SiswaNilai;
use App\Models\SiswaRiwayatKelas;
use Tests\TestCase;

class StudentAcademicHistoryPopupTest extends TestCase
{
    public function test_student_detail_popup_displays_all_six_semesters_and_academic_records(): void
    {
        $siswa = new SiswaBiodata([
            'nama_lengkap' => 'Siswa Enam Semester',
            'nisn' => '1234567890',
            'nomor_induk' => '1234',
        ]);
        $siswa->setRelation('berkas', collect());

        $histories = collect();
        foreach ([
            ['X-1', 'X', '2023/2024'],
            ['XI-1', 'XI', '2024/2025'],
            ['XII-1', 'XII', '2025/2026'],
        ] as [$className, $grade, $year]) {
            $history = new SiswaRiwayatKelas();
            $history->setRelation('kelas', new SekolahKelas(['nama' => $className, 'tingkat' => $grade]));
            $history->setRelation('tahunAjaran', new SekolahTahunAjaran(['nama' => $year]));

            $gradeRow = new SiswaNilai(['semester' => 'Ganjil', 'nilai_angka' => 88]);
            $gradeRow->setRelation('mapel', new SekolahMapel(['nama' => 'Matematika', 'kode' => 'mtk']));
            $history->setRelation('nilai', collect([$gradeRow]));
            $history->setRelation('kehadiran', collect([
                new SiswaKehadiran([
                    'semester' => 'Ganjil',
                    'sakit' => 1,
                    'izin' => 2,
                    'alpa' => 0,
                    'kokurikuler' => 'Aktif berkolaborasi',
                ]),
            ]));
            $history->setRelation('ekskul', collect([
                new SiswaEkskul([
                    'semester' => 'Ganjil',
                    'nama_kegiatan' => 'Pramuka',
                    'predikat' => 'Baik',
                ]),
            ]));
            $histories->push($history);
        }

        $siswa->setRelation('riwayatKelasAkademik', $histories);

        $html = view('admin.siswa-detail-content', [
            'siswa' => $siswa,
            'includeGraduationDetails' => true,
        ])->render();

        foreach (range(1, 6) as $semester) {
            $this->assertStringContainsString('Semester '.$semester, $html);
        }
        $this->assertStringContainsString('Matematika: 88', $html);
        $this->assertStringContainsString('Sakit 1, Izin 2, Alpa 0', $html);
        $this->assertStringContainsString('Pramuka: Baik', $html);
    }
}
