<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\RepairCategory;
use App\Models\Service;
use App\Models\User;
use App\Services\ReportService;
use App\Services\SettingsService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ReportController extends Controller
{
    public function index(ReportService $reportService)
    {
        $stats = $reportService->getDashboardStats();
        return view('reports.index', compact('stats'));
    }

    public function serviceReport(Request $request, ReportService $reportService)
    {
        $filters = $request->only(['start_date', 'end_date', 'technician_id', 'status']);
        $report = $reportService->serviceReport($filters);

        $technicians = User::role('mekanik')->get();

        return view('reports.service', compact('report', 'technicians', 'filters'));
    }

    public function salesReport(Request $request, ReportService $reportService)
    {
        $filters = $request->only(['start_date', 'end_date']);
        $report = $reportService->salesReport($filters);

        return view('reports.sales', compact('report', 'filters'));
    }

    public function stockReport(Request $request, ReportService $reportService)
    {
        $filters = $request->only(['category_id']);
        $report = $reportService->stockReport($filters);
        $categories = \App\Models\ProductType::orderBy('type')->get();

        return view('reports.stock', compact('report', 'categories', 'filters'));
    }

    public function financialReport(Request $request, ReportService $reportService)
    {
        $filters = $request->only(['start_date', 'end_date', 'year', 'month']);
        $report = $reportService->financialReport($filters);

        return view('reports.financial', compact('report', 'filters'));
    }

    public function exportPdf(Request $request)
    {
        $type = $request->get('type', 'service');
        $filters = $request->only(['start_date', 'end_date', 'technician_id', 'status', 'category_id', 'year', 'month']);

        $reportService = app(ReportService::class);
        $report = match ($type) {
            'sales' => $reportService->salesReport($filters),
            'stock' => $reportService->stockReport($filters),
            'financial' => $reportService->financialReport($filters),
            default => $reportService->serviceReport($filters),
        };

        $pdf = Pdf::loadView("reports.pdf.{$type}", compact('report', 'filters'));
        return $pdf->download("report-{$type}-" . date('Ymd') . ".pdf");
    }

    public function exportExcel(Request $request)
    {
        $type = $request->get('type', 'service');
        $filters = $request->only(['start_date', 'end_date', 'technician_id', 'status', 'category_id', 'year', 'month']);

        $reportService = app(ReportService::class);
        $report = match ($type) {
            'sales' => $reportService->salesReport($filters),
            'stock' => $reportService->stockReport($filters),
            'financial' => $reportService->financialReport($filters),
            default => $reportService->serviceReport($filters),
        };

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        if ($type === 'service') {
            $sheet->setCellValue('A1', 'Date');
            $sheet->setCellValue('B1', 'Job No');
            $sheet->setCellValue('C1', 'Customer');
            $sheet->setCellValue('D1', 'Vehicle');
            $sheet->setCellValue('E1', 'Status');
            $sheet->setCellValue('F1', 'Revenue');
            $row = 2;
            foreach (($report['services'] ?? []) as $s) {
                $sheet->setCellValue("A{$row}", $s->service_date ?? '');
                $sheet->setCellValue("B{$row}", $s->job_no ?? '');
                $sheet->setCellValue("C{$row}", $s->customer->name ?? '');
                $sheet->setCellValue("D{$row}", $s->vehicle->number_plate ?? '');
                $sheet->setCellValue("E{$row}", $s->done_status ?? '');
                $sheet->setCellValue("F{$row}", $s->charge ?? 0);
                $row++;
            }
        } elseif ($type === 'sales') {
            $sheet->setCellValue('A1', 'Date');
            $sheet->setCellValue('B1', 'Sales No');
            $sheet->setCellValue('C1', 'Customer');
            $sheet->setCellValue('D1', 'Grand Total');
            $row = 2;
            foreach (($report['sales'] ?? []) as $s) {
                $sheet->setCellValue("A{$row}", $s->sales_date ?? '');
                $sheet->setCellValue("B{$row}", $s->sales_no ?? '');
                $sheet->setCellValue("C{$row}", $s->customer->name ?? '');
                $sheet->setCellValue("D{$row}", $s->grand_total ?? 0);
                $row++;
            }
        } elseif ($type === 'stock') {
            $sheet->setCellValue('A1', 'Product');
            $sheet->setCellValue('B1', 'Type');
            $sheet->setCellValue('C1', 'Current Stock');
            $sheet->setCellValue('D1', 'Unit Cost');
            $sheet->setCellValue('E1', 'Total Value');
            $row = 2;
            foreach (($report['products'] ?? []) as $p) {
                $sheet->setCellValue("A{$row}", $p->name ?? '');
                $sheet->setCellValue("B{$row}", $p->productType->name ?? '');
                $sheet->setCellValue("C{$row}", $p->current_stock ?? 0);
                $sheet->setCellValue("D{$row}", $p->cost_price ?? 0);
                $sheet->setCellValue("E{$row}", ($p->current_stock ?? 0) * ($p->cost_price ?? 0));
                $row++;
            }
        } elseif ($type === 'financial') {
            $sheet->setCellValue('A1', 'Month');
            $sheet->setCellValue('B1', 'Income');
            $sheet->setCellValue('C1', 'Expense');
            $sheet->setCellValue('D1', 'Profit/Loss');
            $row = 2;
            foreach (($report['monthly_breakdown'] ?? []) as $m) {
                $sheet->setCellValue("A{$row}", $m['month'] ?? '');
                $sheet->setCellValue("B{$row}", $m['income'] ?? 0);
                $sheet->setCellValue("C{$row}", $m['expense'] ?? 0);
                $sheet->setCellValue("D{$row}", $m['profit'] ?? 0);
                $row++;
            }
        }

        $writer = new Xlsx($spreadsheet);
        $filename = sys_get_temp_dir() . "/report-{$type}-" . date('Ymd') . ".xlsx";
        $writer->save($filename);

        return response()->download($filename)->deleteFileAfterSend();
    }

    public function technicianPerformance(Request $request, ReportService $reportService)
    {
        $filters = $request->only(['start_date', 'end_date']);
        $start = $filters['start_date'] ?? now()->startOfMonth()->toDateString();
        $end = $filters['end_date'] ?? now()->toDateString();

        $technicians = \App\Models\ServiceTechnician::join('services', 'service_technicians.service_id', '=', 'services.id')
            ->whereBetween('services.service_date', [$start, $end])
            ->selectRaw('service_technicians.user_id, COUNT(*) as job_count, SUM(services.charge) as total_revenue, AVG(TIMESTAMPDIFF(MINUTE, services.started_at, services.completed_at)) as avg_minutes')
            ->groupBy('service_technicians.user_id')
            ->with('user')
            ->get()
            ->map(function ($t) {
                $t->technician_name = $t->user->name ?? 'Unknown';
                $t->avg_duration = $t->avg_minutes ? round($t->avg_minutes / 60, 1) : null;
                return $t;
            });

        $topTechnician = $technicians->sortByDesc('total_revenue')->first();
        $totalJobs = $technicians->sum('job_count');

        return view('reports.technician', compact('technicians', 'topTechnician', 'totalJobs', 'start', 'end'));
    }

    public function customerLifetime(Request $request)
    {
        $topCustomers = \App\Models\Customer::withCount(['services'])
            ->withSum('services', 'charge')
            ->having('services_count', '>', 0)
            ->orderByDesc('services_sum_charge')
            ->limit(20)
            ->get()
            ->map(function ($c) {
                $c->last_service = $c->services()->latest('service_date')->first()?->service_date;
                $c->lifetime_value = $c->services_sum_charge ?? 0;
                $c->avg_per_visit = $c->services_count > 0 ? $c->lifetime_value / $c->services_count : 0;
                return $c;
            });
        return view('reports.customer-lifetime', compact('topCustomers'));
    }

    public function serviceReportPdf(Service $service)
    {
        $service->load([
            'vehicle.vehicleBrand', 'vehicle.vehicleType',
            'customer', 'repairCategory',
            'technicians', 'jobcardDetail',
            'invoice.items', 'serviceObservationPoints.observationPoint.observationType',
        ]);

        $settings = app(SettingsService::class)->getCompanyInfo();

        $pdf = Pdf::loadView('reports.service-pdf', compact('service', 'settings'));
        $pdf->setPaper('a4');

        return $pdf->download('laporan-service-' . $service->job_no . '.pdf');
    }
}
