<?php

namespace App\Services;

use App\Http\Controllers\Admin\PdfCetakController;
use App\Models\SiswaBiodata;
use App\Models\SiswaRiwayatKelas;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * PDF dokumen siswa untuk aplikasi mobile.
 *
 * Isinya identik dengan halaman cetak web (Buku Induk register A-I, capaian nilai,
 * kokurikuler, ekskul, kehadiran, kenaikan kelas): data dibangun oleh builder yang
 * sama (PdfCetakController::dataDokumen) dan partial yang sama (pdf.register,
 * pdf.capaian). Hanya stylesheet-nya yang disesuaikan dengan DomPDF.
 */
class DokumenSiswaPdf
{
    /** F4 (215 x 330 mm) dalam poin, sama dengan ukuran bawaan cetak web. */
    private const KERTAS_F4 = [0, 0, 609.45, 935.43];

    /** Pilihan isi per jenis unduhan; sama dengan preset toolbar halaman cetak web. */
    private const ISI = [
        'bio' => ['bio'],
        'nilai' => ['nilai'],
        'hadir' => ['hadir'],
        'ekskul' => ['ekskul'],
        'all' => ['bio', 'nilai', 'hadir', 'ekskul', 'naik'],
    ];

    /**
     * Nilai variabel CSS layout web. DomPDF tidak mengenal var(--x), padahal beberapa
     * style inline pada partial memakainya, jadi diganti dengan warna sebenarnya.
     */
    private const VARIABEL_CSS = [
        'jb-navy-950' => '#061938', 'jb-navy-900' => '#0a2540', 'jb-navy-800' => '#0f3661',
        'jb-navy-700' => '#154c8a', 'jb-blue-600' => '#2563eb', 'jb-blue-500' => '#3b82f6',
        'jb-blue-100' => '#dbeafe', 'jb-blue-50' => '#eff6ff', 'jb-gold-800' => '#92400e',
        'jb-gold-600' => '#d97706', 'jb-gold-500' => '#f59e0b', 'jb-gold-400' => '#fbbf24',
        'jb-gold-100' => '#fef3c7', 'jb-gold-50' => '#fffbeb', 'jb-slate-900' => '#0f172a',
        'jb-slate-800' => '#1e293b', 'jb-slate-700' => '#334155', 'jb-slate-600' => '#475569',
        'jb-slate-400' => '#94a3b8', 'jb-slate-300' => '#cbd5e1', 'jb-slate-200' => '#e2e8f0',
        'jb-slate-100' => '#f1f5f9', 'jb-slate-50' => '#f8fafc', 'p' => '#0f3661',
        'p2' => '#154c8a', 'a' => '#d97706', 'bg' => '#0b1523', 'ln' => '#cbd5e1',
        'mu' => '#94a3b8',
    ];

    private const EKSTENSI_FOTO = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
    ];

    /**
     * @param  string  $jenis  bio | nilai | hadir | ekskul | all
     * @param  SiswaRiwayatKelas|null  $riwayat  riwayat kelas pada periode yang sudah diperiksa terbuka
     * @param  string|null  $semester  Ganjil | Genap (periode yang sudah diperiksa terbuka)
     */
    public function buat(SiswaBiodata $siswa, string $jenis, ?SiswaRiwayatKelas $riwayat, ?string $semester): DomPdf
    {
        $isi = self::ISI[$jenis];

        // Partial web mengisi "NAIK"/"LULUS" dan tanggal hari ini bila keputusan belum ada.
        // Untuk siswa yang periodenya masih berjalan itu menyesatkan, jadi bagian Kenaikan
        // Kelas hanya dicetak setelah keputusannya benar-benar tersimpan.
        if (! filled($riwayat?->keputusan_kenaikan)) {
            $isi = array_values(array_diff($isi, ['naik']));
        }

        $parameter = ['filter_applied' => '1', 'paper' => 'F4'];
        foreach ($isi as $pilihan) {
            $parameter[$pilihan] = '1';
        }

        // Selain biodata, isi dibatasi pada satu periode yang sudah diperiksa terbuka
        // (tahun ajaran, kelas, dan semester), bukan seluruh riwayat seperti di web.
        if ($jenis !== 'bio' && $riwayat?->tahunAjaran && $semester) {
            $parameter['ta'] = $riwayat->tahunAjaran->nama;
            $parameter['sem'] = $semester;
            if ($riwayat->kelas) {
                $parameter['kelas_filter'] = $riwayat->kelas->nama;
            }
        }

        $data = app(PdfCetakController::class)->dataDokumen(
            Request::create('/', 'GET', $parameter),
            [$siswa->id]
        );

        // Hanya diubah di memori (tidak disimpan) agar foto ikut tercetak.
        $siswaDokumen = $data['siswaItems'][0]['siswa'] ?? null;
        if ($siswaDokumen) {
            $siswaDokumen->foto = $this->fotoSebagaiDataUri($siswa->foto);
        }

        $html = view('siswa-mobile.dokumen-lengkap', $data)->render();
        $html = preg_replace_callback(
            '/var\(--([\w-]+)\)/',
            static fn (array $cocok): string => self::VARIABEL_CSS[$cocok[1]] ?? '#000000',
            $html
        );

        return Pdf::loadHTML($html)->setPaper(self::KERTAS_F4);
    }

    /**
     * Foto dibaca dari lokasi yang sama dengan GET /profil/foto (dengan pemeriksaan
     * jalur dan ekstensi yang sama), lalu disematkan karena DomPDF tidak mengambil
     * gambar dari URL.
     */
    private function fotoSebagaiDataUri(?string $path): ?string
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

            if ($nama === '' || basename($nama) !== $nama || ! isset(self::EKSTENSI_FOTO[$ekstensi])) {
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

            return 'data:'.self::EKSTENSI_FOTO[$ekstensi].';base64,'.base64_encode($isi);
        }

        return null;
    }
}
