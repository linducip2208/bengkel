<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiReportController extends Controller
{
    public function serviceReport(Request $request): JsonResponse
    {
        $dateFrom = $request->get('date_from', now()->subMonth()->startOfDay()->toDateString());
        $dateTo = $request->get('date_to', now()->endOfDay()->toDateString());

        $services = Service::with(['customer', 'vehicle', 'technicians', 'repairCategory'])
            ->whereBetween('service_date', [$dateFrom, $dateTo])
            ->get();

        $byCategory = $services->groupBy('repair_category_id')->map(function ($group) {
            $category = $group->first()->repairCategory;

            return [
                'category' => $category ? $category->repair_category_name : 'Uncategorized',
                'count' => $group->count(),
                'total_charge' => $group->sum('charge'),
            ];
        })->values();

        $byStatus = [
            'pending' => $services->where('done_status', 0)->count(),
            'in_progress' => $services->where('done_status', 1)->count(),
            'completed' => $services->where('done_status', 2)->count(),
        ];

        return response()->json([
            'date_range' => ['from' => $dateFrom, 'to' => $dateTo],
            'total' => $services->count(),
            'total_charge' => $services->sum('charge'),
            'by_category' => $byCategory,
            'by_status' => $byStatus,
            'services' => $services->map(fn ($s) => [
                'id' => $s->id,
                'title' => $s->title,
                'customer' => $s->customer->name ?? null,
                'vehicle' => $s->vehicle->plate_number ?? null,
                'service_date' => $s->service_date,
                'status' => $s->status,
                'charge' => $s->charge,
            ]),
        ]);
    }

    public function salesReport(Request $request): JsonResponse
    {
        $dateFrom = $request->get('date_from', now()->subMonth()->startOfDay()->toDateString());
        $dateTo = $request->get('date_to', now()->endOfDay()->toDateString());

        $invoices = Invoice::with(['service.customer'])
            ->whereBetween('invoice_date', [$dateFrom, $dateTo])
            ->get();

        $dailyInvoiced = $invoices->groupBy(fn ($i) => optional($i->invoice_date)->toDateString())
            ->map(fn ($g) => [
                'count' => $g->count(),
                'total_discount' => $g->sum('discount'),
            ]);

        $byPaymentStatus = $invoices->groupBy('payment_status')->map(fn ($g) => $g->count());

        return response()->json([
            'date_range' => ['from' => $dateFrom, 'to' => $dateTo],
            'summary' => [
                'total_invoices' => $invoices->count(),
                'total_discount' => $invoices->sum('discount'),
                'total_additional_fee' => $invoices->sum('additional_fee'),
            ],
            'daily' => $dailyInvoiced,
            'by_payment_status' => $byPaymentStatus,
        ]);
    }

    public function stockReport(Request $request): JsonResponse
    {
        $products = Product::with(['productType'])
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'code' => $p->code,
                'name' => $p->name,
                'type' => $p->productType->type ?? null,
                'price' => $p->price,
                'cost_price' => $p->cost_price,
                'stock' => $p->current_stock,
                'minimum_stock' => $p->minimum_stock,
                'rack_location' => $p->rack_location,
                'stock_value' => $p->price * $p->current_stock,
                'stock_status' => $p->stock_status,
            ]);

        $lowStock = $products->filter(fn ($p) => $p['stock'] <= $p['minimum_stock'] && $p['minimum_stock'] > 0);
        $outOfStock = $products->filter(fn ($p) => $p['stock'] <= 0);

        return response()->json([
            'total_products' => $products->count(),
            'total_stock_value' => $products->sum('stock_value'),
            'low_stock_count' => $lowStock->count(),
            'out_of_stock_count' => $outOfStock->count(),
            'products' => $products,
            'low_stock_products' => $lowStock->values(),
            'out_of_stock_products' => $outOfStock->values(),
        ]);
    }

    public function financialReport(Request $request): JsonResponse
    {
        $dateFrom = $request->get('date_from', now()->subMonth()->startOfDay()->toDateString());
        $dateTo = $request->get('date_to', now()->endOfDay()->toDateString());

        $totalIncome = Income::whereBetween('income_date', [$dateFrom, $dateTo])->sum('amount');
        $totalExpense = Expense::whereBetween('expense_date', [$dateFrom, $dateTo])->sum('amount');

        $dailyIncome = Income::whereBetween('income_date', [$dateFrom, $dateTo])
            ->selectRaw('DATE(income_date) as date, SUM(amount) as total')
            ->groupBy('date')
            ->get()
            ->pluck('total', 'date');

        $dailyExpense = Expense::whereBetween('expense_date', [$dateFrom, $dateTo])
            ->selectRaw('DATE(expense_date) as date, SUM(amount) as total')
            ->groupBy('date')
            ->get()
            ->pluck('total', 'date');

        $expenseByCategory = Expense::whereBetween('expense_date', [$dateFrom, $dateTo])
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->get()
            ->pluck('total', 'category');

        return response()->json([
            'date_range' => ['from' => $dateFrom, 'to' => $dateTo],
            'summary' => [
                'total_income' => $totalIncome,
                'total_expense' => $totalExpense,
                'net' => $totalIncome - $totalExpense,
            ],
            'daily_income' => $dailyIncome,
            'daily_expense' => $dailyExpense,
            'expense_by_category' => $expenseByCategory,
        ]);
    }
}
