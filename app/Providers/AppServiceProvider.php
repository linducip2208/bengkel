<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(\App\Services\LicenseClient::class);
    }

    public function boot(): void
    {
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
    }
}
