<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Otorisasi permission eksplisit guard `web`, semantik ANY (salah satu cukup).
 *
 * Dipakai sebagai `can.perm:invoice.create,invoice.edit`.
 * Alasan tidak pakai middleware `can:` bawaan Laravel: `can:` lewat Gate
 * yang me-resolve guard dari default auth guard — request API (sanctum)
 * akan me-resolve ke guard `sanctum` sehingga SELALU 403 walau permission
 * web-nya ada. Helper User::hasWebPermission() immun terhadap itu.
 */
class AuthorizePermissions
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        abort_unless($user && $user->hasWebPermission(...$permissions), 403,
            'Anda tidak punya akses untuk aksi ini ('.implode(' / ', $permissions).').');

        return $next($request);
    }
}
