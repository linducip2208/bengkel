<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Income;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Service;
use App\Models\StockHistory;
use App\Models\StockRecord;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function serviceReport(array $filters): array
    {
        $query = Service::with(['customer', 'vehicle']);

        if (!empty($filters['start_date'])) {
            $query->whereDate('service_date', '>=', $filters['start_date']);
        }
        if (!empty($filters['end_date'])) {
            $query->whereDate('service_date', '<=', $filters['end_date']);
        }
        if (!empty($filters['status'])) {
            $query->where('done_status', $filters['status']);
        }

        $services = $query->latest('service_date')->get();

        $totalServices = $services->count();
        $totalRevenue = $services->sum('charge');
        $avgValue = $totalServices > 0 ? $totalRevenue / $totalServices : 0;

        $byDate = $services->groupBy(fn($s) => \Carbon\Carbon::parse($s->service_date)->format('Y-m-d'))
            ->map(fn($group) => [
                'count' => $group->count(),
                'revenue' => $group->sum('charge'),
            ]);

        $byTechnician = $services->groupBy('assign_to')
            ->map(fn($group, $techId) => [
                'technician_id' => $techId,
                'technician_name' => \App\Models\User::find($techId)?->name ?? 'Unassigned',
                'count' => $group->count(),
                'revenue' => $group->sum('charge'),
            ]);

        return [
            'services' => $services,
            'total_services' => $totalServices,
            'total_revenue' => $totalRevenue,
            'avg_value' => $avgValue,
            'by_date' => $byDate,
            'by_technician' => $byTechnician,
        ];
    }

    public function salesReport(array $filters): array
    {
        $query = Sale::with(['customer', 'vehicle']);

        if (!empty($filters['start_date'])) {
            $query->whereDate('sale_date', '>=', $filters['start_date']);
        }
        if (!empty($filters['end_date'])) {
            $query->whereDate('sale_date', '<=', $filters['end_date']);
        }

        $sales = $query->latest('sale_date')->get();
        $totalSales = $sales->count();
        $totalRevenue = $sales->sum('grand_total');

        $byDate = $sales->groupBy(fn($s) => \Carbon\Carbon::parse($s->sale_date)->format('Y-m-d'))
            ->map(fn($group) => [
                'count' => $group->count(),
                'revenue' => $group->sum('grand_total'),
            ]);

        return [
            'sales' => $sales,
            'total_sales' => $totalSales,
            'total_revenue' => $totalRevenue,
            'by_date' => $byDate,
        ];
    }

    public function stockReport(array $filters): array
    {
        $query = Product::with(['productType', 'unit']);

        if (!empty($filters['category_id'])) {
            $query->where('product_type_id', $filters['category_id']);
        }

        $products = $query->get()->map(function ($product) {
            $totalIn = StockHistory::where('product_id', $product->id)->where('quantity_change', '>', 0)->sum('quantity_change');
            $totalOut = StockHistory::where('product_id', $product->id)->where('quantity_change', '<', 0)->sum(DB::raw('ABS(quantity_change)'));
            $lastPurchase = Purchase::whereHas('items', fn($q) => $q->where('product_id', $product->id))
                ->latest('purchase_date')->first();
            $lastUsage = StockHistory::where('product_id', $product->id)->where('quantity_change', '<', 0)->latest()->first();

            $product->current_stock = $totalIn - $totalOut;
            $product->last_purchase_date = $lastPurchase?->purchase_date;
            $product->last_usage_date = $lastUsage?->created_at;
            $product->total_value = $product->current_stock * ($product->cost_price ?? $product->price ?? 0);

            return $product;
        });

        $lowStock = $products->filter(fn($p) => $p->current_stock <= ($p->minimum_stock ?? 5));
        $totalValue = $products->sum('total_value');

        return [
            'products' => $products,
            'low_stock' => $lowStock,
            'total_value' => $totalValue,
            'total_products' => $products->count(),
        ];
    }

    public function financialReport(array $filters): array
    {
        $startDate = $filters['start_date'] ?? now()->startOfYear()->toDateString();
        $endDate = $filters['end_date'] ?? now()->toDateString();

        $totalIncome = Income::whereBetween('income_date', [$startDate, $endDate])->sum('amount');
        $totalExpense = Expense::whereBetween('expense_date', [$startDate, $endDate])->sum('amount');
        $profit = $totalIncome - $totalExpense;

        $monthlyBreakdown = collect([]);

        $months = \Carbon\CarbonPeriod::create($startDate, '1 month', $endDate);
        foreach ($months as $month) {
            $monthStart = $month->copy()->startOfMonth()->toDateString();
            $monthEnd = $month->copy()->endOfMonth()->toDateString();

            $income = Income::whereBetween('income_date', [$monthStart, $monthEnd])->sum('amount');
            $expense = Expense::whereBetween('expense_date', [$monthStart, $monthEnd])->sum('amount');

            $monthlyBreakdown->push([
                'month' => $month->format('M Y'),
                'income' => $income,
                'expense' => $expense,
                'profit' => $income - $expense,
            ]);
        }

        return [
            'total_income' => $totalIncome,
            'total_expense' => $totalExpense,
            'profit' => $profit,
            'monthly_breakdown' => $monthlyBreakdown,
        ];
    }

    public function getDashboardStats(): array
    {
        $today = now()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();

        $openServices = Service::where('done_status', '!=', 2)->count();
        $completedToday = Service::where('done_status', 2)
            ->whereDate('updated_at', $today)->count();

        $revenueToday = Income::whereDate('income_date', $today)->sum('amount');

        $revenueThisMonth = Income::whereBetween('income_date', [$monthStart, $monthEnd])->sum('amount');

        $outstandingInvoices = Invoice::where('payment_status', '!=', 2)->count();

        $lowStockCount = Product::get()->filter(function ($product) {
            $totalIn = StockHistory::where('product_id', $product->id)->where('quantity_change', '>', 0)->sum('quantity_change');
            $totalOut = StockHistory::where('product_id', $product->id)->where('quantity_change', '<', 0)->sum(DB::raw('ABS(quantity_change)'));
            return ($totalIn - $totalOut) <= 5;
        })->count();

        return [
            'open_services' => $openServices,
            'completed_today' => $completedToday,
            'revenue_today' => $revenueToday,
            'revenue_this_month' => $revenueThisMonth,
            'outstanding_invoices' => $outstandingInvoices,
            'low_stock_count' => $lowStockCount,
        ];
    }
}
