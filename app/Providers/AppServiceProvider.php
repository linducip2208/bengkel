<?php

namespace App\Providers;

use App\Services\LicenseClient;
use App\Services\SettingsService;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(LicenseClient::class);
    }

    public function boot(): void
    {
        // Set application locale from session (defaults to config locale = id fallback)
        if (! $this->app->runningInConsole() && session()->has('locale')) {
            app()->setLocale(session('locale'));
        }

        // Tanggal Indonesia konsisten (Januari, Februari, ...): Carbon
        // mengikuti locale aplikasi untuk translatedFormat().
        Carbon::setLocale(app()->getLocale());
        CarbonImmutable::setLocale(app()->getLocale());

        Paginator::defaultView('partials.pagination');

        Blade::directive('money', function ($expression) {
            return "<?php echo \\App\\Models\\Currency::format($expression); ?>";
        });

        // super_admin auto-grant all permissions (guard eksplisit: lihat User::hasWebPermission).
        Gate::before(function ($user, $ability) {
            if ($user->is_active === false) {
                return false;
            }
            if ($user->hasRole('super_admin', 'web')) {
                return true;
            }

            return null; // fallthrough ke check normal
        });

        /*
         * Server-side action gates — defense in depth on top of route
         * middleware. PENTING: gate HARUS cek Spatie permission LANGSUNG via
         * hasAnyPermission (BUKAN $user->can, karena gate yang bernama sama
         * dengan permission — mis. estimates.override — akan rekursi tak
         * berujung), supaya edit di /roles benar-benar berdampak. Nama role
         * hanya dipakai sebagai default awal di PermissionSeeder.
         */
        Gate::define('invoices.manage', fn ($user) => $user->hasWebPermission('invoice.create', 'invoice.edit'));
        Gate::define('invoices.delete', fn ($user) => $user->hasWebPermission('invoice.delete'));
        Gate::define('estimates.override', fn ($user) => $user->hasWebPermission('estimates.override'));
        Gate::define('estimates.convert_invoice', fn ($user) => $user->hasWebPermission('estimates.convert_invoice'));
        Gate::define('payments.process', fn ($user) => $user->hasWebPermission('payment.process'));
        Gate::define('stock-adjustments.approve', fn ($user) => $user->hasWebPermission('stock-adjustment.approve'));
        Gate::define('users.manage', fn ($user) => $user->hasWebPermission('user.edit'));
        Gate::define('roles.manage', fn ($user) => $user->hasWebPermission('role.edit'));
        Gate::define('journals.manage', fn ($user) => $user->hasWebPermission('finance-journal.create'));

        // Share settings ke semua views
        View::composer('*', function ($view) {
            try {
                $service = app(SettingsService::class);
                $view->with('appSettings', $service->getCompanyInfo());
            } catch (\Throwable $e) {
                $view->with('appSettings', [
                    'name' => config('app.name'),
                    'address' => '',
                    'phone' => '',
                    'email' => '',
                    'logo' => '',
                    'tax_id' => '',
                ]);
            }
        });
    }
}
