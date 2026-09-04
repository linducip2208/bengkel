<?php

namespace App\Providers;

use App\Services\LicenseClient;
use App\Services\SettingsService;
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

        Paginator::defaultView('partials.pagination');

        Blade::directive('money', function ($expression) {
            return "<?php echo \\App\\Models\\Currency::format($expression); ?>";
        });

        // super_admin auto-grant all permissions
        Gate::before(function ($user, $ability) {
            if ($user->hasRole('super_admin')) {
                return true;
            }

            return null; // fallthrough ke check normal
        });

        /*
         * Server-side action gates — defense in depth on top of route
         * middleware. Route middleware hides/blocks endpoints; these gates
         * are enforced inside controllers via $this->authorize() so a missed
         * route definition can never expose a sensitive action.
         */
        $roleGates = [
            // Kasir has invoice.create/edit permissions in PermissionSeeder and
            // must be able to use the web invoice form as well.
            'invoices.manage' => ['admin', 'manager', 'kasir'],
            'invoices.delete' => ['admin'],
            'estimates.override' => ['admin', 'manager'],
            'estimates.convert_invoice' => ['admin', 'manager', 'kasir'],
            'payments.process' => ['admin', 'manager', 'kasir'],
            'stock-adjustments.approve' => ['admin', 'manager'],
            'users.manage' => ['admin'],
            'roles.manage' => [],
            'journals.manage' => ['admin', 'manager'],
        ];

        foreach ($roleGates as $ability => $roles) {
            Gate::define($ability, function ($user) use ($roles) {
                return $user->hasAnyRole($roles);
            });
        }

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
