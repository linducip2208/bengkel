<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — {{ config('app.name') }}</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">

    <style>
        :root {
            --sidebar-width: 260px;
            --sidebar-bg: #1e293b;
            --sidebar-hover: #334155;
            --sidebar-active: #3b82f6;
            --topbar-height: 56px;
        }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background-color: #f1f5f9;
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            width: var(--sidebar-width);
            background: var(--sidebar-bg);
            overflow-y: auto;
            z-index: 1030;
            transition: transform 0.3s;
        }

        .sidebar .brand {
            padding: 1rem 1.25rem;
            color: #fff;
            font-size: 1.2rem;
            font-weight: 700;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            height: var(--topbar-height);
        }

        .sidebar-nav {
            list-style: none;
            padding: 0.5rem 0;
            margin: 0;
        }

        .sidebar-nav .nav-item {
            margin: 0;
        }

        .sidebar-nav .nav-link {
            display: flex;
            align-items: center;
            padding: 0.6rem 1.25rem;
            color: #cbd5e1;
            text-decoration: none;
            font-size: 0.875rem;
            transition: all 0.15s;
            cursor: pointer;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
        }

        .sidebar-nav .nav-link:hover {
            background: var(--sidebar-hover);
            color: #fff;
        }

        .sidebar-nav .nav-link.active {
            background: var(--sidebar-active);
            color: #fff;
        }

        .sidebar-nav .nav-link i {
            width: 20px;
            text-align: center;
            margin-right: 0.6rem;
            font-size: 0.9rem;
        }

        .sidebar-nav .nav-link .fa-chevron-down {
            margin-left: auto;
            margin-right: 0;
            font-size: 0.7rem;
            transition: transform 0.2s;
        }

        .sidebar-nav .nav-link[aria-expanded="true"] .fa-chevron-down {
            transform: rotate(180deg);
        }

        .sidebar-nav .submenu {
            list-style: none;
            padding-left: 2.8rem;
            margin: 0;
        }

        .sidebar-nav .submenu .nav-link {
            padding: 0.4rem 1.25rem;
            font-size: 0.82rem;
        }

        .topbar {
            position: fixed;
            top: 0;
            left: var(--sidebar-width);
            right: 0;
            height: var(--topbar-height);
            background: #fff;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 1.5rem;
            z-index: 1020;
        }

        .topbar .tenant-name {
            font-weight: 600;
            color: #475569;
            font-size: 0.9rem;
        }

        .topbar .dropdown-toggle {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .main-content {
            margin-left: var(--sidebar-width);
            margin-top: var(--topbar-height);
            padding: 1.5rem;
            min-height: calc(100vh - var(--topbar-height));
        }

        .toast-container {
            position: fixed;
            top: calc(var(--topbar-height) + 1rem);
            right: 1.5rem;
            z-index: 1060;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.show {
                transform: translateX(0);
            }
            .topbar {
                left: 0;
            }
            .main-content {
                margin-left: 0;
            }
        }

        .card {
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            border: 1px solid #e2e8f0;
        }

        .pagination {
            margin-bottom: 0;
        }
    </style>
    @stack('styles')
</head>
<body>

    <aside class="sidebar" id="sidebar">
        <div class="brand">
            @if(!empty($appSettings['logo']))
                <img src="{{ asset('storage/' . $appSettings['logo']) }}" alt="Logo" style="height:28px;width:auto;margin-right:8px;">
            @else
                <i class="fas fa-wrench"></i>
            @endif
            {{ $appSettings['name'] ?? config('app.name') }}
        </div>
        <ul class="sidebar-nav">
            <li class="nav-item">
                <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>

            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="collapse" data-bs-target="#menuMasterData"
                    aria-expanded="{{ request()->is('vehicle-types*','vehicle-brands*','fuel-types*','colors*','product-types*','product-units*','payment-methods*','tax-rates*','repair-categories*','observation-types*') ? 'true' : 'false' }}">
                    <i class="fas fa-database"></i> Master Data
                    <i class="fas fa-chevron-down"></i>
                </button>
                <ul class="collapse submenu {{ request()->is('vehicle-types*','vehicle-brands*','fuel-types*','colors*','product-types*','product-units*','payment-methods*','tax-rates*','repair-categories*','observation-types*') ? 'show' : '' }}" id="menuMasterData">
                    <li><a href="{{ route('vehicle-types.index') }}" class="nav-link {{ request()->is('vehicle-types*') ? 'active' : '' }}">Vehicle Types</a></li>
                    <li><a href="{{ route('vehicle-brands.index') }}" class="nav-link {{ request()->is('vehicle-brands*') ? 'active' : '' }}">Vehicle Brands</a></li>
                    <li><a href="{{ route('fuel-types.index') }}" class="nav-link {{ request()->is('fuel-types*') ? 'active' : '' }}">Fuel Types</a></li>
                    <li><a href="{{ route('colors.index') }}" class="nav-link {{ request()->is('colors*') ? 'active' : '' }}">Colors</a></li>
                    <li><a href="{{ route('product-types.index') }}" class="nav-link {{ request()->is('product-types*') ? 'active' : '' }}">Product Types</a></li>
                    <li><a href="{{ route('product-units.index') }}" class="nav-link {{ request()->is('product-units*') ? 'active' : '' }}">Product Units</a></li>
                    <li><a href="{{ route('payment-methods.index') }}" class="nav-link {{ request()->is('payment-methods*') ? 'active' : '' }}">Payment Methods</a></li>
                    <li><a href="{{ route('tax-rates.index') }}" class="nav-link {{ request()->is('tax-rates*') ? 'active' : '' }}">Tax Rates</a></li>
                    <li><a href="{{ route('repair-categories.index') }}" class="nav-link {{ request()->is('repair-categories*') ? 'active' : '' }}">Repair Categories</a></li>
                    <li><a href="{{ route('observation-types.index') }}" class="nav-link {{ request()->is('observation-types*') ? 'active' : '' }}">Observation Types</a></li>
                </ul>
            </li>

            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="collapse" data-bs-target="#menuCustomer"
                    aria-expanded="{{ request()->is('customers*') ? 'true' : 'false' }}">
                    <i class="fas fa-users"></i> Customer
                    <i class="fas fa-chevron-down"></i>
                </button>
                <ul class="collapse submenu {{ request()->is('customers*') ? 'show' : '' }}" id="menuCustomer">
                    <li><a href="{{ route('customers.index') }}" class="nav-link {{ request()->is('customers') ? 'active' : '' }}">All Customers</a></li>
                    <li><a href="{{ route('customers.create') }}" class="nav-link {{ request()->is('customers/create') ? 'active' : '' }}">Add Customer</a></li>
                </ul>
            </li>

            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="collapse" data-bs-target="#menuVehicle"
                    aria-expanded="{{ request()->is('vehicles*') ? 'true' : 'false' }}">
                    <i class="fas fa-car"></i> Vehicle
                    <i class="fas fa-chevron-down"></i>
                </button>
                <ul class="collapse submenu {{ request()->is('vehicles*') ? 'show' : '' }}" id="menuVehicle">
                    <li><a href="{{ route('vehicles.index') }}" class="nav-link {{ request()->is('vehicles') ? 'active' : '' }}">All Vehicles</a></li>
                    <li><a href="{{ route('vehicles.create') }}" class="nav-link {{ request()->is('vehicles/create') ? 'active' : '' }}">Add Vehicle</a></li>
                </ul>
            </li>

            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="collapse" data-bs-target="#menuService"
                    aria-expanded="{{ request()->routeIs('services.*','jobcards.*') ? 'true' : 'false' }}">
                    <i class="fas fa-tools"></i> Service
                    <i class="fas fa-chevron-down"></i>
                </button>
                <ul class="collapse submenu {{ request()->routeIs('services.*','jobcards.*') ? 'show' : '' }}" id="menuService">
                    <li><a href="{{ route('services.index') }}" class="nav-link {{ request()->routeIs('services.index') ? 'active' : '' }}">All Services</a></li>
                    <li><a href="{{ route('jobcards.index') }}" class="nav-link {{ request()->routeIs('jobcards.*') ? 'active' : '' }}">Jobcards</a></li>
                    <li><a href="{{ route('services.create') }}" class="nav-link {{ request()->routeIs('services.create') ? 'active' : '' }}">Add Service</a></li>
                </ul>
            </li>

            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="collapse" data-bs-target="#menuGatePass"
                    aria-expanded="{{ request()->routeIs('gate-passes.*') ? 'true' : 'false' }}">
                    <i class="fas fa-ticket-alt"></i> Gate Passes
                    <i class="fas fa-chevron-down"></i>
                </button>
                <ul class="collapse submenu {{ request()->routeIs('gate-passes.*') ? 'show' : '' }}" id="menuGatePass">
                    <li><a href="{{ route('gate-passes.index') }}" class="nav-link {{ request()->routeIs('gate-passes.index') ? 'active' : '' }}">All Gate Passes</a></li>
                    <li><a href="{{ route('gate-passes.create') }}" class="nav-link {{ request()->routeIs('gate-passes.create') ? 'active' : '' }}">Add Gate Pass</a></li>
                </ul>
            </li>

            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="collapse" data-bs-target="#menuInvoice"
                    aria-expanded="{{ request()->routeIs('invoices.*','payments.*') ? 'true' : 'false' }}">
                    <i class="fas fa-file-invoice"></i> Invoice
                    <i class="fas fa-chevron-down"></i>
                </button>
                <ul class="collapse submenu {{ request()->routeIs('invoices.*','payments.*') ? 'show' : '' }}" id="menuInvoice">
                    <li><a href="{{ route('invoices.index') }}" class="nav-link {{ request()->routeIs('invoices.index') ? 'active' : '' }}">All Invoices</a></li>
                    <li><a href="{{ route('invoices.create') }}" class="nav-link {{ request()->routeIs('invoices.create') ? 'active' : '' }}">Add Invoice</a></li>
                </ul>
            </li>

            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="collapse" data-bs-target="#menuInventory"
                    aria-expanded="{{ request()->routeIs('products.*','suppliers.*','purchases.*') ? 'true' : 'false' }}">
                    <i class="fas fa-boxes"></i> Inventory
                    <i class="fas fa-chevron-down"></i>
                </button>
                <ul class="collapse submenu {{ request()->routeIs('products.*','suppliers.*','purchases.*') ? 'show' : '' }}" id="menuInventory">
                    <li><a href="{{ route('products.index') }}" class="nav-link {{ request()->routeIs('products.index') ? 'active' : '' }}">Products</a></li>
                    <li><a href="{{ route('products.stock-opname') }}" class="nav-link {{ request()->routeIs('products.stock-opname') ? 'active' : '' }}">Stock</a></li>
                    <li><a href="{{ route('suppliers.index') }}" class="nav-link {{ request()->routeIs('suppliers.*') ? 'active' : '' }}">Suppliers</a></li>
                    <li><a href="{{ route('purchases.index') }}" class="nav-link {{ request()->routeIs('purchases.*') ? 'active' : '' }}">Purchases</a></li>
                </ul>
            </li>

            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="collapse" data-bs-target="#menuSales"
                    aria-expanded="{{ request()->routeIs('sales.*') ? 'true' : 'false' }}">
                    <i class="fas fa-shopping-cart"></i> Sales
                    <i class="fas fa-chevron-down"></i>
                </button>
                <ul class="collapse submenu {{ request()->routeIs('sales.*') ? 'show' : '' }}" id="menuSales">
                    <li><a href="{{ route('sales.index') }}" class="nav-link {{ request()->routeIs('sales.index') ? 'active' : '' }}">All Sales</a></li>
                    <li><a href="{{ route('sales.create') }}" class="nav-link {{ request()->routeIs('sales.create') ? 'active' : '' }}">Add Sale</a></li>
                </ul>
            </li>

            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="collapse" data-bs-target="#menuFinancial"
                    aria-expanded="{{ request()->routeIs('incomes.*','expenses.*') ? 'true' : 'false' }}">
                    <i class="fas fa-chart-line"></i> Financial
                    <i class="fas fa-chevron-down"></i>
                </button>
                <ul class="collapse submenu {{ request()->routeIs('incomes.*','expenses.*') ? 'show' : '' }}" id="menuFinancial">
                    <li><a href="{{ route('incomes.index') }}" class="nav-link {{ request()->routeIs('incomes.*') ? 'active' : '' }}">Income</a></li>
                    <li><a href="{{ route('expenses.index') }}" class="nav-link {{ request()->routeIs('expenses.*') ? 'active' : '' }}">Expenses</a></li>
                </ul>
            </li>

            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="collapse" data-bs-target="#menuReports"
                    aria-expanded="{{ request()->routeIs('reports.*') ? 'true' : 'false' }}">
                    <i class="fas fa-chart-bar"></i> Reports
                    <i class="fas fa-chevron-down"></i>
                </button>
                <ul class="collapse submenu {{ request()->routeIs('reports.*') ? 'show' : '' }}" id="menuReports">
                    <li><a href="{{ route('reports.service') }}" class="nav-link {{ request()->routeIs('reports.service') ? 'active' : '' }}">Service Report</a></li>
                    <li><a href="{{ route('reports.sales') }}" class="nav-link {{ request()->routeIs('reports.sales') ? 'active' : '' }}">Sales Report</a></li>
                    <li><a href="{{ route('reports.stock') }}" class="nav-link {{ request()->routeIs('reports.stock') ? 'active' : '' }}">Stock Report</a></li>
                    <li><a href="{{ route('reports.financial') }}" class="nav-link {{ request()->routeIs('reports.financial') ? 'active' : '' }}">Financial Report</a></li>
                </ul>
            </li>

            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="collapse" data-bs-target="#menuSettings"
                    aria-expanded="{{ request()->routeIs('settings.*','notification-templates.*','reminders.*') ? 'true' : 'false' }}">
                    <i class="fas fa-cog"></i> Settings
                    <i class="fas fa-chevron-down"></i>
                </button>
                <ul class="collapse submenu {{ request()->routeIs('settings.*','notification-templates.*','reminders.*') ? 'show' : '' }}" id="menuSettings">
                    <li><a href="{{ route('settings.index') }}" class="nav-link {{ request()->routeIs('settings.*') ? 'active' : '' }}">General Settings</a></li>
                    <li><a href="{{ route('notification-templates.index') }}" class="nav-link {{ request()->routeIs('notification-templates.*') ? 'active' : '' }}">Notification Templates</a></li>
                    <li><a href="{{ route('reminders.index') }}" class="nav-link {{ request()->routeIs('reminders.*') ? 'active' : '' }}">Reminders</a></li>
                </ul>
            </li>
        </ul>
    </aside>

    <nav class="topbar">
        <button class="btn btn-sm btn-outline-secondary d-md-none" onclick="document.getElementById('sidebar').classList.toggle('show')">
            <i class="fas fa-bars"></i>
        </button>
        <span class="tenant-name">{{ config('app.name') }}</span>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('docs.index') }}" target="_blank" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-book me-1"></i> Docs
            </a>
            <div class="dropdown">
                <button class="btn btn-light dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="fas fa-user-circle me-1"></i> {{ auth()->user()?->name ?? 'Admin' }}
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a href="#" class="dropdown-item"><i class="fas fa-user me-2"></i>Profile</a></li>
                    <li><a href="#" class="dropdown-item"><i class="fas fa-cog me-2"></i>Settings</a></li>
                    <li><a href="{{ route('docs.index') }}" target="_blank" class="dropdown-item"><i class="fas fa-book me-2"></i>Tutorial</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="dropdown-item"><i class="fas fa-sign-out-alt me-2"></i>Logout</button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="main-content">
        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-1"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        @yield('content')
    </main>

    <div class="toast-container">
        @if(session('toast'))
        <div class="toast align-items-center text-bg-success border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body">{{ session('toast') }}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
        @endif
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-show toasts
        document.querySelectorAll('.toast').forEach(t => new bootstrap.Toast(t).show());
    </script>
    @stack('scripts')
</body>
</html>
