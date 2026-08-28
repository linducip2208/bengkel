<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Service;
use App\Models\StockHistory;
use App\Models\StockRecord;
use App\Models\SupplierPrice;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function serviceReport(array $filters): array
    {
        $query = Service::with(['customer', 'vehicle']);

        if (! empty($filters['start_date'])) {
            $query->whereDate('service_date', '>=', $filters['start_date']);
        }
        if (! empty($filters['end_date'])) {
            $query->whereDate('service_date', '<=', $filters['end_date']);
        }
        if (! empty($filters['status'])) {
            $query->where('done_status', $filters['status']);
        }

        $services = $query->latest('service_date')->get();

        $totalServices = $services->count();
        $totalRevenue = $services->sum('charge');
        $totalActualCost = $services->sum('actual_cost');
        $totalVariance = (float) $totalActualCost - (float) $totalRevenue;
        $avgValue = $totalServices > 0 ? $totalRevenue / $totalServices : 0;

        $byDate = $services->groupBy(fn ($s) => Carbon::parse($s->service_date)->format('Y-m-d'))
            ->map(fn ($group) => [
                'count' => $group->count(),
                'revenue' => $group->sum('charge'),
            ]);

        $byTechnician = $services->groupBy('assign_to')
            ->map(fn ($group, $techId) => [
                'technician_id' => $techId,
                'technician_name' => User::find($techId)?->name ?? 'Unassigned',
                'count' => $group->count(),
                'revenue' => $group->sum('charge'),
            ]);

        return [
            'services' => $services,
            'total_services' => $totalServices,
            'total_revenue' => $totalRevenue,
            'total_actual_cost' => $totalActualCost,
            'total_variance' => $totalVariance,
            'avg_value' => $avgValue,
            'by_date' => $byDate,
            'by_technician' => $byTechnician,
        ];
    }

    public function salesReport(array $filters): array
    {
        // Sales merged into POS — report POS invoices (spare part sales)
        $query = Invoice::with(['customer'])
            ->where('invoice_type', 'pos');

        if (! empty($filters['start_date'])) {
            $query->whereDate('invoice_date', '>=', $filters['start_date']);
        }
        if (! empty($filters['end_date'])) {
            $query->whereDate('invoice_date', '<=', $filters['end_date']);
        }

        $sales = $query->latest('invoice_date')->get();
        $totalSales = $sales->count();
        $totalRevenue = $sales->sum('grand_total');

        $byDate = $sales->groupBy(fn ($s) => Carbon::parse($s->invoice_date)->format('Y-m-d'))
            ->map(fn ($group) => [
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
        $query = Product::with(['productType', 'unit', 'stockRecord']);

        if (! empty($filters['category_id'])) {
            $query->where('product_type_id', $filters['category_id']);
        }

        $products = $query->get();

        $productIds = $products->pluck('id')->toArray();
        $stockIn = StockHistory::whereIn('product_id', $productIds)
            ->where('quantity_change', '>', 0)
            ->selectRaw('product_id, SUM(quantity_change) as total_in')
            ->groupBy('product_id')->pluck('total_in', 'product_id');
        $stockOut = StockHistory::whereIn('product_id', $productIds)
            ->where('quantity_change', '<', 0)
            ->selectRaw('product_id, SUM(ABS(quantity_change)) as total_out')
            ->groupBy('product_id')->pluck('total_out', 'product_id');
        $purchases = Purchase::whereHas('items', fn ($q) => $q->whereIn('product_id', $productIds))
            ->with(['items' => fn ($q) => $q->whereIn('product_id', $productIds)])
            ->latest('purchase_date')->get()
            ->flatMap(fn ($p) => $p->items->map(fn ($i) => [$i->product_id => $p->purchase_date]))
            ->collapse();
        $lastUsages = StockHistory::whereIn('product_id', $productIds)
            ->where('quantity_change', '<', 0)
            ->selectRaw('product_id, MAX(created_at) as last_used')
            ->groupBy('product_id')->pluck('last_used', 'product_id');

        $products = $products->map(function ($product) use ($stockIn, $stockOut, $purchases, $lastUsages) {
            $totalIn = $stockIn[$product->id] ?? 0;
            $totalOut = $stockOut[$product->id] ?? 0;

            $product->current_stock = $totalIn - $totalOut;
            $product->last_purchase_date = $purchases[$product->id] ?? null;
            $product->last_usage_date = $lastUsages[$product->id] ?? null;
            $product->total_value = $product->current_stock * ($product->cost_price ?? $product->price ?? 0);

            return $product;
        });

        $lowStock = $products->filter(fn ($p) => $p->current_stock <= ($p->minimum_stock ?? 5));
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

        // Paid invoices
        $paidInvoices = Invoice::where('payment_status', 2)
            ->whereBetween('invoice_date', [$startDate, $endDate])
            ->sum('grand_total');
        $paidCount = Invoice::where('payment_status', 2)
            ->whereBetween('invoice_date', [$startDate, $endDate])
            ->count();

        $monthlyBreakdown = collect([]);

        $months = CarbonPeriod::create($startDate, '1 month', $endDate);
        foreach ($months as $month) {
            $monthStart = $month->copy()->startOfMonth()->toDateString();
            $monthEnd = $month->copy()->endOfMonth()->toDateString();

            $income = Income::whereBetween('income_date', [$monthStart, $monthEnd])->sum('amount');
            $expense = Expense::whereBetween('expense_date', [$monthStart, $monthEnd])->sum('amount');
            $paidInv = Invoice::where('payment_status', 2)
                ->whereBetween('invoice_date', [$monthStart, $monthEnd])->sum('grand_total');

            $monthlyBreakdown->push([
                'month' => $month->format('M Y'),
                'income' => $income,
                'expense' => $expense,
                'profit' => $income - $expense,
                'paid_invoices' => $paidInv,
            ]);
        }

        return [
            'total_income' => $totalIncome,
            'total_expense' => $totalExpense,
            'profit' => $profit,
            'paid_invoices' => $paidInvoices,
            'paid_count' => $paidCount,
            'monthly_breakdown' => $monthlyBreakdown,
        ];
    }

    public function getLowStockReorder(): array
    {
        return StockRecord::with('product')
            ->where('quantity', '<=', DB::raw('COALESCE(minimum_stock, 5)'))
            ->where('quantity', '>=', 0)
            ->get()
            ->map(function ($record) {
                $minStock = $record->minimum_stock ?? 5;
                $suggestedReorder = max(($minStock * 2) - $record->quantity, 0);

                return [
                    'product_name' => $record->product?->name ?? 'Unknown',
                    'sku' => $record->product?->product_no ?? '-',
                    'current_stock' => $record->quantity,
                    'minimum_stock' => $minStock,
                    'suggested_reorder' => $suggestedReorder,
                    'last_purchase_price' => $record->product?->cost_price ?? 0,
                ];
            })
            ->sortByDesc('suggested_reorder')
            ->values()
            ->toArray();
    }

    public function getReorderSuggestions(): array
    {
        $records = StockRecord::with(['product'])
            ->where('quantity', '<=', DB::raw('COALESCE(minimum_stock, 5)'))
            ->where('quantity', '>=', 0)
            ->get();

        $productIds = $records->pluck('product_id')->filter()->unique()->values();

        $prices = SupplierPrice::with('supplier')
            ->whereIn('product_id', $productIds)
            ->where('is_active', true)
            ->orderBy('price')
            ->get()
            ->groupBy('product_id');

        return $records->map(function ($record) use ($prices) {
            $minStock = $record->minimum_stock ?? 5;
            $suggestedReorder = max(($minStock * 2) - $record->quantity, 0);

            $cheapest = $prices->get($record->product_id)?->first();

            return [
                'product_id' => $record->product_id,
                'product_name' => $record->product?->name ?? 'Unknown',
                'sku' => $record->product?->product_no ?? '-',
                'current_stock' => $record->quantity,
                'minimum_stock' => $minStock,
                'suggested_reorder' => $suggestedReorder,
                'cheapest_supplier_id' => $cheapest?->supplier_id,
                'cheapest_supplier_name' => $cheapest?->supplier?->name ?? '-',
                'cheapest_price' => $cheapest?->price ?? 0,
            ];
        })
            ->sortByDesc('suggested_reorder')
            ->values()
            ->toArray();
    }

    public function getDashboardStats(): array
    {
        $today = now()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();

        $openServices = Service::where('workflow_status', '<', 12)->count();
        $completedToday = Service::where('workflow_status', 12)
            ->whereDate('updated_at', $today)->count();

        $revenueToday = Income::whereDate('income_date', $today)->sum('amount');

        $revenueThisMonth = Income::whereBetween('income_date', [$monthStart, $monthEnd])->sum('amount');

        $outstandingInvoices = Invoice::where('payment_status', '!=', 2)->count();

        $lowStockCount = StockRecord::where('quantity', '<=', DB::raw('COALESCE(minimum_stock, 5)'))->count();

        return [
            'open_services' => $openServices,
            'completed_today' => $completedToday,
            'revenue_today' => $revenueToday,
            'revenue_this_month' => $revenueThisMonth,
            'outstanding_invoices' => $outstandingInvoices,
            'low_stock_count' => $lowStockCount,
        ];
    }

    public function arAgingReport(): array
    {
        $today = Carbon::today();
        $invoices = Invoice::where('payment_status', '!=', 2)
            ->with('customer')
            ->get()
            ->map(function ($inv) use ($today) {
                $dueDate = Carbon::parse($inv->due_date ?? $inv->invoice_date)->startOfDay();
                $inv->days_overdue = $dueDate->lt($today) ? (int) $dueDate->diffInDays($today) : 0;
                $inv->remaining = max($inv->grand_total - ($inv->paid_amount ?? 0), 0);
                $inv->age_group = match (true) {
                    $inv->days_overdue <= 0 => 'current',
                    $inv->days_overdue <= 30 => '1-30',
                    $inv->days_overdue <= 60 => '31-60',
                    $inv->days_overdue <= 90 => '61-90',
                    default => '90+',
                };

                return $inv;
            });

        $aging = $invoices->groupBy('age_group')->map(fn ($g) => [
            'count' => $g->count(),
            'total' => $g->sum('remaining'),
        ]);

        return ['invoices' => $invoices, 'aging' => $aging];
    }

    public function partsUsageReport(array $filters): array
    {
        $query = StockHistory::with('product.productType')
            ->where('type', 'usage')
            ->where('quantity_change', '<', 0);

        if (! empty($filters['start_date'])) {
            $query->whereDate('created_at', '>=', $filters['start_date']);
        }
        if (! empty($filters['end_date'])) {
            $query->whereDate('created_at', '<=', $filters['end_date']);
        }

        $usage = $query->selectRaw('product_id, SUM(ABS(quantity_change)) as total_qty, COUNT(*) as usage_count')
            ->groupBy('product_id')
            ->orderByDesc('total_qty')
            ->get()
            ->map(function ($u) {
                $product = Product::find($u->product_id);
                $u->product_name = $product?->name ?? 'Unknown';
                $u->category = $product?->productType?->name ?? '-';
                $u->unit_cost = $product?->cost_price ?? 0;
                $u->total_cost = $u->total_qty * $u->unit_cost;

                return $u;
            });

        return ['usages' => $usage, 'total_cost' => $usage->sum('total_cost')];
    }

    public function branchComparison(array $filters): array
    {
        $branches = Branch::where('is_active', true)->get();
        $startDate = $filters['start_date'] ?? now()->startOfMonth()->toDateString();
        $endDate = $filters['end_date'] ?? now()->toDateString();

        $results = $branches->map(function ($branch) use ($startDate, $endDate) {
            $serviceRevenue = Service::where('branch_id', $branch->id)
                ->whereBetween('service_date', [$startDate, $endDate])
                ->sum('charge');
            $serviceCount = Service::where('branch_id', $branch->id)
                ->whereBetween('service_date', [$startDate, $endDate])
                ->count();
            $posRevenue = Invoice::where('branch_id', $branch->id)
                ->where('invoice_type', 'pos')
                ->whereBetween('invoice_date', [$startDate, $endDate])
                ->sum('grand_total');
            $posCount = Invoice::where('branch_id', $branch->id)
                ->where('invoice_type', 'pos')
                ->whereBetween('invoice_date', [$startDate, $endDate])
                ->count();

            return [
                'id' => $branch->id,
                'name' => $branch->name,
                'service_revenue' => $serviceRevenue,
                'service_count' => $serviceCount,
                'pos_revenue' => $posRevenue,
                'pos_count' => $posCount,
                'total_revenue' => $serviceRevenue + $posRevenue,
            ];
        });

        return ['branches' => $results, 'total_revenue' => $results->sum('total_revenue')];
    }

    public function cashFlowReport(array $filters): array
    {
        $startDate = $filters['start_date'] ?? now()->subDays(30)->toDateString();
        $endDate = $filters['end_date'] ?? now()->toDateString();

        $dailyIncome = Income::whereBetween('income_date', [$startDate, $endDate])
            ->selectRaw('income_date as date, SUM(amount) as total')
            ->groupBy('date')->pluck('total', 'date');

        $dailyExpense = Expense::whereBetween('expense_date', [$startDate, $endDate])
            ->selectRaw('expense_date as date, SUM(amount) as total')
            ->groupBy('date')->pluck('total', 'date');

        $daily = [];
        $period = CarbonPeriod::create($startDate, $endDate);
        foreach ($period as $day) {
            $d = $day->toDateString();
            $inc = $dailyIncome[$d] ?? 0;
            $exp = $dailyExpense[$d] ?? 0;
            $daily[] = ['date' => $d, 'income' => $inc, 'expense' => $exp, 'net' => $inc - $exp];
        }

        return [
            'daily' => $daily,
            'total_income' => array_sum(array_column($daily, 'income')),
            'total_expense' => array_sum(array_column($daily, 'expense')),
            'net' => array_sum(array_column($daily, 'net')),
        ];
    }
}
