<?php

namespace App\Http\Controllers;

use App\Services\ReportService;

class DashboardController extends Controller
{
    public function index(ReportService $reportService)
    {
        $stats = $reportService->getDashboardStats();
        $recentServices = \App\Models\Service::with(['customer', 'vehicle'])
            ->latest()
            ->limit(10)
            ->get();

        $upcomingServices = \App\Models\Service::with(['customer', 'vehicle'])
            ->where('service_date', '>=', now())
            ->where('service_date', '<=', now()->addDays(7))
            ->orderBy('service_date')
            ->get();

        return view('dashboard', compact('stats', 'recentServices', 'upcomingServices'));
    }
}
