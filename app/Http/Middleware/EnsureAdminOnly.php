<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check() || Auth::user()->peran !== 'admin') {
            abort(403, 'Akses hanya untuk administrator.');
        }

        return $next($request);
    }
}

