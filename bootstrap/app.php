<?php

use App\Http\Middleware\RequirePair;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'permission' => PermissionMiddleware::class,
            'role' => RoleMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);

        $middleware->web(append: [
            RequirePair::class,
        ]);

        $middleware->api(prepend: [
            EnsureFrontendRequestsAreStateful::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'payment/callback/*',
            'webhooks/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (Throwable $e, $request) {
            // Laravel handles these natively (redirect back with validation
            // errors, redirect guests to the login page). Never hijack them,
            // otherwise form validation and auth redirects break in
            // production (APP_DEBUG=false) and in CI.
            if ($e instanceof ValidationException || $e instanceof AuthenticationException) {
                return null;
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => config('app.debug') ? $e->getMessage() : 'Terjadi kesalahan pada server.',
                ], 500);
            }

            if (config('app.debug')) {
                return null; // biarkan default whoops di debug mode
            }

            // Production: friendly error page
            $status = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;
            if ($status < 400 || $status > 599) {
                $status = 500;
            }

            return response()->view('errors.generic', [
                'status' => $status,
                'message' => match ($status) {
                    404 => 'Halaman tidak ditemukan.',
                    403 => 'Anda tidak punya akses ke halaman ini.',
                    419 => 'Halaman kedaluwarsa (session expired). Silakan refresh & coba lagi.',
                    500 => 'Terjadi kesalahan pada server. Silakan coba lagi atau hubungi admin.',
                    default => 'Terjadi kesalahan.',
                },
            ], $status);
        });
    })->create();
