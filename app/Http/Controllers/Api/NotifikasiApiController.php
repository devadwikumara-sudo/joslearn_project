<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Layar Notifikasi dan "Aktivitas Terbaru" di Home.
 * Tabel: akun_notifikasi (jenis: info / peringatan).
 */
class NotifikasiApiController extends Controller
{
    /**
     * Dipanggil controller lain untuk membuat notifikasi,
     * contoh: NotifikasiApiController::buat($id, 'PDF Biodata berhasil di unduh', '...');
     */
    public static function buat(int $penggunaId, string $judul, ?string $isi = null, string $jenis = 'info'): void
    {
        DB::table('akun_notifikasi')->insert([
            'pengguna_id' => $penggunaId,
            'judul'       => $judul,
            'isi'         => $isi,
            'jenis'       => $jenis,
            'created_at'  => now(),
        ]);
    }

    /**
     * Daftar notifikasi terbaru milik pengguna (untuk dipakai juga oleh Home).
     */
    public static function terbaru(int $penggunaId, int $batas = 50): array
    {
        return DB::table('akun_notifikasi')
            ->where('pengguna_id', $penggunaId)
            ->orderByDesc('id')
            ->limit($batas)
            ->get()
            ->map(fn ($n) => [
                'id'     => $n->id,
                'judul'  => $n->judul,
                'isi'    => $n->isi,
                'jenis'  => $n->jenis,
                'dibaca' => $n->dibaca_at !== null,
                'waktu'  => $n->created_at,
            ])
            ->all();
    }

    /**
     * GET /api/v1/notifikasi
     */
    public function index(Request $request): JsonResponse
    {
        $id = $request->user()->id;

        return response()->json([
            'data' => [
                'belum_dibaca' => DB::table('akun_notifikasi')
                    ->where('pengguna_id', $id)->whereNull('dibaca_at')->count(),
                'notifikasi'   => self::terbaru($id),
            ],
        ]);
    }

    /**
     * POST /api/v1/notifikasi/baca  — tandai semua sudah dibaca.
     */
    public function baca(Request $request): JsonResponse
    {
        DB::table('akun_notifikasi')
            ->where('pengguna_id', $request->user()->id)
            ->whereNull('dibaca_at')
            ->update(['dibaca_at' => now()]);

        return response()->json(['pesan' => 'Semua notifikasi ditandai sudah dibaca.']);
    }
}