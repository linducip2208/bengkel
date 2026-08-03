<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — Bengkel Paten</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

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

        /* ===== SIDEBAR OVERLAY (mobile) ===== */
        .sidebar-overlay {
            display: none;
            position: fixed; inset: 0; background: rgba(0,0,0,0.5);
            z-index: 1025;
        }
        .sidebar-overlay.show { display: block; }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                width: 280px;
                box-shadow: 4px 0 16px rgba(0,0,0,0.25);
            }
            .sidebar.show {
                transform: translateX(0);
            }
            .topbar {
                left: 0;
            }
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
            .topbar .tenant-name { display: none; }
            .topbar { padding: 0 0.75rem; }
            .topbar .form-select-sm { max-width: 120px; font-size: 0.8rem; }
            .card { border-radius: 8px; }
            .card-body { padding: 1rem; }
        }

        /* ===== TABLET (769px - 1024px) ===== */
        @media (min-width: 769px) and (max-width: 1024px) {
            .sidebar {
                width: 220px;
            }
            :root { --sidebar-width: 220px; }
            .sidebar .brand { font-size: 1rem; padding: 1rem 0.9rem; }
            .sidebar-nav .nav-link { padding: 0.5rem 0.9rem; font-size: 0.8rem; }
            .sidebar-nav .submenu { padding-left: 2.2rem; }
            .sidebar-nav .submenu .nav-link { font-size: 0.76rem; }
            .main-content { padding: 1.25rem; }
            .topbar { padding: 0 1rem; }
        }

        /* ===== MOBILE TABLE SCROLL ===== */
        @media (max-width: 640px) {
            .table-responsive {
                -webkit-overflow-scrolling: touch;
            }
            .table td, .table th {
                white-space: nowrap;
                padding: 0.5rem 0.6rem;
                font-size: 0.82rem;
            }
            .btn-sm { font-size: 0.78rem; padding: 0.25rem 0.45rem; min-height: 32px; }
            h4 { font-size: 1.25rem; }
            .d-flex.justify-content-between {
                flex-direction: column;
                gap: 0.75rem;
                align-items: flex-start !important;
            }
            form .row.g-3 > div {
                width: 100%;
            }
            .input-group { flex-wrap: wrap; }
            .input-group > .form-control,
            .input-group > .form-select { min-width: 120px; }
        }

        /* ===== TOUCH TARGETS (WCAG 2.5.5) ===== */
        @media (pointer: coarse) {
            .sidebar-nav .nav-link { min-height: 44px; }
            .btn-sm { min-width: 38px; min-height: 38px; }
            .dropdown-item { min-height: 40px; padding: 0.5rem 1rem; display: flex; align-items: center; }
        }

        /* ===== REDUCED MOTION ===== */
        @media (prefers-reduced-motion: reduce) {
            .sidebar { transition: none; }
            * { animation-duration: 0.01ms !important; transition-duration: 0.01ms !important; }
        }

        .card {
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            border: 1px solid #e2e8f0;
        }

        .pagination {
            margin-bottom: 0;
        }

        /* ===== Action button enhancements ===== */
        .table .btn-sm {
            min-width: 32px;
            min-height: 32px;
            padding: 0.3rem 0.55rem;
            font-size: 0.85rem;
            font-weight: 500;
            border-width: 1.5px;
        }
        .table .btn-sm i {
            font-size: 0.95rem;
        }
        /* High-contrast outline action buttons */
        .table .btn-outline-warning,
        .table .btn-warning {
            background-color: #fef3c7;
            color: #92400e;
            border-color: #f59e0b;
        }
        .table .btn-outline-warning:hover,
        .table .btn-warning:hover {
            background-color: #f59e0b;
            color: #fff;
        }
        .table .btn-outline-danger,
        .table .btn-danger {
            background-color: #fee2e2;
            color: #991b1b;
            border-color: #ef4444;
        }
        .table .btn-outline-danger:hover,
        .table .btn-danger:hover {
            background-color: #ef4444;
            color: #fff;
        }
        .table .btn-outline-info,
        .table .btn-info {
            background-color: #dbeafe;
            color: #1e40af;
            border-color: #3b82f6;
        }
        .table .btn-outline-info:hover,
        .table .btn-info:hover {
            background-color: #3b82f6;
            color: #fff;
        }
        .table .btn-outline-success,
        .table .btn-success {
            background-color: #d1fae5;
            color: #065f46;
            border-color: #10b981;
        }
        .table .btn-outline-success:hover,
        .table .btn-success:hover {
            background-color: #10b981;
            color: #fff;
        }
        .table .btn-outline-secondary,
        .table .btn-secondary {
            background-color: #e5e7eb;
            color: #374151;
            border-color: #6b7280;
        }
        .table .btn-outline-secondary:hover,
        .table .btn-secondary:hover {
            background-color: #6b7280;
            color: #fff;
        }
        /* Tooltip-style label on hover */
        .table .btn-sm[title] { position: relative; }
        .table .btn-sm[title]:hover::after {
            content: attr(title);
            position: absolute;
            bottom: calc(100% + 6px);
            left: 50%;
            transform: translateX(-50%);
            background: #1f2937;
            color: #fff;
            padding: 0.25rem 0.55rem;
            border-radius: 4px;
            font-size: 0.72rem;
            font-weight: 500;
            white-space: nowrap;
            z-index: 10;
            pointer-events: none;
        }
        /* Button group spacing */
        .table .d-flex.gap-1 { gap: 0.35rem !important; }
    </style>
    @stack('styles')
</head>
<body>

    @auth
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="document.getElementById('sidebar').classList.remove('show'); this.classList.remove('show')"></div>
    <aside class="sidebar" id="sidebar">
        <div class="brand">
            <i class="fas fa-wrench"></i> Bengkel Paten
            <button class="btn btn-sm btn-link text-white-50 ms-auto d-md-none" style="text-decoration:none;" onclick="document.getElementById('sidebar').classList.remove('show'); document.getElementById('sidebarOverlay').classList.remove('show')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <ul class="sidebar-nav">
            <li class="nav-item">
                <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>

            @can('dashboard.view')
            <li class="nav-item">
                <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
            @endcan

            @canany(['branch.view','holiday.view','washbay.view'])
            {{-- Cabang --}}
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="collapse" data-bs-target="#menuBranches"
                    aria-expanded="{{ request()->is('branches*','holidays*','washbays*','business-hours*') ? 'true' : 'false' }}">
                    <i class="fas fa-building"></i> Cabang
                    <i class="fas fa-chevron-down"></i>
                </button>
                <ul class="collapse submenu {{ request()->is('branches*','holidays*','washbays*','business-hours*') ? 'show' : '' }}" id="menuBranches">
                    @can('branch.view')<li><a href="{{ route('branches.index') }}" class="nav-link {{ request()->is('branches*') ? 'active' : '' }}"><i class="fas fa-store-alt me-1"></i> Daftar Cabang</a></li>@endcan
                    @can('holiday.view')<li><a href="{{ route('holidays.index') }}" class="nav-link {{ request()->is('holidays*') ? 'active' : '' }}"><i class="fas fa-calendar-times me-1"></i> Hari Libur</a></li>@endcan
                    @can('washbay.view')<li><a href="{{ route('washbays.index') }}" class="nav-link {{ request()->is('washbays*') ? 'active' : '' }}"><i class="fas fa-shower me-1"></i> Washbay / Slot</a></li>@endcan
                </ul>
            </li>
            @endcanany

            @can('master-data.view')
            {{-- Master Data --}}
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="collapse" data-bs-target="#menuMasterData"
                    aria-expanded="{{ request()->is('vehicle-types*','vehicle-brands*','fuel-types*','colors*','product-types*','product-units*','payment-methods*','tax-rates*','repair-categories*','observation-types*','observation-points*','inspection-points*','checkout-categories*','service-packages*') ? 'true' : 'false' }}">
                    <i class="fas fa-database"></i> Master Data
                    <i class="fas fa-chevron-down"></i>
                </button>
                <ul class="collapse submenu {{ request()->is('vehicle-types*','vehicle-brands*','fuel-types*','colors*','product-types*','product-units*','payment-methods*','tax-rates*','repair-categories*','observation-types*','observation-points*','inspection-points*','checkout-categories*','service-packages*') ? 'show' : '' }}" id="menuMasterData">
                    <li><a href="{{ route('vehicle-types.index') }}" class="nav-link {{ request()->is('vehicle-types*') ? 'active' : '' }}"><i class="fas fa-truck-pickup me-1"></i> Vehicle Types</a></li>
                    <li><a href="{{ route('vehicle-brands.index') }}" class="nav-link {{ request()->is('vehicle-brands*') ? 'active' : '' }}"><i class="fas fa-trademark me-1"></i> Vehicle Brands</a></li>
                    <li><a href="{{ route('fuel-types.index') }}" class="nav-link {{ request()->is('fuel-types*') ? 'active' : '' }}"><i class="fas fa-gas-pump me-1"></i> Fuel Types</a></li>
                    <li><a href="{{ route('colors.index') }}" class="nav-link {{ request()->is('colors*') ? 'active' : '' }}"><i class="fas fa-palette me-1"></i> Colors</a></li>
                    <li><a href="{{ route('product-types.index') }}" class="nav-link {{ request()->is('product-types*') ? 'active' : '' }}"><i class="fas fa-tags me-1"></i> Product Types</a></li>
                    <li><a href="{{ route('product-units.index') }}" class="nav-link {{ request()->is('product-units*') ? 'active' : '' }}"><i class="fas fa-balance-scale me-1"></i> Product Units</a></li>
                    <li><a href="{{ route('payment-methods.index') }}" class="nav-link {{ request()->is('payment-methods*') ? 'active' : '' }}"><i class="fas fa-credit-card me-1"></i> Payment Methods</a></li>
                    <li><a href="{{ route('tax-rates.index') }}" class="nav-link {{ request()->is('tax-rates*') ? 'active' : '' }}"><i class="fas fa-percent me-1"></i> Tax Rates</a></li>
                    <li><a href="{{ route('repair-categories.index') }}" class="nav-link {{ request()->is('repair-categories*') ? 'active' : '' }}"><i class="fas fa-toolbox me-1"></i> Repair Categories</a></li>
                    <li><a href="{{ route('observation-types.index') }}" class="nav-link {{ request()->is('observation-types*') ? 'active' : '' }}"><i class="fas fa-clipboard-check me-1"></i> Observation Types</a></li>
                    <li><a href="{{ route('observation-points.index') }}" class="nav-link {{ request()->is('observation-points*') ? 'active' : '' }}"><i class="fas fa-list-ol me-1"></i> Observation Points</a></li>
                    <li><a href="{{ route('inspection-points.index') }}" class="nav-link {{ request()->is('inspection-points*') ? 'active' : '' }}"><i class="fas fa-search me-1"></i> Inspection Points</a></li>
                    <li><a href="{{ route('checkout-categories.index') }}" class="nav-link {{ request()->is('checkout-categories*') ? 'active' : '' }}"><i class="fas fa-check-double me-1"></i> Checkout Categories</a></li>
                    <li><a href="{{ route('service-packages.index') }}" class="nav-link {{ request()->is('service-packages*') ? 'active' : '' }}"><i class="fas fa-cubes me-1"></i> Service Packages</a></li>
                </ul>
            </li>
            @endcan

            @can('customer.view')
            {{-- Customer --}}
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="collapse" data-bs-target="#menuCustomer"
                    aria-expanded="{{ request()->is('customers*','customer-groups*') ? 'true' : 'false' }}">
                    <i class="fas fa-users"></i> Customer
                    <i class="fas fa-chevron-down"></i>
                </button>
                <ul class="collapse submenu {{ request()->is('customers*','customer-groups*') ? 'show' : '' }}" id="menuCustomer">
                    <li><a href="{{ route('customers.index') }}" class="nav-link {{ request()->is('customers') ? 'active' : '' }}"><i class="fas fa-list me-1"></i> All Customers</a></li>
                    @can('customer.create')<li><a href="{{ route('customers.create') }}" class="nav-link {{ request()->is('customers/create') ? 'active' : '' }}"><i class="fas fa-user-plus me-1"></i> Add Customer</a></li>@endcan
                    <li><a href="{{ route('customer-groups.index') }}" class="nav-link {{ request()->is('customer-groups*') ? 'active' : '' }}"><i class="fas fa-layer-group me-1"></i> Customer Groups</a></li>
                </ul>
            </li>
            @endcan

            @can('vehicle.view')
            {{-- Vehicle --}}
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="collapse" data-bs-target="#menuVehicle"
                    aria-expanded="{{ request()->is('vehicles*') ? 'true' : 'false' }}">
                    <i class="fas fa-car"></i> Vehicle
                    <i class="fas fa-chevron-down"></i>
                </button>
                <ul class="collapse submenu {{ request()->is('vehicles*') ? 'show' : '' }}" id="menuVehicle">
                    <li><a href="{{ route('vehicles.index') }}" class="nav-link {{ request()->is('vehicles') ? 'active' : '' }}"><i class="fas fa-list me-1"></i> All Vehicles</a></li>
                    @can('vehicle.create')<li><a href="{{ route('vehicles.create') }}" class="nav-link {{ request()->is('vehicles/create') ? 'active' : '' }}"><i class="fas fa-plus-circle me-1"></i> Add Vehicle</a></li>@endcan
                </ul>
            </li>
            @endcan

            @can('service.view')
            {{-- Service --}}
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="collapse" data-bs-target="#menuService"
                    aria-expanded="{{ request()->routeIs('services.*','jobcards.*') ? 'true' : 'false' }}">
                    <i class="fas fa-tools"></i> Service
                    <i class="fas fa-chevron-down"></i>
                </button>
                <ul class="collapse submenu {{ request()->routeIs('services.*','jobcards.*','subcontractors.*') ? 'show' : '' }}" id="menuService">
                    <li><a href="{{ route('services.index') }}" class="nav-link {{ request()->routeIs('services.index') ? 'active' : '' }}"><i class="fas fa-list me-1"></i> All Services</a></li>
                    @can('jobcard.view')<li><a href="{{ route('jobcards.index') }}" class="nav-link {{ request()->routeIs('jobcards.*') ? 'active' : '' }}"><i class="fas fa-clipboard-list me-1"></i> Jobcards</a></li>@endcan
                    @can('service.create')<li><a href="{{ route('services.create') }}" class="nav-link {{ request()->routeIs('services.create') ? 'active' : '' }}"><i class="fas fa-plus-circle me-1"></i> Add Service</a></li>@endcan
                    <li><a href="{{ route('subcontractors.index') }}" class="nav-link {{ request()->routeIs('subcontractors.*') ? 'active' : '' }}"><i class="fas fa-user-gear me-1"></i> Subkontraktor</a></li>
                </ul>
            </li>
            @endcan

            @can('gate-pass.view')
            {{-- Gate Passes --}}
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="collapse" data-bs-target="#menuGatePass"
                    aria-expanded="{{ request()->routeIs('gate-passes.*') ? 'true' : 'false' }}">
                    <i class="fas fa-ticket-alt"></i> Gate Passes
                    <i class="fas fa-chevron-down"></i>
                </button>
                <ul class="collapse submenu {{ request()->routeIs('gate-passes.*') ? 'show' : '' }}" id="menuGatePass">
                    <li><a href="{{ route('gate-passes.index') }}" class="nav-link {{ request()->routeIs('gate-passes.index') ? 'active' : '' }}"><i class="fas fa-list me-1"></i> All Gate Passes</a></li>
                    @can('gate-pass.create')<li><a href="{{ route('gate-passes.create') }}" class="nav-link {{ request()->routeIs('gate-passes.create') ? 'active' : '' }}"><i class="fas fa-plus-circle me-1"></i> Add Gate Pass</a></li>@endcan
                </ul>
            </li>
            @endcan

            @can('invoice.view')
            {{-- Invoice --}}
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="collapse" data-bs-target="#menuInvoice"
                    aria-expanded="{{ request()->routeIs('invoices.*','payments.*') ? 'true' : 'false' }}">
                    <i class="fas fa-file-invoice"></i> Invoice
                    <i class="fas fa-chevron-down"></i>
                </button>
                <ul class="collapse submenu {{ request()->routeIs('invoices.*','payments.*') ? 'show' : '' }}" id="menuInvoice">
                    <li><a href="{{ route('invoices.index') }}" class="nav-link {{ request()->routeIs('invoices.index') ? 'active' : '' }}"><i class="fas fa-list me-1"></i> All Invoices</a></li>
                    @can('invoice.create')<li><a href="{{ route('invoices.create') }}" class="nav-link {{ request()->routeIs('invoices.create') ? 'active' : '' }}"><i class="fas fa-plus-circle me-1"></i> Add Invoice</a></li>@endcan
                </ul>
            </li>
            @endcan

            @canany(['product.view','supplier.view','purchase.view'])
            {{-- Inventory --}}
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="collapse" data-bs-target="#menuInventory"
                    aria-expanded="{{ request()->routeIs('products.*','suppliers.*','purchases.*','equipment.*','warehouses.*') ? 'true' : 'false' }}">
                    <i class="fas fa-boxes"></i> Inventory
                    <i class="fas fa-chevron-down"></i>
                </button>
                <ul class="collapse submenu {{ request()->routeIs('products.*','suppliers.*','purchases.*','equipment.*','warehouses.*') ? 'show' : '' }}" id="menuInventory">
                    @can('product.view')<li><a href="{{ route('products.index') }}" class="nav-link {{ request()->routeIs('products.index') ? 'active' : '' }}"><i class="fas fa-box me-1"></i> Products</a></li>@endcan
                    @can('product.stock-opname')<li><a href="{{ route('products.stock-opname') }}" class="nav-link {{ request()->routeIs('products.stock-opname') ? 'active' : '' }}"><i class="fas fa-clipboard me-1"></i> Stock</a></li>@endcan
                    @can('supplier.view')<li><a href="{{ route('suppliers.index') }}" class="nav-link {{ request()->routeIs('suppliers.*') ? 'active' : '' }}"><i class="fas fa-truck me-1"></i> Suppliers</a></li>@endcan
                    @can('purchase.view')<li><a href="{{ route('purchases.index') }}" class="nav-link {{ request()->routeIs('purchases.*') ? 'active' : '' }}"><i class="fas fa-shopping-basket me-1"></i> Purchases</a></li>@endcan
                    <li><a href="{{ route('equipment.index') }}" class="nav-link {{ request()->routeIs('equipment.*') ? 'active' : '' }}"><i class="fas fa-toolbox me-1"></i> Peralatan Bengkel</a></li>
                    <li><a href="{{ route('warehouses.index') }}" class="nav-link {{ request()->routeIs('warehouses.*') ? 'active' : '' }}"><i class="fas fa-warehouse me-1"></i> Gudang</a></li>
                </ul>
            </li>
            @endcanany

            @can('pos.view')
            {{-- POS Kasir --}}
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="collapse" data-bs-target="#menuPos"
                    aria-expanded="{{ request()->routeIs('pos.*') ? 'true' : 'false' }}">
                    <i class="fas fa-cash-register"></i> POS Kasir
                    <i class="fas fa-chevron-down"></i>
                </button>
                <ul class="collapse submenu {{ request()->routeIs('pos.*') ? 'show' : '' }}" id="menuPos">
                    <li><a href="{{ route('pos.terminal') }}" class="nav-link {{ request()->routeIs('pos.terminal','pos.openForm') ? 'active' : '' }}"><i class="fas fa-desktop me-1"></i> Terminal Kasir</a></li>
                    <li><a href="{{ route('pos.sessions') }}" class="nav-link {{ request()->routeIs('pos.sessions','pos.close*') ? 'active' : '' }}"><i class="fas fa-history me-1"></i> Histori Sesi POS</a></li>
                </ul>
            </li>
            @endcan

            @can('booking.view')
            {{-- Booking Online --}}
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="collapse" data-bs-target="#menuBooking"
                    aria-expanded="{{ request()->routeIs('bookings.*') ? 'true' : 'false' }}">
                    <i class="fas fa-calendar-check"></i> Booking Online
                    <i class="fas fa-chevron-down"></i>
                </button>
                <ul class="collapse submenu {{ request()->routeIs('bookings.*') ? 'show' : '' }}" id="menuBooking">
                    <li><a href="{{ route('bookings.index') }}" class="nav-link {{ request()->routeIs('bookings.index') ? 'active' : '' }}"><i class="fas fa-list me-1"></i> Daftar Booking</a></li>
                    <li><a href="{{ route('bookings.calendar') }}" class="nav-link {{ request()->routeIs('bookings.calendar*') ? 'active' : '' }}"><i class="fas fa-calendar-alt me-1"></i> Kalender</a></li>
                </ul>
            </li>
            @endcan

            @canany(['voucher.view','loyalty.view','review.view'])
            {{-- Marketing --}}
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="collapse" data-bs-target="#menuMarketing"
                    aria-expanded="{{ request()->routeIs('vouchers.*','loyalty.*','reviews.*') ? 'true' : 'false' }}">
                    <i class="fas fa-gift"></i> Marketing
                    <i class="fas fa-chevron-down"></i>
                </button>
                <ul class="collapse submenu {{ request()->routeIs('vouchers.*','loyalty.*','reviews.*') ? 'show' : '' }}" id="menuMarketing">
                    @can('voucher.view')<li><a href="{{ route('vouchers.index') }}" class="nav-link {{ request()->routeIs('vouchers.*') ? 'active' : '' }}"><i class="fas fa-ticket-alt me-1"></i> Voucher / Promo</a></li>@endcan
                    @can('loyalty.view')<li><a href="{{ route('loyalty.index') }}" class="nav-link {{ request()->routeIs('loyalty.*') ? 'active' : '' }}"><i class="fas fa-star me-1"></i> Loyalty & Membership</a></li>@endcan
                    @can('review.view')<li><a href="{{ route('reviews.index') }}" class="nav-link {{ request()->routeIs('reviews.*') ? 'active' : '' }}"><i class="fas fa-comment-dots me-1"></i> Review & Rating</a></li>@endcan
                    <li><a href="{{ route('blog.admin.index') }}" class="nav-link {{ request()->routeIs('blog.admin.*') ? 'active' : '' }}"><i class="fas fa-blog me-1"></i> Blog Artikel</a></li>
                </ul>
            </li>
            @endcanany

            @can('warranty.view')
            {{-- Klaim Garansi --}}
            <li class="nav-item">
                <a href="{{ route('warranty-claims.index') }}" class="nav-link {{ request()->routeIs('warranty-claims.*') ? 'active' : '' }}">
                    <i class="fas fa-shield-alt"></i> Klaim Garansi
                </a>
            </li>
            @endcan

            @can('commission.view')
            {{-- HRM Teknisi --}}
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="collapse" data-bs-target="#menuHrm"
                    aria-expanded="{{ request()->routeIs('commissions.*') ? 'true' : 'false' }}">
                    <i class="fas fa-user-tie"></i> HRM Teknisi
                    <i class="fas fa-chevron-down"></i>
                </button>
                <ul class="collapse submenu {{ request()->routeIs('commissions.*') ? 'show' : '' }}" id="menuHrm">
                    @can('commission.view')<li><a href="{{ route('commissions.index') }}" class="nav-link {{ request()->routeIs('commissions.index','commissions.markPaid*') ? 'active' : '' }}"><i class="fas fa-hand-holding-usd me-1"></i> Komisi Teknisi</a></li>@endcan
                    @can('commission.report')<li><a href="{{ route('commissions.report') }}" class="nav-link {{ request()->routeIs('commissions.report') ? 'active' : '' }}"><i class="fas fa-file-invoice me-1"></i> Laporan Komisi</a></li>@endcan
                </ul>
            </li>
            @endcan

            @can('sale.view')
            {{-- Sales (Kendaraan) --}}
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="collapse" data-bs-target="#menuSales"
                    aria-expanded="{{ request()->routeIs('sales.*') ? 'true' : 'false' }}">
                    <i class="fas fa-shopping-cart"></i> Sales (Kendaraan)
                    <i class="fas fa-chevron-down"></i>
                </button>
                <ul class="collapse submenu {{ request()->routeIs('sales.*') ? 'show' : '' }}" id="menuSales">
                    <li><a href="{{ route('sales.index') }}" class="nav-link {{ request()->routeIs('sales.index') ? 'active' : '' }}"><i class="fas fa-list me-1"></i> All Sales</a></li>
                    @can('sale.create')<li><a href="{{ route('sales.create') }}" class="nav-link {{ request()->routeIs('sales.create') ? 'active' : '' }}"><i class="fas fa-plus-circle me-1"></i> Add Sale</a></li>@endcan
                </ul>
            </li>
            @endcan

            @canany(['income.view','expense.view'])
            {{-- Financial --}}
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="collapse" data-bs-target="#menuFinancial"
                    aria-expanded="{{ request()->routeIs('incomes.*','expenses.*','finance.*') ? 'true' : 'false' }}">
                    <i class="fas fa-chart-line"></i> Financial
                    <i class="fas fa-chevron-down"></i>
                </button>
                <ul class="collapse submenu {{ request()->routeIs('incomes.*','expenses.*','finance.*') ? 'show' : '' }}" id="menuFinancial">
                    @can('income.view')<li><a href="{{ route('incomes.index') }}" class="nav-link {{ request()->routeIs('incomes.*') ? 'active' : '' }}"><i class="fas fa-arrow-up me-1 text-success"></i> Income</a></li>@endcan
                    @can('expense.view')<li><a href="{{ route('expenses.index') }}" class="nav-link {{ request()->routeIs('expenses.*') ? 'active' : '' }}"><i class="fas fa-arrow-down me-1 text-danger"></i> Expenses</a></li>@endcan
                    <li><a href="{{ route('finance.coa') }}" class="nav-link {{ request()->routeIs('finance.coa*') ? 'active' : '' }}"><i class="fas fa-list-ol me-1"></i> Chart of Accounts</a></li>
                    <li><a href="{{ route('finance.journal') }}" class="nav-link {{ request()->routeIs('finance.journal*') ? 'active' : '' }}"><i class="fas fa-book me-1"></i> Journal Entry</a></li>
                </ul>
            </li>
            @endcanany

            @can('report.view')
            {{-- Reports --}}
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="collapse" data-bs-target="#menuReports"
                    aria-expanded="{{ request()->routeIs('reports.*') ? 'true' : 'false' }}">
                    <i class="fas fa-chart-bar"></i> Reports
                    <i class="fas fa-chevron-down"></i>
                </button>
                <ul class="collapse submenu {{ request()->routeIs('reports.*') ? 'show' : '' }}" id="menuReports">
                    <li><a href="{{ route('reports.service') }}" class="nav-link {{ request()->routeIs('reports.service') ? 'active' : '' }}"><i class="fas fa-tools me-1"></i> Service Report</a></li>
                    <li><a href="{{ route('reports.sales') }}" class="nav-link {{ request()->routeIs('reports.sales') ? 'active' : '' }}"><i class="fas fa-shopping-cart me-1"></i> Sales Report</a></li>
                    <li><a href="{{ route('reports.stock') }}" class="nav-link {{ request()->routeIs('reports.stock') ? 'active' : '' }}"><i class="fas fa-boxes me-1"></i> Stock Report</a></li>
                    <li><a href="{{ route('reports.financial') }}" class="nav-link {{ request()->routeIs('reports.financial') ? 'active' : '' }}"><i class="fas fa-chart-pie me-1"></i> Financial Report</a></li>
                    <li><a href="{{ route('reports.technician') }}" class="nav-link {{ request()->routeIs('reports.technician') ? 'active' : '' }}"><i class="fas fa-user-gear me-1"></i> Produktivitas Teknisi</a></li>
                    <li><a href="{{ route('reports.customer-lifetime') }}" class="nav-link {{ request()->routeIs('reports.customer-lifetime') ? 'active' : '' }}"><i class="fas fa-crown me-1"></i> Customer Lifetime</a></li>
                </ul>
            </li>
            @endcan

            @canany(['currency.view','country.view','state.view','city.view'])
            {{-- Geografi & Currency --}}
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="collapse" data-bs-target="#menuGeo"
                    aria-expanded="{{ request()->is('countries*','states*','cities*','currencies*') ? 'true' : 'false' }}">
                    <i class="fas fa-globe"></i> Geografi & Currency
                    <i class="fas fa-chevron-down"></i>
                </button>
                <ul class="collapse submenu {{ request()->is('countries*','states*','cities*','currencies*') ? 'show' : '' }}" id="menuGeo">
                    @can('currency.view')<li><a href="{{ route('currencies.index') }}" class="nav-link {{ request()->is('currencies*') ? 'active' : '' }}"><i class="fas fa-dollar-sign me-1"></i> Currencies</a></li>@endcan
                    @can('country.view')<li><a href="{{ route('countries.index') }}" class="nav-link {{ request()->is('countries*') ? 'active' : '' }}"><i class="fas fa-flag me-1"></i> Negara</a></li>@endcan
                    @can('state.view')<li><a href="{{ route('states.index') }}" class="nav-link {{ request()->is('states*') ? 'active' : '' }}"><i class="fas fa-map-pin me-1"></i> Provinsi</a></li>@endcan
                    @can('city.view')<li><a href="{{ route('cities.index') }}" class="nav-link {{ request()->is('cities*') ? 'active' : '' }}"><i class="fas fa-city me-1"></i> Kota</a></li>@endcan
                </ul>
            </li>
            @endcanany

            @canany(['stock-history.view','email-log.view','note.view'])
            {{-- Audit & Log --}}
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="collapse" data-bs-target="#menuAudit"
                    aria-expanded="{{ request()->is('stock-histories*','email-logs*','notes*') ? 'true' : 'false' }}">
                    <i class="fas fa-history"></i> Audit & Log
                    <i class="fas fa-chevron-down"></i>
                </button>
                <ul class="collapse submenu {{ request()->is('stock-histories*','email-logs*','notes*') ? 'show' : '' }}" id="menuAudit">
                    @can('stock-history.view')<li><a href="{{ route('stock-histories.index') }}" class="nav-link {{ request()->is('stock-histories*') ? 'active' : '' }}"><i class="fas fa-archive me-1"></i> Stock History</a></li>@endcan
                    @can('email-log.view')<li><a href="{{ route('email-logs.index') }}" class="nav-link {{ request()->is('email-logs*') ? 'active' : '' }}"><i class="fas fa-envelope me-1"></i> Log Notifikasi</a></li>@endcan
                    @can('note.view')<li><a href="{{ route('notes.index') }}" class="nav-link {{ request()->is('notes*') ? 'active' : '' }}"><i class="fas fa-sticky-note me-1"></i> Catatan Internal</a></li>@endcan
                </ul>
            </li>
            @endcanany

            @can('petty-cash.view')
            {{-- Kas Kecil --}}
            <li class="nav-item">
                <a href="{{ route('petty-cash.index') }}" class="nav-link {{ request()->routeIs('petty-cash.*') ? 'active' : '' }}">
                    <i class="fas fa-wallet"></i> Kas Kecil
                </a>
            </li>
            @endcan

            @canany(['user.view','role.view','activity-log.view'])
            {{-- User & Akses --}}
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="collapse" data-bs-target="#menuUsers"
                    aria-expanded="{{ request()->routeIs('users.*','roles.*','activity-logs.*') ? 'true' : 'false' }}">
                    <i class="fas fa-user-shield"></i> User & Akses
                    <i class="fas fa-chevron-down"></i>
                </button>
                <ul class="collapse submenu {{ request()->routeIs('users.*','roles.*','activity-logs.*') ? 'show' : '' }}" id="menuUsers">
                    @can('user.view')<li><a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}"><i class="fas fa-users-cog me-1"></i> Manajemen User</a></li>@endcan
                    @can('role.view')<li><a href="{{ route('roles.index') }}" class="nav-link {{ request()->routeIs('roles.*') ? 'active' : '' }}"><i class="fas fa-lock me-1"></i> Role & Permission</a></li>@endcan
                    @can('activity-log.view')<li><a href="{{ route('activity-logs.index') }}" class="nav-link {{ request()->routeIs('activity-logs.*') ? 'active' : '' }}"><i class="fas fa-history me-1"></i> Activity Log</a></li>@endcan
                    <li><a href="{{ route('users.api-tokens') }}" class="nav-link {{ request()->routeIs('users.api-tokens') ? 'active' : '' }}"><i class="fas fa-key me-1"></i> API Tokens</a></li>
                </ul>
            </li>
            @endcanany

            @canany(['settings.view','notification-template.view','reminder.view','custom-field.view','payment-gateway.view','two-factor.view'])
            {{-- Settings --}}
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="collapse" data-bs-target="#menuSettings"
                    aria-expanded="{{ request()->routeIs('settings.*','notification-templates.*','reminders.*','custom-fields.*','2fa.*') ? 'true' : 'false' }}">
                    <i class="fas fa-cog"></i> Settings
                    <i class="fas fa-chevron-down"></i>
                </button>
                <ul class="collapse submenu {{ request()->routeIs('settings.*','notification-templates.*','reminders.*','custom-fields.*','2fa.*') ? 'show' : '' }}" id="menuSettings">
                    @can('settings.view')<li><a href="{{ route('settings.index') }}" class="nav-link {{ request()->routeIs('settings.*') ? 'active' : '' }}"><i class="fas fa-sliders-h me-1"></i> General Settings</a></li>@endcan
                    @can('notification-template.view')<li><a href="{{ route('notification-templates.index') }}" class="nav-link {{ request()->routeIs('notification-templates.*') ? 'active' : '' }}"><i class="fas fa-bell me-1"></i> Notification Templates</a></li>@endcan
                    @can('reminder.view')<li><a href="{{ route('reminders.index') }}" class="nav-link {{ request()->routeIs('reminders.*') ? 'active' : '' }}"><i class="fas fa-clock me-1"></i> Reminders</a></li>@endcan
                    @can('custom-field.view')<li><a href="{{ route('custom-fields.index') }}" class="nav-link {{ request()->is('custom-fields*') ? 'active' : '' }}"><i class="fas fa-puzzle-piece me-1"></i> Custom Fields</a></li>@endcan
                    @can('payment-gateway.view')<li><a href="{{ route('payment-gateways.index') }}" class="nav-link {{ request()->routeIs('payment-gateways.*') ? 'active' : '' }}"><i class="fas fa-credit-card me-1"></i> Payment Gateway</a></li>@endcan
                    @can('two-factor.view')<li><a href="{{ route('2fa.enable.form') }}" class="nav-link {{ request()->routeIs('2fa.*') ? 'active' : '' }}"><i class="fas fa-fingerprint me-1"></i> Two-Factor Auth (2FA)</a></li>@endcan
                </ul>
            </li>
            @endcanany
        </ul>
    </aside>

    <nav class="topbar">
        <button class="btn btn-sm btn-outline-secondary d-md-none sidebar-toggle" onclick="var s=document.getElementById('sidebar');var o=document.getElementById('sidebarOverlay');s.classList.toggle('show');o.classList.toggle('show')">
            <i class="fas fa-bars"></i>
        </button>
        <span class="tenant-name">Bengkel Paten</span>

        @php
            try {
                $allBranches = \App\Models\Branch::where('is_active', true)->orderBy('name')->get();
            } catch (\Throwable $e) {
                $allBranches = collect();
            }
            $currentBranchId = session('current_branch_id');
            $currentBranch = $currentBranchId ? $allBranches->firstWhere('id', $currentBranchId) : null;
        @endphp
        @if($allBranches->count() > 0)
        <form method="POST" action="{{ route('branches.switch') }}" class="d-flex align-items-center gap-2">
            @csrf
            <small class="text-muted"><i class="fas fa-building me-1"></i>Cabang:</small>
            <select name="branch_id" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
                <option value="">— Semua —</option>
                @foreach($allBranches as $br)
                    <option value="{{ $br->id }}" {{ $currentBranchId == $br->id ? 'selected' : '' }}>{{ $br->name }}</option>
                @endforeach
            </select>
        </form>
        @endif

        <div class="dropdown">
            <button class="btn btn-light dropdown-toggle" data-bs-toggle="dropdown">
                <i class="fas fa-user-circle me-1"></i> {{ auth()->user()?->name ?? 'Admin' }}
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a href="#" class="dropdown-item"><i class="fas fa-user me-2"></i>Profile</a></li>
                <li><a href="#" class="dropdown-item"><i class="fas fa-cog me-2"></i>Settings</a></li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="dropdown-item"><i class="fas fa-sign-out-alt me-2"></i>Logout</button>
                    </form>
                </li>
            </ul>
        </div>
    </nav>
@else
    <nav class="topbar" style="left: 0 !important;">
        <span class="tenant-name"><i class="fas fa-wrench me-2"></i>Bengkel Paten</span>
        <a href="{{ route('login') }}" class="btn btn-primary btn-sm">
            <i class="fas fa-sign-in-alt me-1"></i>Login
        </a>
    </nav>
@endauth

    <main class="main-content" @guest style="margin-left: 0 !important;" @endguest>
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

        @if(session('info'))
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <i class="fas fa-info-circle me-1"></i> {{ session('info') }}
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
