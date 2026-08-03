<?php

namespace App\Http\Middleware;

use App\Services\LicenseClient;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Block ALL routes (admin, user, storefront, anything) until the app is paired
 * to a license. Only `/__pair*` and a small dev-allowlist are accessible
 * without a valid .license.lock for the current host.
 */
class RequirePair
{
    public function __construct(private LicenseClient $client) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->shouldBypass($request)) {
            return $next($request);
        }

        $domain = strtolower($request->getHost());
        $data   = $this->client->verify($domain);

        if ($data) {
            $request->attributes->set('license', $data);
            return $next($request);
        }

        // Not paired (or invalid/tampered) — redirect to wizard
        return redirect()->to('/__pair');
    }

    private function shouldBypass(Request $request): bool
    {
        $path = '/' . ltrim($request->path(), '/');

        // Always allow the wizard itself
        if (str_starts_with($path, '/__pair')) return true;

        // Health check / debug
        if ($path === '/up') return true;
        if (str_starts_with($path, '/_debugbar')) return true;

        // Public-facing & SEO surfaces (boleh diakses sebelum license aktif)
        if ($path === '/docs' || str_starts_with($path, '/docs/')) return true;
        if ($path === '/sitemap.xml') return true;
        if ($path === '/robots.txt') return true;
        if (str_starts_with($path, '/best/')) return true;
        if (str_starts_with($path, '/alternatives-to/')) return true;
        if (str_starts_with($path, '/compare/')) return true;
        if ($path === '/login' || $path === '/logout') return true;
        // Public Booking & Customer Portal
        if ($path === '/booking' || str_starts_with($path, '/booking')) return true;
        if (str_starts_with($path, '/customer/')) return true;
        // Payment gateway webhook (POST dari PG, no session)
        if (str_starts_with($path, '/payment/callback/')) return true;
        // Public tracking service link (token-based)
        if (str_starts_with($path, '/track/')) return true;

        // Localhost dev bypass
        if (config('license.dev_bypass') && app()->environment('local')) {
            $host = $request->getHost();
            if ($this->isDevHost($host)) return true;
        }

        return false;
    }

    private function isDevHost(string $host): bool
    {
        return $host === 'localhost'
            || $host === '127.0.0.1'
            || str_ends_with($host, '.test')
            || str_ends_with($host, '.localhost');
    }
}
