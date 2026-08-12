<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="manifest" href="/manifest.json">
    <script>if('serviceWorker' in navigator){navigator.serviceWorker.register('/sw.js')}</script>
    <title>@yield('title', 'Dashboard') — {{ config('app.name') }}</title>

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
            --bg: #f1f5f9;
            --card-bg: #fff;
            --text: #1e293b;
            --text-muted: #64748b;
            --border: #e2e8f0;
        }
        body.dark {
            --sidebar-bg: #0f172a;
            --sidebar-hover: #1e293b;
            --bg: #0f172a;
            --card-bg: #1e293b;
            --text: #e2e8f0;
            --text-muted: #94a3b8;
            --border: #334155;
        }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background-color: var(--bg);
            color: var(--text);
            transition: background 0.3s, color 0.3s;
        }
        .card { background: var(--card-bg); border-color: var(--border); box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
        .card-header, .modal-content { background: var(--card-bg); border-color: var(--border); }
        .table { color: var(--text); }
        .table-light { background: rgba(0,0,0,0.03); }
        .text-muted { color: var(--text-muted) !important; }
        .form-control, .form-select { background: var(--card-bg); color: var(--text); border-color: var(--border); }

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

        .submenu .submenu-header {
            padding: 6px 14px;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: rgba(255,255,255,0.35);
            font-weight: 600;
            pointer-events: none;
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

        @media print {
            .sidebar, .topbar, .sidebar-overlay, .btn, footer, nav, .no-print,
            .nav-tabs, .nav-pills, .alert, canvas, .dropdown-menu, .chart-container,
            .pagination, .input-group, .d-print-none { display: none !important; }

            .d-print-block { display: block !important; }

            body { background: #fff !important; font-size: 13px !important; }
            @page { margin: 10mm; size: A4 portrait; }

            .main-content { margin: 0 !important; padding: 0 !important; width: 100% !important; }

            .row { display: block !important; margin-left: 0 !important; margin-right: 0 !important; }
            .col,.col-auto,.col-sm,.col-sm-auto,.col-md,.col-md-auto,.col-lg,.col-lg-auto,
            [class^="col-"], [class*=" col-"] {
                width: 100% !important; max-width: 100% !important; flex: 0 0 100% !important;
                padding-left: 0 !important; padding-right: 0 !important;
            }

            .card { box-shadow: none !important; border: 1px solid #ccc !important; margin-bottom: 6px !important; }
            .card-body { padding: 8px 12px !important; }
            .card-header { padding: 6px 12px !important; }

            .table { font-size: 12px !important; margin-bottom: 4px !important; }
            .table td, .table th { padding: 4px 8px !important; }
            .table-light { background: #f5f5f5 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .table-responsive { overflow-x: visible !important; }
            thead { display: table-header-group !important; }
            tr { page-break-inside: avoid; }

            h1 { font-size: 20px !important; } h2 { font-size: 18px !important; }
            h3 { font-size: 16px !important; } h4 { font-size: 15px !important; }
            h5 { font-size: 14px !important; } h6 { font-size: 13px !important; }
            small, .text-muted { font-size: 10px !important; }

            .tab-content > .tab-pane { display: block !important; opacity: 1 !important; visibility: visible !important; }

            .text-success, .text-danger, .text-warning, .text-primary, .text-info, .text-secondary { color: #000 !important; }
            .badge { border: 1px solid #666 !important; color: #000 !important; background: none !important; }

            .mb-1, .mb-2, .mb-3, .mb-4, .mb-5 { margin-bottom: 4px !important; }
            .mt-1, .mt-2, .mt-3, .mt-4, .mt-5 { margin-top: 4px !important; }
            .my-1, .my-2, .my-3, .my-4, .my-5 { margin-top: 4px !important; margin-bottom: 4px !important; }
            .g-1, .g-2, .g-3, .g-4, .g-5 { gap: 0 !important; --bs-gutter-x: 0 !important; --bs-gutter-y: 0 !important; }
        }
    </style>
    @stack('styles')
</head>
<body>

    @auth
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="document.getElementById('sidebar').classList.remove('show'); this.classList.remove('show')"></div>
    <aside class="sidebar" id="sidebar">
        <div class="brand">
            @if(!empty($appSettings['logo']))
                <img src="{{ asset('storage/' . $appSettings['logo']) }}" alt="Logo" style="height:28px;width:auto;margin-right:8px;">
            @else
                <i class="fas fa-wrench"></i>
            @endif
            {{ $appSettings['name'] ?? config('app.name') }}
            <button class="btn btn-sm btn-link text-white-50 ms-auto d-md-none" style="text-decoration:none;" onclick="document.getElementById('sidebar').classList.remove('show'); document.getElementById('sidebarOverlay').classList.remove('show')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="sidebar-search px-3 py-2">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-transparent border-secondary text-white-50"><i class="fas fa-search"></i></span>
                <input type="text" id="menuSearch" class="form-control bg-transparent border-secondary text-white" placeholder="Cari menu..." autocomplete="off" style="font-size:0.82rem;">
            </div>
        </div>
        <ul class="sidebar-nav">
            {{-- 1. DASHBOARD --}}
            @can('dashboard.view')
            <li class="nav-item">
                <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
            @endcan

            {{-- 2. OPERATIONS --}}
            @canany(['branch.view','booking.view','service.view','customer.view','vehicle.view','gate-pass.view'])
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="collapse" data-bs-target="#menuOperations"
                    aria-expanded="{{ request()->is('branches*','holidays*','washbays*','business-hours*') || request()->routeIs('bookings.*','services.*','jobcards.*','customers.*','vehicles.*','gate-passes.*') ? 'true' : 'false' }}">
                    <i class="fas fa-cogs"></i> Operations
                    <i class="fas fa-chevron-down"></i>
                </button>
                <ul class="collapse submenu {{ request()->is('branches*','holidays*','washbays*','business-hours*') || request()->routeIs('bookings.*','services.*','jobcards.*','customers.*','vehicles.*','gate-passes.*') ? 'show' : '' }}" id="menuOperations">
                    @can('branch.view')<li><a href="{{ route('branches.index') }}" class="nav-link {{ request()->is('branches*') ? 'active' : '' }}"><i class="fas fa-store-alt me-1"></i> Branches</a></li>@endcan
                    @can('holiday.view')<li><a href="{{ route('holidays.index') }}" class="nav-link {{ request()->is('holidays*') ? 'active' : '' }}"><i class="fas fa-calendar-times me-1"></i> Holidays</a></li>@endcan
                    @can('washbay.view')<li><a href="{{ route('washbays.index') }}" class="nav-link {{ request()->is('washbays*') ? 'active' : '' }}"><i class="fas fa-shower me-1"></i> Washbay / Slots</a></li>@endcan
                    @can('booking.view')<li><a href="{{ route('bookings.index') }}" class="nav-link {{ request()->routeIs('bookings.index') ? 'active' : '' }}"><i class="fas fa-calendar-check me-1"></i> Bookings</a></li>@endcan
                    @can('booking.view')<li><a href="{{ route('bookings.calendar') }}" class="nav-link {{ request()->routeIs('bookings.calendar*') ? 'active' : '' }}"><i class="fas fa-calendar-alt me-1"></i> Calendar</a></li>@endcan
                    @can('service.view')<li><a href="{{ route('services.index') }}" class="nav-link {{ request()->routeIs('services.index') ? 'active' : '' }}"><i class="fas fa-clipboard-check me-1"></i> Job Cards / Services</a></li>@endcan
                    @can('service.view')<li><a href="{{ route('services.history') }}" class="nav-link {{ request()->routeIs('services.history') ? 'active' : '' }}"><i class="fas fa-history me-1"></i> Service History</a></li>@endcan
                    @can('gate-pass.view')<li><a href="{{ route('gate-passes.index') }}" class="nav-link {{ request()->routeIs('gate-passes.*') ? 'active' : '' }}"><i class="fas fa-ticket-alt me-1"></i> Gate Passes</a></li>@endcan
                    @can('vehicle.view')<li><a href="{{ route('vehicles.index') }}" class="nav-link {{ request()->routeIs('vehicles.*') ? 'active' : '' }}"><i class="fas fa-car me-1"></i> Vehicles</a></li>@endcan
                    @can('customer.view')<li><a href="{{ route('customers.index') }}" class="nav-link {{ request()->routeIs('customers.*') ? 'active' : '' }}"><i class="fas fa-users me-1"></i> Customers</a></li>@endcan
                    @can('customer-group.view')<li><a href="{{ route('customer-groups.index') }}" class="nav-link {{ request()->is('customer-groups*') ? 'active' : '' }}"><i class="fas fa-layer-group me-1"></i> Customer Groups</a></li>@endcan
                </ul>
            </li>
            @endcanany

            {{-- 3. SERVICE MANAGEMENT --}}
            @canany(['service.view','jobcard.view','subcontractor.view','service-package.view'])
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="collapse" data-bs-target="#menuServiceMgmt"
                    aria-expanded="{{ request()->routeIs('services.*','jobcards.*','subcontractors.*','service-packages.*') ? 'true' : 'false' }}">
                    <i class="fas fa-tools"></i> Service Management
                    <i class="fas fa-chevron-down"></i>
                </button>
                <ul class="collapse submenu {{ request()->routeIs('services.*','jobcards.*','subcontractors.*','service-packages.*') ? 'show' : '' }}" id="menuServiceMgmt">
                    @can('service-package.view')<li><a href="{{ route('service-packages.index') }}" class="nav-link {{ request()->is('service-packages*') ? 'active' : '' }}"><i class="fas fa-cubes me-1"></i> Service Packages</a></li>@endcan
                    @can('service.view')<li><a href="{{ route('services.index') }}" class="nav-link {{ request()->routeIs('services.index') ? 'active' : '' }}"><i class="fas fa-list me-1"></i> All Services</a></li>@endcan
                    @can('jobcard.view')<li><a href="{{ route('jobcards.index') }}" class="nav-link {{ request()->routeIs('jobcards.*') ? 'active' : '' }}"><i class="fas fa-clipboard-list me-1"></i> Job Cards</a></li>@endcan
                    @can('subcontractor.view')<li><a href="{{ route('subcontractors.index') }}" class="nav-link {{ request()->routeIs('subcontractors.*') ? 'active' : '' }}"><i class="fas fa-user-gear me-1"></i> Subcontractors</a></li>@endcan
                </ul>
            </li>
            @endcanany

            {{-- 4. INVENTORY --}}
            @canany(['product.view','supplier.view','purchase.view','equipment.view','warehouse.view'])
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="collapse" data-bs-target="#menuInventory"
                    aria-expanded="{{ request()->routeIs('products.*','suppliers.*','purchases.*','equipment.*','warehouses.*','stock-adjustments.*') ? 'true' : 'false' }}">
                    <i class="fas fa-boxes"></i> Inventory
                    <i class="fas fa-chevron-down"></i>
                </button>
                <ul class="collapse submenu {{ request()->routeIs('products.*','suppliers.*','purchases.*','equipment.*','warehouses.*','stock-adjustments.*') ? 'show' : '' }}" id="menuInventory">
                    @can('product.view')<li><a href="{{ route('products.index') }}" class="nav-link {{ request()->routeIs('products.index') ? 'active' : '' }}"><i class="fas fa-box me-1"></i> Products / Parts</a></li>@endcan
                    @can('product.stock-opname')<li><a href="{{ route('products.stock-opname') }}" class="nav-link {{ request()->routeIs('products.stock-opname') ? 'active' : '' }}"><i class="fas fa-clipboard me-1"></i> Stock Opname</a></li>@endcan
                    <li><a href="{{ route('stock-adjustments.index') }}" class="nav-link {{ request()->routeIs('stock-adjustments.*') ? 'active' : '' }}"><i class="fas fa-balance-scale me-1"></i> Stock Adjustment</a></li>
                    @can('supplier.view')<li><a href="{{ route('suppliers.index') }}" class="nav-link {{ request()->routeIs('suppliers.*') ? 'active' : '' }}"><i class="fas fa-truck me-1"></i> Suppliers</a></li>@endcan
                    @can('purchase.view')<li><a href="{{ route('purchases.index') }}" class="nav-link {{ request()->routeIs('purchases.*') ? 'active' : '' }}"><i class="fas fa-shopping-basket me-1"></i> Purchases</a></li>@endcan
                    @can('equipment.view')<li><a href="{{ route('equipment.index') }}" class="nav-link {{ request()->routeIs('equipment.*') ? 'active' : '' }}"><i class="fas fa-toolbox me-1"></i> Equipment</a></li>@endcan
                    @can('warehouse.view')<li><a href="{{ route('warehouses.index') }}" class="nav-link {{ request()->routeIs('warehouses.*') ? 'active' : '' }}"><i class="fas fa-warehouse me-1"></i> Warehouses</a></li>@endcan
                </ul>
            </li>
            @endcanany

            {{-- 5. SALES & POS --}}
            @canany(['pos.view','sale.view','invoice.view'])
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="collapse" data-bs-target="#menuSalesPos"
                    aria-expanded="{{ request()->routeIs('pos.*','sales.*','invoices.*','payments.*') ? 'true' : 'false' }}">
                    <i class="fas fa-cash-register"></i> Sales & POS
                    <i class="fas fa-chevron-down"></i>
                </button>
                <ul class="collapse submenu {{ request()->routeIs('pos.*','sales.*','invoices.*','payments.*') ? 'show' : '' }}" id="menuSalesPos">
                    @can('pos.view')<li><a href="{{ route('pos.terminal') }}" class="nav-link {{ request()->routeIs('pos.terminal','pos.openForm') ? 'active' : '' }}"><i class="fas fa-desktop me-1"></i> POS Terminal</a></li>@endcan
                    @can('pos.view')<li><a href="{{ route('pos.sessions') }}" class="nav-link {{ request()->routeIs('pos.sessions','pos.close*') ? 'active' : '' }}"><i class="fas fa-history me-1"></i> POS Sessions</a></li>@endcan
                    @can('sale.view')<li><a href="{{ route('sales.index') }}" class="nav-link {{ request()->routeIs('sales.index') ? 'active' : '' }}"><i class="fas fa-cart-plus me-1"></i> Sales</a></li>@endcan
                    @can('invoice.view')<li><a href="{{ route('invoices.index') }}" class="nav-link {{ request()->routeIs('invoices.index') ? 'active' : '' }}"><i class="fas fa-file-invoice me-1"></i> Invoices</a></li>@endcan
                </ul>
            </li>
            @endcanany

            {{-- 6. TECHNICIANS --}}
            @canany(['commission.view','hrm.view'])
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="collapse" data-bs-target="#menuTechnicians"
                    aria-expanded="{{ request()->routeIs('commissions.*','hrm.*') ? 'true' : 'false' }}">
                    <i class="fas fa-user-cog"></i> Technicians
                    <i class="fas fa-chevron-down"></i>
                </button>
                <ul class="collapse submenu {{ request()->routeIs('commissions.*','hrm.*') ? 'show' : '' }}" id="menuTechnicians">
                    @can('commission.view')<li><a href="{{ route('commissions.index') }}" class="nav-link {{ request()->routeIs('commissions.index','commissions.markPaid*') ? 'active' : '' }}"><i class="fas fa-hand-holding-usd me-1"></i> Commissions</a></li>@endcan
                    @can('commission.report')<li><a href="{{ route('commissions.report') }}" class="nav-link {{ request()->routeIs('commissions.report') ? 'active' : '' }}"><i class="fas fa-file-invoice me-1"></i> Commission Report</a></li>@endcan
                    <li><a href="{{ route('hrm.leaves.index') }}" class="nav-link {{ request()->routeIs('hrm.leaves.*') ? 'active' : '' }}"><i class="fas fa-calendar-alt me-1"></i> Leave / Permission</a></li>
                </ul>
            </li>
            @endcanany

            {{-- 7. CRM & MARKETING --}}
            @canany(['voucher.view','loyalty.view','review.view','blog.view','campaign.view','customer-group.view'])
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="collapse" data-bs-target="#menuCrm"
                    aria-expanded="{{ request()->routeIs('vouchers.*','loyalty.*','reviews.*','blog.admin.*','marketing.campaign*') ? 'true' : 'false' }}">
                    <i class="fas fa-bullhorn"></i> CRM & Marketing
                    <i class="fas fa-chevron-down"></i>
                </button>
                <ul class="collapse submenu {{ request()->routeIs('vouchers.*','loyalty.*','reviews.*','blog.admin.*','marketing.campaign*') ? 'show' : '' }}" id="menuCrm">
                    @can('voucher.view')<li><a href="{{ route('vouchers.index') }}" class="nav-link {{ request()->routeIs('vouchers.*') ? 'active' : '' }}"><i class="fas fa-ticket-alt me-1"></i> Vouchers / Promos</a></li>@endcan
                    @can('loyalty.view')<li><a href="{{ route('loyalty.index') }}" class="nav-link {{ request()->routeIs('loyalty.*') ? 'active' : '' }}"><i class="fas fa-star me-1"></i> Loyalty & Membership</a></li>@endcan
                    @can('review.view')<li><a href="{{ route('reviews.index') }}" class="nav-link {{ request()->routeIs('reviews.*') ? 'active' : '' }}"><i class="fas fa-comment-dots me-1"></i> Reviews & Ratings</a></li>@endcan
                    @can('blog.view')<li><a href="{{ route('blog.admin.index') }}" class="nav-link {{ request()->routeIs('blog.admin.*') ? 'active' : '' }}"><i class="fas fa-blog me-1"></i> Blog Articles</a></li>@endcan
                    @can('campaign.view')<li><a href="{{ route('marketing.campaign') }}" class="nav-link {{ request()->routeIs('marketing.campaign*') ? 'active' : '' }}"><i class="fas fa-envelope-open-text me-1"></i> Campaigns</a></li>@endcan
                </ul>
            </li>
            @endcanany

            {{-- 8. WARRANTY --}}
            @can('warranty.view')
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="collapse" data-bs-target="#menuWarranty"
                    aria-expanded="{{ request()->routeIs('warranty-claims.*','recalls.*') ? 'true' : 'false' }}">
                    <i class="fas fa-shield-alt"></i> Warranty
                    <i class="fas fa-chevron-down"></i>
                </button>
                <ul class="collapse submenu {{ request()->routeIs('warranty-claims.*','recalls.*') ? 'show' : '' }}" id="menuWarranty">
                    <li><a href="{{ route('warranty-claims.index') }}" class="nav-link {{ request()->routeIs('warranty-claims.*') ? 'active' : '' }}"><i class="fas fa-shield-alt me-1"></i> Warranty Claims</a></li>
                    <li><a href="{{ route('recalls.index') }}" class="nav-link {{ request()->routeIs('recalls.*') ? 'active' : '' }}"><i class="fas fa-exclamation-triangle me-1"></i> Recalls</a></li>
                </ul>
            </li>
            @endcan

            {{-- 9. FINANCE & ACCOUNTING --}}
            @canany(['income.view','expense.view','petty-cash.view','finance-coa.view','finance-journal.view'])
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="collapse" data-bs-target="#menuFinance"
                    aria-expanded="{{ request()->routeIs('incomes.*','expenses.*','petty-cash.*','finance.*') ? 'true' : 'false' }}">
                    <i class="fas fa-chart-line"></i> Finance & Accounting
                    <i class="fas fa-chevron-down"></i>
                </button>
                <ul class="collapse submenu {{ request()->routeIs('incomes.*','expenses.*','petty-cash.*','finance.*') ? 'show' : '' }}" id="menuFinance">
                    @can('income.view')<li><a href="{{ route('incomes.index') }}" class="nav-link {{ request()->routeIs('incomes.*') ? 'active' : '' }}"><i class="fas fa-arrow-up me-1 text-success"></i> Income</a></li>@endcan
                    @can('expense.view')<li><a href="{{ route('expenses.index') }}" class="nav-link {{ request()->routeIs('expenses.*') ? 'active' : '' }}"><i class="fas fa-arrow-down me-1 text-danger"></i> Expenses</a></li>@endcan
                    @can('petty-cash.view')<li><a href="{{ route('petty-cash.index') }}" class="nav-link {{ request()->routeIs('petty-cash.*') ? 'active' : '' }}"><i class="fas fa-wallet me-1"></i> Petty Cash</a></li>@endcan
                    @can('finance-coa.view')<li><a href="{{ route('finance.coa') }}" class="nav-link {{ request()->routeIs('finance.coa*') ? 'active' : '' }}"><i class="fas fa-list-ol me-1"></i> Chart of Accounts</a></li>@endcan
                    @can('finance-journal.view')<li><a href="{{ route('finance.journal') }}" class="nav-link {{ request()->routeIs('finance.journal*') ? 'active' : '' }}"><i class="fas fa-book me-1"></i> Journal Entries</a></li>@endcan
                </ul>
            </li>
            @endcanany

            {{-- 10. REPORTS --}}
            @can('report.view')
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
                    <li><a href="{{ route('reports.technician') }}" class="nav-link {{ request()->routeIs('reports.technician') ? 'active' : '' }}"><i class="fas fa-user-gear me-1"></i> Technician</a></li>
                    <li><a href="{{ route('reports.customer-lifetime') }}" class="nav-link {{ request()->routeIs('reports.customer-lifetime') ? 'active' : '' }}"><i class="fas fa-crown me-1"></i> Customer Lifetime</a></li>
                    <li><a href="{{ route('reports.ar-aging') }}" class="nav-link {{ request()->routeIs('reports.ar-aging') ? 'active' : '' }}"><i class="fas fa-clock me-1"></i> AR Aging</a></li>
                    <li><a href="{{ route('reports.parts-usage') }}" class="nav-link {{ request()->routeIs('reports.parts-usage') ? 'active' : '' }}"><i class="fas fa-microchip me-1"></i> Parts Usage</a></li>
                    <li><a href="{{ route('reports.branch-comparison') }}" class="nav-link {{ request()->routeIs('reports.branch-comparison') ? 'active' : '' }}"><i class="fas fa-code-branch me-1"></i> Branch Comparison</a></li>
                    <li><a href="{{ route('reports.cash-flow') }}" class="nav-link {{ request()->routeIs('reports.cash-flow') ? 'active' : '' }}"><i class="fas fa-money-bill-wave me-1"></i> Cash Flow</a></li>
                    <li><a href="{{ route('reports.general-ledger') }}" class="nav-link {{ request()->routeIs('reports.general-ledger') ? 'active' : '' }}"><i class="fas fa-book-open me-1"></i> General Ledger</a></li>
                    <li><a href="{{ route('reports.profit-loss') }}" class="nav-link {{ request()->routeIs('reports.profit-loss') ? 'active' : '' }}"><i class="fas fa-chart-line me-1"></i> Profit & Loss</a></li>
                    <li><a href="{{ route('reports.balance-sheet') }}" class="nav-link {{ request()->routeIs('reports.balance-sheet') ? 'active' : '' }}"><i class="fas fa-balance-scale me-1"></i> Balance Sheet</a></li>
                </ul>
            </li>
            @endcan

            {{-- 11. MASTER DATA --}}
            @can('master-data.view')
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="collapse" data-bs-target="#menuMasterData"
                    aria-expanded="{{ request()->is('vehicle-types*','vehicle-brands*','fuel-types*','colors*','product-types*','product-units*','payment-methods*','tax-rates*','repair-categories*','observation-types*','observation-points*','inspection-points*','checkout-categories*') ? 'true' : 'false' }}">
                    <i class="fas fa-database"></i> Master Data
                    <i class="fas fa-chevron-down"></i>
                </button>
                <ul class="collapse submenu {{ request()->is('vehicle-types*','vehicle-brands*','fuel-types*','colors*','product-types*','product-units*','payment-methods*','tax-rates*','repair-categories*','observation-types*','observation-points*','inspection-points*','checkout-categories*') ? 'show' : '' }}" id="menuMasterData">
                    <li class="submenu-header">Vehicle</li>
                    <li><a href="{{ route('vehicle-types.index') }}" class="nav-link {{ request()->is('vehicle-types*') ? 'active' : '' }}"><i class="fas fa-truck-pickup me-1"></i> Vehicle Types</a></li>
                    <li><a href="{{ route('vehicle-brands.index') }}" class="nav-link {{ request()->is('vehicle-brands*') ? 'active' : '' }}"><i class="fas fa-trademark me-1"></i> Vehicle Brands</a></li>
                    <li><a href="{{ route('fuel-types.index') }}" class="nav-link {{ request()->is('fuel-types*') ? 'active' : '' }}"><i class="fas fa-gas-pump me-1"></i> Fuel Types</a></li>
                    <li><a href="{{ route('colors.index') }}" class="nav-link {{ request()->is('colors*') ? 'active' : '' }}"><i class="fas fa-palette me-1"></i> Colors</a></li>
                    <li class="submenu-header">Product</li>
                    <li><a href="{{ route('product-types.index') }}" class="nav-link {{ request()->is('product-types*') ? 'active' : '' }}"><i class="fas fa-tags me-1"></i> Product Types</a></li>
                    <li><a href="{{ route('product-units.index') }}" class="nav-link {{ request()->is('product-units*') ? 'active' : '' }}"><i class="fas fa-balance-scale me-1"></i> Product Units</a></li>
                    <li class="submenu-header">Workshop</li>
                    <li><a href="{{ route('repair-categories.index') }}" class="nav-link {{ request()->is('repair-categories*') ? 'active' : '' }}"><i class="fas fa-toolbox me-1"></i> Repair Categories</a></li>
                    <li><a href="{{ route('observation-types.index') }}" class="nav-link {{ request()->is('observation-types*') ? 'active' : '' }}"><i class="fas fa-clipboard-check me-1"></i> Observation Types</a></li>
                    <li><a href="{{ route('observation-points.index') }}" class="nav-link {{ request()->is('observation-points*') ? 'active' : '' }}"><i class="fas fa-list-ol me-1"></i> Observation Points</a></li>
                    <li><a href="{{ route('inspection-points.index') }}" class="nav-link {{ request()->is('inspection-points*') ? 'active' : '' }}"><i class="fas fa-search me-1"></i> Inspection Points</a></li>
                    <li><a href="{{ route('checkout-categories.index') }}" class="nav-link {{ request()->is('checkout-categories*') ? 'active' : '' }}"><i class="fas fa-check-double me-1"></i> Checkout Categories</a></li>
                    <li class="submenu-header">Finance</li>
                    <li><a href="{{ route('payment-methods.index') }}" class="nav-link {{ request()->is('payment-methods*') ? 'active' : '' }}"><i class="fas fa-credit-card me-1"></i> Payment Methods</a></li>
                    <li><a href="{{ route('tax-rates.index') }}" class="nav-link {{ request()->is('tax-rates*') ? 'active' : '' }}"><i class="fas fa-percent me-1"></i> Tax Rates</a></li>
                </ul>
            </li>
            @endcan

            {{-- 12. GEOGRAPHY --}}
            @canany(['currency.view','country.view','state.view','city.view'])
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="collapse" data-bs-target="#menuGeo"
                    aria-expanded="{{ request()->is('countries*','states*','cities*','currencies*') ? 'true' : 'false' }}">
                    <i class="fas fa-globe"></i> Geography
                    <i class="fas fa-chevron-down"></i>
                </button>
                <ul class="collapse submenu {{ request()->is('countries*','states*','cities*','currencies*') ? 'show' : '' }}" id="menuGeo">
                    @can('currency.view')<li><a href="{{ route('currencies.index') }}" class="nav-link {{ request()->is('currencies*') ? 'active' : '' }}"><i class="fas fa-dollar-sign me-1"></i> Currencies</a></li>@endcan
                    @can('country.view')<li><a href="{{ route('countries.index') }}" class="nav-link {{ request()->is('countries*') ? 'active' : '' }}"><i class="fas fa-flag me-1"></i> Countries</a></li>@endcan
                    @can('state.view')<li><a href="{{ route('states.index') }}" class="nav-link {{ request()->is('states*') ? 'active' : '' }}"><i class="fas fa-map-pin me-1"></i> Provinces</a></li>@endcan
                    @can('city.view')<li><a href="{{ route('cities.index') }}" class="nav-link {{ request()->is('cities*') ? 'active' : '' }}"><i class="fas fa-city me-1"></i> Cities</a></li>@endcan
                </ul>
            </li>
            @endcanany

            {{-- 13. NOTIFICATIONS --}}
            @canany(['notification-template.view','reminder.view','email-log.view','stock-history.view'])
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="collapse" data-bs-target="#menuNotifications"
                    aria-expanded="{{ request()->routeIs('notification-templates.*','reminders.*') || request()->is('email-logs*','stock-histories*') ? 'true' : 'false' }}">
                    <i class="fas fa-bell"></i> Notifications
                    <i class="fas fa-chevron-down"></i>
                </button>
                <ul class="collapse submenu {{ request()->routeIs('notification-templates.*','reminders.*') || request()->is('email-logs*','stock-histories*') ? 'show' : '' }}" id="menuNotifications">
                    @can('notification-template.view')<li><a href="{{ route('notification-templates.index') }}" class="nav-link {{ request()->routeIs('notification-templates.*') ? 'active' : '' }}"><i class="fas fa-envelope me-1"></i> Templates</a></li>@endcan
                    @can('reminder.view')<li><a href="{{ route('reminders.index') }}" class="nav-link {{ request()->routeIs('reminders.*') ? 'active' : '' }}"><i class="fas fa-clock me-1"></i> Reminders</a></li>@endcan
                    @can('email-log.view')<li><a href="{{ route('email-logs.index') }}" class="nav-link {{ request()->is('email-logs*') ? 'active' : '' }}"><i class="fas fa-paper-plane me-1"></i> Notification Logs</a></li>@endcan
                    @can('stock-history.view')<li><a href="{{ route('stock-histories.index') }}" class="nav-link {{ request()->is('stock-histories*') ? 'active' : '' }}"><i class="fas fa-archive me-1"></i> Stock History</a></li>@endcan
                </ul>
            </li>
            @endcanany

            {{-- 14. USERS & SECURITY --}}
            @canany(['user.view','role.view','activity-log.view','note.view'])
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="collapse" data-bs-target="#menuUsers"
                    aria-expanded="{{ request()->routeIs('users.*','roles.*','activity-logs.*') || request()->is('notes*') ? 'true' : 'false' }}">
                    <i class="fas fa-user-shield"></i> Users & Security
                    <i class="fas fa-chevron-down"></i>
                </button>
                <ul class="collapse submenu {{ request()->routeIs('users.*','roles.*','activity-logs.*') || request()->is('notes*') ? 'show' : '' }}" id="menuUsers">
                    @can('user.view')<li><a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}"><i class="fas fa-users-cog me-1"></i> Users</a></li>@endcan
                    @can('role.view')<li><a href="{{ route('roles.index') }}" class="nav-link {{ request()->routeIs('roles.*') ? 'active' : '' }}"><i class="fas fa-lock me-1"></i> Roles & Permissions</a></li>@endcan
                    @can('activity-log.view')<li><a href="{{ route('activity-logs.index') }}" class="nav-link {{ request()->routeIs('activity-logs.*') ? 'active' : '' }}"><i class="fas fa-history me-1"></i> Activity Log</a></li>@endcan
                    @can('api-token.view')<li><a href="{{ route('users.api-tokens') }}" class="nav-link {{ request()->routeIs('users.api-tokens') ? 'active' : '' }}"><i class="fas fa-key me-1"></i> API Tokens</a></li>@endcan
                    @can('note.view')<li><a href="{{ route('notes.index') }}" class="nav-link {{ request()->is('notes*') ? 'active' : '' }}"><i class="fas fa-sticky-note me-1"></i> Internal Notes</a></li>@endcan
                </ul>
            </li>
            @endcanany

            {{-- 15. SETTINGS --}}
            @canany(['settings.view','custom-field.view','payment-gateway.view','two-factor.view','backup.view'])
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="collapse" data-bs-target="#menuSettings"
                    aria-expanded="{{ request()->routeIs('settings.*','custom-fields.*','payment-gateways.*','2fa.*') ? 'true' : 'false' }}">
                    <i class="fas fa-cog"></i> Settings
                    <i class="fas fa-chevron-down"></i>
                </button>
                <ul class="collapse submenu {{ request()->routeIs('settings.*','custom-fields.*','payment-gateways.*','2fa.*') ? 'show' : '' }}" id="menuSettings">
                    @can('settings.view')<li><a href="{{ route('settings.index') }}" class="nav-link {{ request()->routeIs('settings.*') ? 'active' : '' }}"><i class="fas fa-sliders-h me-1"></i> General</a></li>@endcan
                    @can('custom-field.view')<li><a href="{{ route('custom-fields.index') }}" class="nav-link {{ request()->is('custom-fields*') ? 'active' : '' }}"><i class="fas fa-puzzle-piece me-1"></i> Custom Fields</a></li>@endcan
                    @can('payment-gateway.view')<li><a href="{{ route('payment-gateways.index') }}" class="nav-link {{ request()->routeIs('payment-gateways.*') ? 'active' : '' }}"><i class="fas fa-plug me-1"></i> Integrations</a></li>@endcan
                    @can('two-factor.view')<li><a href="{{ route('2fa.enable.form') }}" class="nav-link {{ request()->routeIs('2fa.*') ? 'active' : '' }}"><i class="fas fa-fingerprint me-1"></i> 2FA Security</a></li>@endcan
                    @can('backup.view')<li><a href="{{ route('settings.backup-page') }}" class="nav-link {{ request()->routeIs('settings.backup*') ? 'active' : '' }}"><i class="fas fa-database me-1"></i> Backup & Restore</a></li>@endcan
                </ul>
            </li>
            @endcanany
        </ul>
    </aside>

    <nav class="topbar">
        <button class="btn btn-sm btn-outline-secondary d-md-none sidebar-toggle" onclick="var s=document.getElementById('sidebar');var o=document.getElementById('sidebarOverlay');s.classList.toggle('show');o.classList.toggle('show')">
            <i class="fas fa-bars"></i>
        </button>
        <span class="tenant-name">{{ config('app.name') }}</span>

        <div class="d-flex align-items-center gap-2">
            {{-- Quick Actions --}}
            <div class="d-none d-md-flex align-items-center gap-1">
                @can('booking.view')
                <a href="{{ route('bookings.index') }}" class="btn btn-outline-primary btn-sm" title="Booking Baru" style="font-size:0.75rem;">
                    <i class="fas fa-calendar-plus me-1"></i>Booking
                </a>
                @endcan
                @can('service.view')
                <a href="{{ route('services.create') }}" class="btn btn-outline-primary btn-sm" title="Job Card Baru" style="font-size:0.75rem;">
                    <i class="fas fa-clipboard-check me-1"></i>Job Card
                </a>
                @endcan
                @can('invoice.view')
                <a href="{{ route('invoices.create') }}" class="btn btn-outline-primary btn-sm" title="Invoice Baru" style="font-size:0.75rem;">
                    <i class="fas fa-file-invoice me-1"></i>Invoice
                </a>
                @endcan
            </div>

            {{-- Notification Bell --}}
            @php
                $unreadCount = 0;
                try {
                    $notifications = \App\Models\NotificationQueue::where('status','pending')->latest()->limit(5)->get();
                    $reminders = \App\Models\Reminder::where('sent',false)->where('reminder_date','>=',now()->toDateString())->latest()->limit(5)->get();
                    $unreadCount = $notifications->count() + $reminders->count();
                } catch(\Throwable $e) { $notifications = collect(); $reminders = collect(); }
            @endphp
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary position-relative" data-bs-toggle="dropdown" id="notifBell">
                    <i class="fas fa-bell"></i>
                    @if($unreadCount > 0)
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="notifBadge" style="font-size:0.6rem;">
                        {{ $unreadCount > 99 ? '99+' : $unreadCount }}
                    </span>
                    @endif
                </button>
                <ul class="dropdown-menu dropdown-menu-end" style="width:320px;max-height:400px;overflow-y:auto;">
                    <li class="dropdown-header fw-semibold small text-uppercase">Notifications</li>
                    @forelse($notifications as $n)
                    <li>
                        <a href="#" class="dropdown-item small py-2 d-flex">
                            <i class="fas fa-envelope me-2 text-muted mt-1"></i>
                            <div>
                                <div class="text-truncate" style="max-width:230px;">{{ Str::limit($n->message ?? 'Notification', 50) }}</div>
                                <small class="text-muted">{{ $n->created_at ? $n->created_at->diffForHumans() : '-' }}</small>
                            </div>
                        </a>
                    </li>
                    @empty
                    @endforelse
                    @forelse($reminders as $r)
                    <li>
                        <a href="{{ route('reminders.show', $r) }}" class="dropdown-item small py-2 d-flex">
                            <i class="fas fa-clock me-2 text-warning mt-1"></i>
                            <div>
                                <div class="text-truncate" style="max-width:230px;">{{ $r->reminder_type }}</div>
                                <small class="text-muted">{{ \Carbon\Carbon::parse($r->reminder_date)->diffForHumans() }}</small>
                            </div>
                        </a>
                    </li>
                    @empty
                    @endforelse
                    @if($notifications->isEmpty() && $reminders->isEmpty())
                    <li><span class="dropdown-item text-muted small py-2">No notifications</span></li>
                    @endif
                    <li><hr class="dropdown-divider"></li>
                    <li><a href="{{ route('notification-templates.index') }}" class="dropdown-item small text-center text-primary">Lihat Semua</a></li>
                </ul>
            </div>

            <button class="btn btn-sm btn-outline-secondary" id="darkToggle" title="Dark Mode" onclick="toggleDark()">
                <i class="fas fa-moon"></i>
            </button>

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
        <span class="tenant-name"><i class="fas fa-wrench me-2"></i>{{ config('app.name') }}</span>
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

    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Dark mode
        if(localStorage.getItem('dark')==='1') document.body.classList.add('dark');
        function toggleDark(){ document.body.classList.toggle('dark'); localStorage.setItem('dark',document.body.classList.contains('dark')?'1':'0');
            document.getElementById('darkToggle').innerHTML=document.body.classList.contains('dark')?'<i class="fas fa-sun"></i>':'<i class="fas fa-moon"></i>'; }
        // Auto-show toasts
        document.querySelectorAll('.toast').forEach(t => new bootstrap.Toast(t).show());
        // Auto-refresh notification badge every 60s
        setInterval(function(){
            fetch(window.location.href,{headers:{'X-Requested-With':'XMLHttpRequest'}})
                .then(function(r){return r.text();})
                .then(function(html){
                    var parser=new DOMParser();
                    var doc=parser.parseFromString(html,'text/html');
                    var newBadge=doc.getElementById('notifBadge');
                    var currentBadge=document.getElementById('notifBadge');
                    if(newBadge){
                        if(currentBadge) currentBadge.textContent=newBadge.textContent;
                        else {
                            var bell=document.getElementById('notifBell');
                            if(bell){
                                var span=document.createElement('span');
                                span.id='notifBadge';
                                span.className='position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger';
                                span.style.fontSize='0.6rem';
                                span.textContent=newBadge.textContent;
                                bell.appendChild(span);
                            }
                        }
                    } else {
                        if(currentBadge) currentBadge.remove();
                    }
                }).catch(function(){});
        },60000);

        // ── Sidebar menu search ──
        var menuSearch = document.getElementById('menuSearch');
        if (menuSearch) {
            menuSearch.addEventListener('input', function() {
                var q = this.value.trim().toLowerCase();
                var items = document.querySelectorAll('.sidebar-nav .nav-item');
                items.forEach(function(item) {
                    var text = (item.textContent || '').toLowerCase();
                    if (!q || text.indexOf(q) > -1) {
                        item.style.display = '';
                        // Expand parent if match found
                        var submenu = item.querySelector('.collapse.submenu');
                        if (submenu && q) {
                            submenu.classList.add('show');
                            var btn = item.querySelector('button.nav-link');
                            if (btn) btn.setAttribute('aria-expanded', 'true');
                        }
                    } else {
                        item.style.display = 'none';
                    }
                });
                // Hide section headers in Master Data when searching
                document.querySelectorAll('.submenu-header').forEach(function(h) {
                    h.parentElement.style.display = q ? 'none' : '';
                });
            });
            // Clear search on Escape
            menuSearch.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') { this.value = ''; this.dispatchEvent(new Event('input')); this.blur(); }
            });
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    @stack('scripts')
</body>
</html>
