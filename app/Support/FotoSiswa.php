<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class FotoSiswa
{
    private const EKSTENSI = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
    ];

    public static function sebagaiDataUri(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        foreach (['siswa/foto/' => 'disk', 'uploads/foto/' => 'public'] as $awalan => $lokasi) {
            if (! str_starts_with($path, $awalan)) {
                continue;
            }

            $nama = substr($path, strlen($awalan));
            $ekstensi = strtolower(pathinfo($nama, PATHINFO_EXTENSION));

            if ($nama === '' || basename($nama) !== $nama || ! isset(self::EKSTENSI[$ekstensi])) {
                return null;
            }

            if ($lokasi === 'disk') {
                $isi = Storage::disk('local')->exists($path) ? Storage::disk('local')->get($path) : null;
            } else {
                $isi = is_file(public_path($path)) ? file_get_contents(public_path($path)) : null;
            }

            if (! is_string($isi) || $isi === '') {
                return null;
            }

            return 'data:'.self::EKSTENSI[$ekstensi].';base64,'.base64_encode($isi);
        }

        return null;
    }
}
