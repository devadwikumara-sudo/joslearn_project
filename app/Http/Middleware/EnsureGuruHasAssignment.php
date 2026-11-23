<?php

namespace App\Http\Middleware;

use App\Services\AksesGuru;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureGuruHasAssignment
{
    public function __construct(private AksesGuru $aksesGuru) {}

    public function handle(Request $request, Closure $next): Response
    {
        $pengguna = $request->user();

        abort_unless(
            $pengguna
                && ($this->aksesGuru->adalahGuruMapel($pengguna) || $this->aksesGuru->adalahWali($pengguna)),
            403,
            'Akun guru belum memiliki penugasan.'
        );

        return $next($request);
    }
}
