<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdminOrGuru
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        if (! $user->aktif) {
            Auth::logout();
            return redirect()->route('login')->withErrors([
                'username' => 'Akun Anda tidak aktif. Hubungi administrator.',
            ]);
        }

        if (! in_array($user->peran, ['admin', 'guru'])) {
            Auth::logout();
            return redirect()->route('login')->withErrors([
                'username' => 'Akun ini tidak memiliki akses ke portal web.',
            ]);
        }

        // Force password change if required
        if ($user->wajib_ganti_password && ! $request->routeIs(['ganti-password*', 'buat-kata-sandi-baru*', 'selesai-kata-sandi*'])) {
            return redirect()->route('buat-kata-sandi-baru');
        }

        return $next($request);
    }
}
