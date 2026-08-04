<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Aplikasi Bengkel Terbaik API Documentation
    |--------------------------------------------------------------------------
    |
    | All API endpoints are prefixed with /api and use Laravel Sanctum for
    | authentication. Protected routes require Bearer token in Authorization
    | header.
    |
    | Base URL: https://{tenant-subdomain}.yourdomain.com/api
    | Auth Type: Bearer Token (Laravel Sanctum)
    |
    */

    'auth' => [
        'login' => [
            'method' => 'POST',
            'uri' => '/api/login',
            'auth' => false,
            'body' => [
                'email' => 'string|required',
                'password' => 'string|required',
            ],
            'response' => [
                'user' => 'object',
                'token' => 'string',
            ],
        ],
        'register' => [
            'method' => 'POST',
            'uri' => '/api/register',
            'auth' => false,
            'body' => [
                'name' => 'string|required',
                'email' => 'string|required|unique',
                'password' => 'string|required|min:8',
            ],
            'response' => [
                'user' => 'object',
                'token' => 'string',
            ],
        ],
        'logout' => [
            'method' => 'POST',
            'uri' => '/api/logout',
            'auth' => true,
        ],
        'me' => [
            'method' => 'GET',
            'uri' => '/api/me',
            'auth' => true,
            'response' => 'User with roles & permissions',
        ],
    ],

    'modules' => [
        'dashboard' => ['resource' => '/api/dashboard', 'only' => ['index']],
        'customers' => ['resource' => '/api/customers', 'extra' => ['POST /import']],
        'vehicles' => ['resource' => '/api/vehicles', 'extra' => ['POST /{vehicle}/images']],
        'vehicle-types' => ['resource' => '/api/vehicle-types'],
        'vehicle-brands' => ['resource' => '/api/vehicle-brands'],
        'fuel-types' => ['resource' => '/api/fuel-types'],
        'colors' => ['resource' => '/api/colors'],
        'product-types' => ['resource' => '/api/product-types'],
        'product-units' => ['resource' => '/api/product-units'],
        'payment-methods' => ['resource' => '/api/payment-methods'],
        'tax-rates' => ['resource' => '/api/tax-rates'],
        'repair-categories' => ['resource' => '/api/repair-categories'],
        'observation-types' => ['resource' => '/api/observation-types'],
        'suppliers' => ['resource' => '/api/suppliers'],
        'products' => ['resource' => '/api/products'],
        'services' => ['resource' => '/api/services', 'extra' => ['POST /{service}/complete', 'POST /{service}/images']],
        'jobcards' => ['routes' => ['GET /api/jobcards', 'POST /api/services/{service}/jobcard']],
        'invoices' => ['resource' => '/api/invoices', 'extra' => ['POST /{invoice}/payment', 'GET /{invoice}/pdf']],
        'purchases' => ['resource' => '/api/purchases'],
        'sales' => ['resource' => '/api/sales'],
        'incomes' => ['resource' => '/api/incomes'],
        'expenses' => ['resource' => '/api/expenses'],
        'reports' => [
            'routes' => [
                'GET /api/reports',
                'GET /api/reports/service',
                'GET /api/reports/sales',
                'GET /api/reports/stock',
                'GET /api/reports/financial',
            ],
        ],
        'settings' => ['routes' => ['GET /api/settings', 'PUT /api/settings']],
        'gate-passes' => ['resource' => '/api/gate-passes'],
        'notification-templates' => ['resource' => '/api/notification-templates'],
        'reminders' => ['resource' => '/api/reminders'],
    ],

    'rbac' => [
        'super_admin' => ['*'], // All permissions
        'tenant_admin' => ['manage_users', 'manage_settings', 'view_reports', '*'],
        'mechanic' => ['view_services', 'update_services', 'view_jobcards', 'create_jobcards'],
        'cashier' => ['view_invoices', 'create_invoices', 'create_payments', 'view_sales'],
        'customer' => ['view_own_vehicles', 'view_own_services', 'view_own_invoices'],
    ],

];
