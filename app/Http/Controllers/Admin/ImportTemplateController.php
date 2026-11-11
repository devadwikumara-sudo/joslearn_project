<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ImportTemplateExport;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ImportTemplateController extends Controller
{
    private const TEMPLATES = [
        'siswa' => [
            'headers' => [
                'NISN', 'NIS', 'Nama', 'Kelas', 'Tahun Ajaran', 'Jenis Kelamin',
                'Tempat Lahir', 'Tanggal Lahir', 'Agama', 'NIK', 'Kewarganegaraan',
                'Alamat', 'RT/RW', 'Kelurahan', 'Kecamatan', 'Kabupaten', 'Provinsi',
                'Kode Pos', 'Nomor HP', 'Tinggal', 'Jarak ke Sekolah',
                'Golongan Darah', 'Penyakit', 'Kelainan Fisik', 'Tinggi Badan',
                'Berat Badan', 'Sekolah Asal', 'Nama Ayah', 'Pendidikan Ayah',
                'Pekerjaan Ayah', 'Penghasilan Ayah', 'HP Ayah', 'Nama Ibu',
                'Pendidikan Ibu', 'Pekerjaan Ibu', 'Penghasilan Ibu', 'HP Ibu',
                'Nama Wali', 'Status Siswa',
            ],
            'instruction' => 'NISN dan Nama wajib diisi. Baris data dimulai tepat di bawah header.',
        ],
        'guru' => [
            'headers' => [
                'Nama', 'NIP', 'NUPTK', 'NIK', 'Jenis Kelamin', 'Tempat Lahir',
                'Tanggal Lahir', 'Pendidikan', 'Status Kepegawaian', 'Mulai Bertugas',
                'Nomor HP', 'Alamat',
            ],
            'instruction' => 'Isi Nama atau NIP. Template ini hanya untuk biodata, bukan penugasan kelas/mapel.',
        ],
        'nilai' => [
            'headers' => ['NISN', 'Nama', 'Kelas', 'Semester', 'Mata Pelajaran', 'Nilai Akhir'],
            'instruction' => 'Satu baris untuk satu mapel. Isi kode/nama mapel yang terdaftar dan nilai 0–100. Semester dapat berupa Ganjil/Genap atau nomor 1–6.',
        ],
        'kehadiran' => [
            'headers' => ['NISN', 'Nama', 'Kelas', 'Semester', 'Sakit (hari)', 'Izin (hari)', 'Alpa (hari)', 'Kokurikuler'],
            'instruction' => 'Isi jumlah hari sebagai bilangan bulat tidak negatif. Semester dapat berupa Ganjil/Genap atau nomor 1–6.',
        ],
        'ekskul' => [
            'headers' => ['NISN', 'Nama', 'Kelas', 'Semester', 'Nama Ekskul', 'Predikat'],
            'instruction' => 'Satu baris untuk satu kegiatan. Semester dapat berupa Ganjil/Genap atau nomor 1–6.',
        ],
    ];

    public function download(string $jenis): BinaryFileResponse
    {
        abort_unless(isset(self::TEMPLATES[$jenis]), 404);

        $template = self::TEMPLATES[$jenis];
        $workbook = new ImportTemplateExport(
            ['Data' => [$template['headers']]],
            [
                ['Petunjuk penggunaan'],
                [$template['instruction']],
                ['Tidak ada baris contoh. Isi hanya data yang akan diimpor; jangan menambah atau mengubah nama header.'],
            ]
        );

        return Excel::download($workbook, 'template-'.$jenis.'.xlsx', ExcelWriter::XLSX);
    }
}
