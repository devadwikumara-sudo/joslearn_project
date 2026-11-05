<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Layar "Sesi Aktif" dan "Logout dari semua perangkat?".
 */
class SesiApiController extends Controller
{
    /**
     * GET /api/v1/sesi
     * Dipakai juga saat aplikasi dibuka: "Sesi masih aktif, lanjutkan sebagai ...?"
     */
    public function index(Request $request): JsonResponse
    {
        $pengguna = $request->user();
        $tokenId  = $pengguna->currentAccessToken()->id;

        DB::table('akun_perangkat_login')
            ->where('token_id', $tokenId)
            ->update(['terakhir_aktif_at' => now()]);

        $perangkat = DB::table('akun_perangkat_login')
            ->where('pengguna_id', $pengguna->id)
            ->orderByDesc('terakhir_aktif_at')
            ->get()
            ->map(fn ($p) => [
                'id'               => $p->id,
                'nama_perangkat'   => $p->nama_perangkat ?: 'Perangkat tidak dikenal',
                'alamat_ip'        => $p->alamat_ip,
                'terakhir_aktif_at' => $p->terakhir_aktif_at,
                'login_at'         => $p->created_at,
                'perangkat_ini'    => (int) $p->token_id === (int) $tokenId,
            ])
            ->values();

        return response()->json([
            'data' => [
                'pengguna'  => ['nama' => $pengguna->nama, 'nisn' => $pengguna->username],
                'perangkat' => $perangkat,
            ],
        ]);
    }

    /**
     * DELETE /api/v1/sesi/{id}  — keluarkan satu perangkat.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $pengguna = $request->user();

        $sesi = DB::table('akun_perangkat_login')
            ->where('id', $id)->where('pengguna_id', $pengguna->id)->first();

        if (! $sesi) {
            return response()->json(['pesan' => 'Sesi tidak ditemukan.'], 404);
        }

        if ($sesi->token_id) {
            $pengguna->tokens()->where('id', $sesi->token_id)->delete();
        }
        DB::table('akun_perangkat_login')->where('id', $sesi->id)->delete();

        return response()->json(['pesan' => 'Perangkat berhasil dikeluarkan.']);
    }

    /**
     * DELETE /api/v1/sesi  — "Logout Semua" (termasuk perangkat ini).
     */
    public function destroyAll(Request $request): JsonResponse
    {
        $pengguna = $request->user();

        $pengguna->tokens()->delete();
        DB::table('akun_perangkat_login')->where('pengguna_id', $pengguna->id)->delete();

        return response()->json(['pesan' => 'Berhasil keluar dari semua perangkat.']);
    }
}