<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Batasi per_page paginasi API (1-100) agar satu request tidak bisa
 * menarik ratusan ribu baris sekaligus. Nilai di luar rentang dijepit,
 * bukan ditolak — daftar mobile yang memakai default 20 tidak berubah.
 */
class ClampPerPage
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->has('per_page')) {
            $request->merge(['per_page' => max(1, min(100, (int) $request->input('per_page', 20)))]);
        }

        return $next($request);
    }
}
