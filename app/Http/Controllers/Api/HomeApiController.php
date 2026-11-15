<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SekolahTahunAjaran;
use App\Models\SiswaRiwayatKelas;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Layar Home: sapaan, ringkasan semester, dan aktivitas terbaru.
 */
class HomeApiController extends Controller
{
    /**
     * GET /api/v1/home
     */
    public function index(Request $request): JsonResponse
    {
        $pengguna = $request->user();
        $siswa    = $pengguna->siswa;

        if (! $siswa) {
            return response()->json(['pesan' => 'Data siswa tidak ditemukan.'], 404);
        }

        $ta = SekolahTahunAjaran::where('sedang_berjalan', 1)->first()
            ?? SekolahTahunAjaran::orderByDesc('nama')->first();

        $riwayat = null;
        if ($ta) {
            $riwayat = SiswaRiwayatKelas::with('kelas')
                ->where('siswa_id', $siswa->id)->where('tahun_ajaran_id', $ta->id)->first();
        }
        // Siswa lulus / belum punya baris di tahun berjalan: pakai riwayat terakhir
        $riwayat ??= SiswaRiwayatKelas::with('kelas')
            ->where('siswa_id', $siswa->id)->orderByDesc('tahun_ajaran_id')->first();

        $semester = DB::table('sekolah_pengaturan')->where('kunci', 'semester_berjalan')->value('isi') ?: 'Ganjil';

        return response()->json([
            'data' => [
                'nama'          => $siswa->nama_lengkap,
                'nama_panggilan' => $siswa->nama_panggilan,
                'nisn'          => $siswa->nisn,
                'kelas'         => $riwayat?->kelas?->nama,
                'status'        => $riwayat?->status,
                'tahun_ajaran'  => $ta?->nama,
                'semester'      => $semester,
                'foto_url'      => $siswa->foto ? url('/api/v1/profil/foto') : null,
                'belum_dibaca'  => DB::table('akun_notifikasi')
                    ->where('pengguna_id', $pengguna->id)->whereNull('dibaca_at')->count(),
                'aktivitas_terbaru' => NotifikasiApiController::terbaru($pengguna->id, 3),
            ],
        ]);
    }
}