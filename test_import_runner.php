<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();
$app->instance('request', Illuminate\Http\Request::create('/'));

$admin = App\Models\Pengguna::where('peran', 'admin')->first();
$app['auth']->guard()->setUser($admin);

$controller = new App\Http\Controllers\Admin\ImportBatchController();

echo "=== TEST 1: IMPORT SISWA ===" . PHP_EOL;
$reqSiswa = new Illuminate\Http\Request([
    'type' => 'siswa',
    'tahun_ajaran' => '2024/2025',
    'mode' => 'semua',
    'dup' => 'timpa',
    'rows' => [
        [
            'nisn' => '0098765431',
            'nis' => '8801',
            'nama' => 'Budi Santoso Uji',
            'kelas' => 'X-1',
            'jk' => 'L',
            'tmp' => 'Nganjuk',
            'tgl' => '2008-05-10',
            'agama' => 'Islam'
        ],
        [
            'nisn' => '0098765432',
            'nis' => '8802',
            'nama' => 'Siti Nurhaliza Uji',
            'kelas' => 'X-2',
            'jk' => 'P',
            'tmp' => 'Kediri',
            'tgl' => '2008-08-15',
            'agama' => 'Islam'
        ]
    ]
]);
$resSiswa = $controller->saveBatch($reqSiswa);
echo "Siswa Response: " . json_encode($resSiswa->getData()) . PHP_EOL;

echo "=== VERIFIKASI SISWA DI DATABASE ===" . PHP_EOL;
$s1 = App\Models\SiswaBiodata::where('nisn', '0098765431')->with('riwayatKelas.kelas')->first();
echo "Found S1: " . ($s1 ? $s1->nama_lengkap . " (Kelas: " . $s1->riwayatKelas->first()?->kelas?->nama . ")" : "NO") . PHP_EOL;

echo "=== TEST 2: IMPORT NILAI ===" . PHP_EOL;
$reqNilai = new Illuminate\Http\Request([
    'type' => 'nilai',
    'tahun_ajaran' => '2024/2025',
    'semester' => 'Genap',
    'mode' => 'semua',
    'rows' => [
        [
            'nisn' => '0098765431',
            'kelas' => 'X-1',
            'mtk' => '88',
            'ind' => '90'
        ]
    ]
]);
$resNilai = $controller->saveBatch($reqNilai);
echo "Nilai Response: " . json_encode($resNilai->getData()) . PHP_EOL;

echo "=== VERIFIKASI NILAI DI DATABASE ===" . PHP_EOL;
$n = App\Models\SiswaNilai::whereHas('riwayatKelas.siswa', fn($q) => $q->where('nisn', '0098765431'))->get();
echo "Total Nilai Tersimpan: " . $n->count() . PHP_EOL;

echo "=== TEST 3: IMPORT KEHADIRAN ===" . PHP_EOL;
$reqHadir = new Illuminate\Http\Request([
    'type' => 'hadir',
    'tahun_ajaran' => '2024/2025',
    'semester' => 'Genap',
    'mode' => 'semua',
    'rows' => [
        [
            'nisn' => '0098765431',
            'kelas' => 'X-1',
            'sakit' => 2,
            'izin' => 1,
            'alpa' => 0,
            'kokur' => 'Aktif dalam proyek P5'
        ]
    ]
]);
$resHadir = $controller->saveBatch($reqHadir);
echo "Hadir Response: " . json_encode($resHadir->getData()) . PHP_EOL;

echo "=== TEST 4: IMPORT EKSKUL ===" . PHP_EOL;
$reqEkskul = new Illuminate\Http\Request([
    'type' => 'ekskul',
    'tahun_ajaran' => '2024/2025',
    'semester' => 'Genap',
    'mode' => 'semua',
    'rows' => [
        [
            'nisn' => '0098765431',
            'kelas' => 'X-1',
            'ekskul' => 'Pramuka',
            'predikat' => 'A'
        ]
    ]
]);
$resEkskul = $controller->saveBatch($reqEkskul);
echo "Ekskul Response: " . json_encode($resEkskul->getData()) . PHP_EOL;
