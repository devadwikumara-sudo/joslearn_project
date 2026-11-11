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
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
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
            $history = new SiswaRiwayatKelas;
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
        $this->assertStringNotContainsString('data-academic-open', $html);
    }

    public function test_admin_academic_detail_shows_add_update_and_delete_controls_per_record(): void
    {
        $siswa = new SiswaBiodata(['nama_lengkap' => 'Siswa Uji']);
        $siswa->setAttribute('id', 3);

        $history = new SiswaRiwayatKelas(['tahun_ajaran_id' => 2025]);
        $history->setAttribute('id', 44);
        $history->setRelation('kelas', new SekolahKelas(['nama' => 'X-1', 'tingkat' => 'X']));
        $history->setRelation('tahunAjaran', new SekolahTahunAjaran(['nama' => '2025/2026']));

        $mapel = new SekolahMapel(['nama' => 'Matematika', 'kode' => 'mtk']);
        $mapel->setAttribute('id', 7);
        $grade = new SiswaNilai(['semester' => 'Ganjil', 'mapel_id' => 7, 'nilai_angka' => 88]);
        $grade->setAttribute('id', 99);
        $grade->setRelation('mapel', $mapel);
        $history->setRelation('nilai', collect([$grade]));

        $attendance = new SiswaKehadiran([
            'semester' => 'Ganjil',
            'sakit' => 1,
            'izin' => 0,
            'alpa' => 0,
        ]);
        $attendance->setAttribute('id', 100);
        $history->setRelation('kehadiran', collect([$attendance]));

        $activity = new SiswaEkskul([
            'semester' => 'Ganjil',
            'nama_kegiatan' => 'Pramuka',
            'predikat' => 'Baik',
        ]);
        $activity->setAttribute('id', 101);
        $history->setRelation('ekskul', collect([$activity]));
        $siswa->setRelation('riwayatKelasAkademik', collect([$history]));

        $html = view('partials.siswa-akademik-content', [
            'siswa' => $siswa,
            'canManageAcademicRecords' => true,
            'academicMapelOptions' => new EloquentCollection([$mapel]),
        ])->render();

        foreach (['nilai', 'kehadiran', 'ekskul'] as $type) {
            $this->assertStringContainsString('data-type="'.$type.'"', $html);
            $this->assertStringContainsString('/data-siswa/3/rekam-akademik/'.$type, $html);
        }
        $this->assertStringContainsString('/data-siswa/3/rekam-akademik/nilai/99', $html);
        $this->assertStringContainsString('/data-siswa/3/rekam-akademik/kehadiran/100', $html);
        $this->assertStringContainsString('/data-siswa/3/rekam-akademik/ekskul/101', $html);
    }
}
