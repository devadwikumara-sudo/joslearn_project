<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SiswaBiodata;
use App\Models\SiswaRiwayatKelas;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Layar Biodata (hanya lihat) dan Unduh PDF.
 */
class BiodataApiController extends Controller
{
    private const JENIS_UNDUH = ['bio', 'nilai', 'hadir', 'ekskul', 'all'];

    /**
     * GET /api/v1/biodata
     */
    public function show(Request $request): JsonResponse
    {
        $siswa = $request->user()->siswa;

        if (! $siswa) {
            return response()->json(['pesan' => 'Data siswa tidak ditemukan.'], 404);
        }

        return response()->json(['data' => self::ringkasanBiodata($siswa)]);
    }

    /**
     * GET /api/v1/unduh/{jenis}?ta=&semester=
     * jenis: bio | nilai | hadir | ekskul | all
     * Mengembalikan file PDF (Flutter menyimpan / membukanya).
     */
    public function unduh(Request $request, string $jenis)
    {
        if (! in_array($jenis, self::JENIS_UNDUH, true)) {
            return response()->json(['pesan' => 'Jenis dokumen tidak dikenal.'], 404);
        }

        $pengguna = $request->user();
        $siswa    = $pengguna->siswa;

        if (! $siswa) {
            return response()->json(['pesan' => 'Data siswa tidak ditemukan.'], 404);
        }

        $periodeSiswa = NilaiApiController::periodeTersedia($siswa);
        $riwayat  = null;
        $semester = null;
        $dataSemester = [];

        // Biodata tidak butuh semester; dokumen lain butuh tahun ajaran + semester
        if ($jenis !== 'bio') {
            [$terpilih, $semester] = NilaiApiController::pilihPeriode($request, $periodeSiswa);

            if (! $terpilih) {
                return response()->json(['pesan' => 'Tahun ajaran atau semester ini belum bisa dibuka.'], 403);
            }

            $riwayat = SiswaRiwayatKelas::with(['kelas', 'tahunAjaran'])->find($terpilih['riwayat_id']);
            $dataSemester = NilaiApiController::ambilDataSemester(
                $terpilih['riwayat_id'], (string) $terpilih['tingkat'], $semester
            );
        } else {
            $riwayat = SiswaRiwayatKelas::with(['kelas', 'tahunAjaran'])
                ->where('siswa_id', $siswa->id)->orderByDesc('tahun_ajaran_id')->first();
        }

        $judul = [
            'bio'    => 'Biodata',
            'nilai'  => 'Nilai',
            'hadir'  => 'Kehadiran',
            'ekskul' => 'Ekstrakurikuler',
            'all'    => 'Dokumen Lengkap',
        ][$jenis];

        $pdf = Pdf::loadView('siswa-mobile.dokumen-pdf', [
            'jenis'    => $jenis,
            'judul'    => $judul,
            'siswa'    => $siswa,
            'riwayat'  => $riwayat,
            'semester' => $semester,
            'biodata'  => self::ringkasanBiodata($siswa),
            'data'     => $dataSemester,
        ])->setPaper('a4');

        NotifikasiApiController::buat(
            $pengguna->id,
            "PDF {$judul} berhasil di unduh",
            "Unduh PDF {$judul} telah berhasil"
        );

        return $pdf->download(strtolower(str_replace(' ', '-', $judul)).'-'.$siswa->nisn.'.pdf');
    }

    /**
     * Isi layar Biodata (sama dengan isi prototype).
     */
    public static function ringkasanBiodata(SiswaBiodata $siswa): array
    {
        return [
            'nama'           => $siswa->nama_lengkap,
            'nisn'           => $siswa->nisn,
            'nomor_induk'    => $siswa->nomor_induk,
            'tempat_lahir'   => $siswa->tempat_lahir,
            'tanggal_lahir'  => $siswa->tanggal_lahir,
            'jenis_kelamin'  => $siswa->jenis_kelamin,
            'agama'          => $siswa->agama,
            'alamat'         => self::gabungAlamat($siswa),
            'nama_ayah'      => $siswa->nama_ayah,
            'nama_ibu'       => $siswa->nama_ibu,
            'nama_wali'      => $siswa->nama_wali,
            'sekolah_asal'   => $siswa->sekolah_asal,
            'tanggal_diterima' => $siswa->tanggal_diterima,
            'tinggi_badan_cm' => $siswa->tinggi_badan_cm,
            'berat_badan_kg' => $siswa->berat_badan_kg,
        ];
    }

    public static function gabungAlamat(SiswaBiodata $siswa): ?string
    {
        $bagian = array_filter([
            $siswa->alamat_jalan,
            $siswa->alamat_rt_rw ? 'RT/RW '.$siswa->alamat_rt_rw : null,
            $siswa->alamat_desa,
            $siswa->alamat_kecamatan,
            $siswa->alamat_kabupaten,
            $siswa->alamat_provinsi,
            $siswa->alamat_kode_pos,
        ], fn ($x) => filled($x));

        return $bagian ? implode(', ', $bagian) : null;
    }
}