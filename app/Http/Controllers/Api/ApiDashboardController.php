<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Service;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;

class ApiDashboardController extends Controller
{
    public function stats(): JsonResponse
    {
        $today = now()->startOfDay();
        $thisMonth = now()->startOfMonth();

        return response()->json([
            'today' => [
                'services_created' => Service::whereDate('created_at', $today)->count(),
                'services_completed' => Service::where('done_status', 2)->whereDate('updated_at', $today)->count(),
                'invoices_created' => Invoice::whereDate('created_at', $today)->count(),
                'revenue' => Income::whereDate('income_date', $today)->sum('amount'),
                'new_customers' => Customer::whereDate('created_at', $today)->count(),
            ],
            'this_month' => [
                'services_created' => Service::where('created_at', '>=', $thisMonth)->count(),
                'services_completed' => Service::where('done_status', 2)->where('updated_at', '>=', $thisMonth)->count(),
                'invoices_created' => Invoice::where('created_at', '>=', $thisMonth)->count(),
                'revenue' => Income::where('income_date', '>=', $thisMonth)->sum('amount'),
                'expenses' => Expense::where('expense_date', '>=', $thisMonth)->sum('amount'),
                'new_customers' => Customer::where('created_at', '>=', $thisMonth)->count(),
            ],
            'totals' => [
                'customers' => Customer::count(),
                'vehicles' => Vehicle::count(),
                'pending_services' => Service::whereIn('done_status', [0, 1])->count(),
                'completed_services' => Service::where('done_status', 2)->count(),
                'unpaid_invoices' => Invoice::whereIn('payment_status', [0, 1])->count(),
                'products' => Product::count(),
                'low_stock_products' => Product::whereHas('stockRecord', fn ($q) => $q->whereColumn('quantity', '<=', 'minimum_stock'))->count(),
            ],
            'revenue_chart' => $this->getMonthlyRevenue(),
            'service_chart' => $this->getMonthlyServices(),
        ]);
    }

    protected function getMonthlyRevenue(): array
    {
        return Income::selectRaw("DATE_FORMAT(income_date, '%Y-%m') as month, SUM(amount) as total")
            ->whereYear('income_date', now()->year)
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->pluck('total', 'month')
            ->toArray();
    }

    protected function getMonthlyServices(): array
    {
        return Service::selectRaw("DATE_FORMAT(service_date, '%Y-%m') as month, COUNT(*) as total")
            ->whereYear('service_date', now()->year)
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->pluck('total', 'month')
            ->toArray();
    }
}
