<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(\App\Services\LicenseClient::class);
    }

    public function boot(): void
    {
        // Set application locale from session (defaults to config locale = id fallback)
        if (!$this->app->runningInConsole() && session()->has('locale')) {
            app()->setLocale(session('locale'));
        }

        Paginator::defaultView('partials.pagination');

        \Illuminate\Support\Facades\Blade::directive('money', function ($expression) {
            return "<?php echo \\App\\Models\\Currency::format($expression); ?>";
        });

        // super_admin auto-grant all permissions
        \Illuminate\Support\Facades\Gate::before(function ($user, $ability) {
            if ($user->hasRole('super_admin')) {
                return true;
            }
            return null; // fallthrough ke check normal
        });

        // Share settings ke semua views
        \Illuminate\Support\Facades\View::composer('*', function ($view) {
            $service = app(\App\Services\SettingsService::class);
            $view->with('appSettings', $service->getCompanyInfo());
        });
    }
}
