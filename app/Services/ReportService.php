<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Service;
use App\Models\ServiceEstimate;
use App\Models\ServiceFinding;
use App\Models\ServiceWorkPackage;
use App\Models\ServiceWorkTask;
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

    /**
     * Workshop Operating System counters (operational pipeline health).
     */
    public function getWorkshopFlowStats(): array
    {
        $criticalFindings = ServiceFinding::whereIn('status', [
            ServiceFinding::STATUS_OPEN,
            ServiceFinding::STATUS_WORK_PROPOSED,
        ])->where('severity', ServiceFinding::SEVERITY_CRITICAL)->count();

        $pendingEstimates = ServiceEstimate::whereIn('status', [
            ServiceEstimate::STATUS_SENT,
            ServiceEstimate::STATUS_WAITING_APPROVAL,
        ])->count();

        $approvedPackages = ServiceWorkPackage::where('status', ServiceWorkPackage::STATUS_APPROVED)->count();
        $inProgressTasks = ServiceWorkTask::where('status', ServiceWorkTask::STATUS_IN_PROGRESS)->count();
        $awaitingQc = ServiceWorkTask::where('status', ServiceWorkTask::STATUS_QC_PENDING)->count();
        $readyServices = Service::where('workflow_status', 8)->count();

        // Services still in inspection with an incomplete checklist.
        $incompleteChecklists = Service::whereIn('workflow_status', [1, 2])
            ->whereHas('serviceObservationPoints', fn ($q) => $q->where('condition_status', 'not_checked'))
            ->whereHas('serviceObservationPoints')
            ->count();

        return [
            'incomplete_checklists' => $incompleteChecklists,
            'critical_findings' => $criticalFindings,
            'pending_estimates' => $pendingEstimates,
            'approved_packages' => $approvedPackages,
            'in_progress_tasks' => $inProgressTasks,
            'awaiting_qc' => $awaitingQc,
            'ready_services' => $readyServices,
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

    /**
     * Standard vs actual time per work package (variance reporting).
     */
    public function workPackageTimeReport(?string $startDate = null, ?string $endDate = null): array
    {
        $packages = ServiceWorkPackage::query()
            ->with(['task', 'service:id,job_no', 'finding:id,finding_number,title'])
            ->when($startDate !== null && $startDate !== '', fn ($q) => $q->whereDate('created_at', '>=', $startDate))
            ->when($endDate !== null && $endDate !== '', fn ($q) => $q->whereDate('created_at', '<=', $endDate))
            ->orderByDesc('id')
            ->limit(200)
            ->get();

        $rows = $packages->map(function ($package) {
            /** @var ServiceWorkPackage $package */
            $task = $package->task;
            $standard = (int) ($task?->standard_minutes ?? $package->standard_minutes);
            $actual = $task !== null ? $task->actualMinutes() : null;

            return [
                'title' => $package->title,
                'job_no' => $package->service?->job_no,
                'finding_number' => $package->finding?->finding_number,
                'status_label' => ServiceWorkPackage::STATUS_LABELS[$package->status] ?? $package->status,
                'standard_minutes' => $standard,
                'actual_minutes' => $actual,
                'variance_minutes' => $actual !== null ? $actual - $standard : null,
            ];
        });

        $measured = $rows->filter(fn ($r) => $r['actual_minutes'] !== null);
        $totalStandard = (int) $measured->sum('standard_minutes');
        $totalActual = (int) $measured->sum('actual_minutes');

        return [
            'rows' => $rows,
            'total_standard_minutes' => $totalStandard,
            'total_actual_minutes' => $totalActual,
            'total_variance_minutes' => $totalActual - $totalStandard,
            'efficiency' => $totalActual > 0 ? round($totalStandard / $totalActual * 100, 1) : null,
        ];
    }

    /**
     * Per-technician execution report: completed tasks + standard/actual minutes.
     */
    public function technicianTimeReport(): array
    {
        $tasks = ServiceWorkTask::query()
            ->with(['assignee:id,name'])
            ->whereNotNull('assigned_to')
            ->get();

        $byTechnician = [];

        foreach ($tasks as $task) {
            /** @var ServiceWorkTask $task */
            $techId = (int) $task->assigned_to;
            if (! isset($byTechnician[$techId])) {
                $byTechnician[$techId] = [
                    'technician_id' => $techId,
                    'technician_name' => $task->assignee?->name ?? "User #{$techId}",
                    'total_tasks' => 0,
                    'completed_tasks' => 0,
                    'standard_minutes' => 0,
                    'actual_minutes' => 0,
                ];
            }

            $byTechnician[$techId]['total_tasks']++;
            $done = in_array($task->status, [ServiceWorkTask::STATUS_QC_PENDING, ServiceWorkTask::STATUS_QC_PASSED, ServiceWorkTask::STATUS_COMPLETED], true);
            if ($done) {
                $byTechnician[$techId]['completed_tasks']++;
                $byTechnician[$techId]['standard_minutes'] += (int) $task->standard_minutes;
                $byTechnician[$techId]['actual_minutes'] += $task->actualMinutes();
            }
        }

        $rows = collect($byTechnician)->values()->sortByDesc('completed_tasks')->values()->all();

        return ['rows' => $rows];
    }
}
