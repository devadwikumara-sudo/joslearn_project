<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Membatasi API mobile hanya untuk akun siswa yang aktif.
 * Dipakai langsung lewat nama class di routes/api.php (tidak perlu alias).
 */
class EnsureSiswaOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        $pengguna = $request->user();

        if (! $pengguna || $pengguna->peran !== 'siswa' || ! $pengguna->aktif) {
            return response()->json([
                'pesan' => 'Akses ini khusus untuk akun siswa yang aktif.',
            ], 403);
        }

        return $next($request);
    }
}