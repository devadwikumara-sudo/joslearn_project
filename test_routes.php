<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

$admin = App\Models\Pengguna::where('peran', 'admin')->first();
if (!$admin) {
    echo "No admin found\n";
    exit;
}

$routes = [
    '/admin/dashboard',
    '/admin/data-siswa',
    '/admin/form-pendataan',
    '/admin/kenaikan-kelas',
    '/admin/mutasi-siswa',
    '/admin/data-guru',
    '/admin/alumni',
    '/admin/pelacakan-alumni',
    '/admin/import/nilai',
    '/admin/import/kehadiran',
    '/admin/import/ekskul',
    '/admin/laporan',
    '/admin/log-aktivitas',
    '/admin/master-kelas',
    '/admin/cek-kualitas-data',
    '/admin/sampah',
    '/admin/setting',
    '/ganti-password',
];

foreach ($routes as $uri) {
    $request = Illuminate\Http\Request::create($uri, 'GET');
    $app->instance('request', $request);
    auth()->login($admin);
    try {
        $response = $kernel->handle($request);
        echo $uri . ' -> ' . $response->getStatusCode() . "\n";
        if ($response->getStatusCode() >= 400) {
            echo "Error content: " . substr(strip_tags($response->getContent()), 0, 300) . "\n";
        }
    } catch (\Throwable $e) {
        echo $uri . ' -> EXCEPTION: ' . $e->getMessage() . "\n";
    }
}
