<?php
namespace App\Services;

class ExcelImportService
{
    // TODO: deteksi sheet/baris judul, pemetaan alias kolom, pratinjau, validasi, simpan.
    public function preview(string $path): array
    {
        return ['status' => 'belum_dibuat'];
    }
}
