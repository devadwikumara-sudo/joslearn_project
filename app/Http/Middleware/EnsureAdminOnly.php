<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminOnly
{
    public function __construct(private EnsureUserIsAdminOrGuru $webAccess) {}

    public function handle(Request $request, Closure $next): Response
    {
        return $this->webAccess->handle($request, function (Request $request) use ($next): Response {
            if (Auth::user()->peran !== 'admin') {
                abort(403, 'Akses hanya untuk administrator.');
            }

            return $next($request);
        });
    }
}
