<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Income;
use App\Models\Invoice;
use App\Models\Service;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public const WIDGETS = [
        'role_widgets' => 'Role Widgets',
        'stat_cards' => 'Stat Cards',
        'revenue_chart' => 'Revenue Chart',
        'status_chart' => 'Status Chart',
        'recent_services' => 'Recent Services',
        'upcoming_services' => 'Upcoming Services',
        'low_stock' => 'Low Stock Alert',
    ];

    public function index(ReportService $reportService)
    {
        $user = auth()->user();
        $enabledWidgets = $this->getEnabledWidgets($user);

        $stats = $reportService->getDashboardStats();
        $recentServices = Service::with(['customer', 'vehicle'])
            ->latest()
            ->limit(10)
            ->get();

        $upcomingServices = Service::with(['customer', 'vehicle'])
            ->where('service_date', '>=', now())
            ->where('service_date', '<=', now()->addDays(7))
            ->orderBy('service_date')
            ->get();

        $chartData = $this->getRevenueChartData();
        $statusChart = $this->getStatusChartData();
        $roleWidgets = $this->getRoleWidgets($user);

        // Low stock alert — show once per session
        $lowStockAlert = false;
        if ($stats['low_stock_count'] > 0 && !session('low_stock_shown')) {
            $lowStockAlert = true;
            session(['low_stock_shown' => true]);
        }

        $lowStockReorder = $reportService->getLowStockReorder();

        return view('dashboard', compact(
            'stats', 'recentServices', 'upcomingServices',
            'chartData', 'statusChart', 'roleWidgets', 'lowStockAlert',
            'lowStockReorder', 'enabledWidgets'
        ));
    }

    public function configure()
    {
        $config = auth()->user()->dashboardConfig?->config;

        $enabled = is_array($config)
            ? array_keys(array_filter($config))
            : array_keys(self::WIDGETS);

        return view('dashboard.config', [
            'widgets' => self::WIDGETS,
            'enabled' => $enabled,
        ]);
    }

    public function saveConfig(Request $request)
    {
        $validated = $request->validate([
            'widgets' => ['array'],
            'widgets.*' => ['string', 'in:' . implode(',', array_keys(self::WIDGETS))],
        ]);

        $enabled = array_fill_keys(array_keys(self::WIDGETS), false);
        foreach ($validated['widgets'] ?? [] as $key) {
            $enabled[$key] = true;
        }

        auth()->user()->dashboardConfig()->updateOrCreate(
            ['user_id' => auth()->id()],
            ['config' => $enabled]
        );

        return redirect()->route('dashboard')->with('success', 'Konfigurasi dashboard disimpan.');
    }

    protected function getEnabledWidgets($user): array
    {
        $config = $user->dashboardConfig?->config;

        if (!is_array($config)) {
            return array_keys(self::WIDGETS);
        }

        $enabled = [];
        foreach (array_keys(self::WIDGETS) as $key) {
            if (!empty($config[$key])) {
                $enabled[] = $key;
            }
        }

        return $enabled;
    }

    protected function getRevenueChartData(): array
    {
        $start = now()->subDays(13)->toDateString();
        $end = now()->toDateString();

        $revenueData = Income::whereBetween('income_date', [$start, $end])
            ->selectRaw('income_date, SUM(amount) as total')
            ->groupBy('income_date')->pluck('total', 'income_date');

        $expenseData = Expense::whereBetween('expense_date', [$start, $end])
            ->selectRaw('expense_date, SUM(amount) as total')
            ->groupBy('expense_date')->pluck('total', 'expense_date');

        $days = []; $revenue = []; $expenses = [];
        for ($i = 13; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $days[] = now()->subDays($i)->format('d/m');
            $revenue[] = $revenueData[$date] ?? 0;
            $expenses[] = $expenseData[$date] ?? 0;
        }

        return ['days' => $days, 'revenue' => $revenue, 'expenses' => $expenses];
    }

    protected function getStatusChartData(): array
    {
        return [
            'booked' => Service::where('workflow_status', 0)->count(),
            'checked_in' => Service::where('workflow_status', 1)->count(),
            'inspection' => Service::where('workflow_status', 2)->count(),
            'waiting_approval' => Service::where('workflow_status', 3)->count(),
            'approved' => Service::where('workflow_status', 4)->count(),
            'in_progress' => Service::where('workflow_status', 5)->count(),
            'waiting_parts' => Service::where('workflow_status', 6)->count(),
            'qc' => Service::where('workflow_status', 7)->count(),
            'ready' => Service::where('workflow_status', 8)->count(),
            'invoiced' => Service::where('workflow_status', 9)->count(),
            'paid' => Service::where('workflow_status', 10)->count(),
            'released' => Service::where('workflow_status', 11)->count(),
            'completed' => Service::where('workflow_status', 12)->count(),
        ];
    }

    protected function getRoleWidgets($user): array
    {
        $widgets = [];

        // Owner & Admin: financial overview
        if ($user->hasRole('owner') || $user->hasRole('admin')) {
            $widgets['revenue'] = Income::whereMonth('income_date', now()->month)->sum('amount');
            $widgets['expense'] = Expense::whereMonth('expense_date', now()->month)->sum('amount');
            $widgets['profit'] = $widgets['revenue'] - $widgets['expense'];
            $widgets['total_invoices'] = Invoice::count();
            $widgets['unpaid_invoices'] = Invoice::whereIn('payment_status', [0, 1])->count();
        }

        // Manager: operational metrics
        if ($user->hasRole('manager') || $user->hasRole('admin')) {
            $widgets['services_today'] = Service::whereDate('service_date', today())->count();
            $widgets['services_pending'] = Service::where('done_status', 0)->count();
            $widgets['services_completed'] = Service::where('done_status', 2)->whereDate('updated_at', today())->count();
        }

        // Teknisi: assigned tasks
        if ($user->hasRole('teknisi')) {
            $widgets['my_pending'] = Service::where('done_status', '!=', 2)
                ->where(function ($q) use ($user) {
                    $q->where('assign_to', $user->id)
                      ->orWhereHas('technicians', fn($t) => $t->where('users.id', $user->id));
                })->count();
            $widgets['my_completed_today'] = Service::where('done_status', 2)
                ->whereDate('updated_at', today())
                ->where(function ($q) use ($user) {
                    $q->where('assign_to', $user->id)
                      ->orWhereHas('technicians', fn($t) => $t->where('users.id', $user->id));
                })->count();
            $widgets['my_commission'] = \App\Models\ServiceTechnician::where('user_id', $user->id)
                ->whereNull('paid_at')
                ->sum('commission_amt');
        }

        // Kasir: POS stats
        if ($user->hasRole('kasir')) {
            $widgets['pos_today'] = Invoice::where('invoice_type', 'pos')
                ->whereDate('invoice_date', today())->count();
            $widgets['pos_revenue_today'] = Invoice::where('invoice_type', 'pos')
                ->whereDate('invoice_date', today())
                ->sum('grand_total');
            $activeSession = \App\Models\PosSession::where('user_id', $user->id)
                ->where('status', 'open')
                ->first();
            $widgets['pos_balance'] = $activeSession?->opening_balance ?? 0;
        }

        return $widgets;
    }
}
