<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SekolahTahunAjaran;
use App\Models\SiswaBiodata;
use App\Models\SiswaRiwayatKelas;
use App\Support\Semester;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Layar Nilai (pilih tahun ajaran + semester).
 * Aturan desain: hanya tahun ajaran dan semester yang sudah dijalani yang bisa dibuka.
 */
class NilaiApiController extends Controller
{
    /**
     * GET /api/v1/nilai?ta={tahun_ajaran_id}&semester={Ganjil|Genap}
     * Tanpa parameter: tahun ajaran terbaru dan semester terbuka terakhir.
     */
    public function index(Request $request): JsonResponse
    {
        $siswa = $request->user()->siswa;

        if (! $siswa) {
            return response()->json(['pesan' => 'Data siswa tidak ditemukan.'], 404);
        }

        $periode = self::periodeTersedia($siswa);

        if (! $periode) {
            return response()->json([
                'data' => ['periode' => [], 'terpilih' => null, 'nilai' => [], 'rata_rata' => null],
            ]);
        }

        [$terpilih, $semester] = self::pilihPeriode($request, $periode);

        if (! $terpilih) {
            return response()->json(['pesan' => 'Tahun ajaran atau semester ini belum bisa dibuka.'], 403);
        }

        return response()->json([
            'data' => [
                'periode'  => $periode,
                'terpilih' => [
                    'tahun_ajaran_id' => $terpilih['tahun_ajaran_id'],
                    'tahun_ajaran'    => $terpilih['tahun_ajaran'],
                    'kelas'           => $terpilih['kelas'],
                    'semester'        => $semester,
                ],
            ] + self::ambilDataSemester($terpilih['riwayat_id'], (string) $terpilih['tingkat'], $semester),
        ]);
    }

    /**
     * Daftar tahun ajaran (milik siswa) beserta semester yang sudah terbuka.
     */
    public static function periodeTersedia(SiswaBiodata $siswa): array
    {
        $taBerjalan = SekolahTahunAjaran::where('sedang_berjalan', 1)->first();
        $semesterBerjalan = DB::table('sekolah_pengaturan')->where('kunci', 'semester_berjalan')->value('isi') ?: 'Ganjil';

        $riwayat = SiswaRiwayatKelas::with(['tahunAjaran', 'kelas'])
            ->where('siswa_id', $siswa->id)
            ->get()
            ->filter(fn ($r) => $r->tahunAjaran)
            ->sortBy(fn ($r) => $r->tahunAjaran->nama);

        $hasil = [];

        foreach ($riwayat as $r) {
            $ta = $r->tahunAjaran;

            // Tahun ajaran setelah yang berjalan belum dijalani
            if ($taBerjalan && strcmp($ta->nama, $taBerjalan->nama) > 0) {
                continue;
            }

            $genapTerbuka = ! $taBerjalan
                || strcmp($ta->nama, $taBerjalan->nama) < 0
                || $semesterBerjalan === 'Genap';

            $hasil[] = [
                'riwayat_id'      => $r->id,
                'tahun_ajaran_id' => $ta->id,
                'tahun_ajaran'    => $ta->nama,
                'kelas'           => $r->kelas?->nama,
                'tingkat'         => $r->kelas?->tingkat,
                'semester'        => [
                    ['nama' => 'Ganjil', 'terbuka' => true],
                    ['nama' => 'Genap', 'terbuka' => $genapTerbuka],
                ],
            ];
        }

        return $hasil;
    }

    /**
     * Pilih tahun ajaran + semester dari query string, atau default terbaru.
     * Mengembalikan [periodeTerpilih, namaSemester], atau [null, null] jika tidak boleh dibuka.
     */
    public static function pilihPeriode(Request $request, array $periode): array
    {
        $taId = (int) $request->query('ta', 0);

        $terpilih = $taId
            ? collect($periode)->firstWhere('tahun_ajaran_id', $taId)
            : collect($periode)->last();

        if (! $terpilih) {
            return [null, null];
        }

        $terbuka = collect($terpilih['semester'])->where('terbuka', true)->pluck('nama')->all();
        $semester = $request->query('semester') ?: end($terbuka);

        if (! in_array($semester, $terbuka, true)) {
            return [null, null];
        }

        return [$terpilih, $semester];
    }

    /**
     * Nilai, rata-rata, kehadiran, dan ekskul untuk satu semester.
     * Juga dipakai BiodataApiController saat membuat PDF.
     */
    public static function ambilDataSemester(int $riwayatId, string $tingkat, string $semester): array
    {
        $semester = Semester::jenisDariNomor(Semester::nomor($tingkat, $semester));

        $nilai = DB::table('siswa_nilai as n')
            ->join('sekolah_mapel as m', 'm.id', '=', 'n.mapel_id')
            ->leftJoin('sekolah_mapel_per_tingkat as k', function ($join) use ($tingkat) {
                $join->on('k.mapel_id', '=', 'n.mapel_id')->where('k.tingkat', '=', $tingkat);
            })
            ->where('n.riwayat_kelas_id', $riwayatId)
            ->where('n.semester', $semester)
            ->orderBy('k.urutan')
            ->orderBy('m.nama')
            ->get(['m.nama as mapel', 'n.nilai_angka as nilai', 'k.kelompok_mapel as kelompok'])
            ->map(fn ($x) => [
                'mapel'    => $x->mapel,
                'nilai'    => (float) $x->nilai,
                'kelompok' => $x->kelompok,
            ])
            ->all();

        $rataRata = $nilai ? round(array_sum(array_column($nilai, 'nilai')) / count($nilai), 2) : null;

        $hadir = DB::table('siswa_kehadiran')
            ->where('riwayat_kelas_id', $riwayatId)->where('semester', $semester)
            ->first(['sakit', 'izin', 'alpa', 'kokurikuler']);

        $ekskul = DB::table('siswa_ekskul')
            ->where('riwayat_kelas_id', $riwayatId)->where('semester', $semester)
            ->orderBy('nama_kegiatan')
            ->get(['nama_kegiatan as kegiatan', 'predikat'])
            ->map(fn ($e) => ['kegiatan' => $e->kegiatan, 'predikat' => $e->predikat])
            ->all();

        return [
            'nilai'     => $nilai,
            'rata_rata' => $rataRata,
            'kehadiran' => $hadir ? [
                'sakit'       => (int) $hadir->sakit,
                'izin'        => (int) $hadir->izin,
                'alpa'        => (int) $hadir->alpa,
                'kokurikuler' => $hadir->kokurikuler,
            ] : null,
            'ekskul'    => $ekskul,
        ];
    }
}