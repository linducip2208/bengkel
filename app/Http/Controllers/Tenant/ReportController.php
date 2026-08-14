<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
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
            'ar-aging' => $reportService->arAgingReport(),
            'parts-usage' => $reportService->partsUsageReport($filters),
            'branch-comparison' => $reportService->branchComparison($filters),
            'cash-flow' => $reportService->cashFlowReport($filters),
            'technician' => $this->technicianReportData($filters),
            'customer-lifetime' => $this->customerLifetimeReportData(),
            'general-ledger' => $this->generalLedgerReportData($filters),
            'profit-loss' => $this->profitLossReportData($filters),
            'balance-sheet' => $this->balanceSheetReportData($filters),
            default => $reportService->serviceReport($filters),
        };

        $pdf = Pdf::loadView("reports.pdf.{$type}", compact('report', 'filters'));
        $pdf->setPaper('a4');
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
            'ar-aging' => $reportService->arAgingReport(),
            'parts-usage' => $reportService->partsUsageReport($filters),
            'branch-comparison' => $reportService->branchComparison($filters),
            'cash-flow' => $reportService->cashFlowReport($filters),
            default => $reportService->serviceReport($filters),
        };

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $lastCol = 'F';

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
                $sheet->setCellValue("C{$row}", $s->customer?->name ?? '');
                $sheet->setCellValue("D{$row}", $s->vehicle?->number_plate ?? '');
                $sheet->setCellValue("E{$row}", $s->done_status ?? '');
                $sheet->setCellValue("F{$row}", $s->charge ?? 0);
                $row++;
            }
        } elseif ($type === 'sales') {
            $sheet->setCellValue('A1', 'Date');
            $sheet->setCellValue('B1', 'Invoice No');
            $sheet->setCellValue('C1', 'Customer');
            $sheet->setCellValue('D1', 'Grand Total');
            $row = 2;
            foreach (($report['sales'] ?? []) as $s) {
                $sheet->setCellValue("A{$row}", $s->invoice_date ?? '');
                $sheet->setCellValue("B{$row}", $s->invoice_number ?? '');
                $sheet->setCellValue("C{$row}", $s->customer?->name ?? '');
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
                $sheet->setCellValue("B{$row}", $p->productType?->name ?? '');
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
        } elseif ($type === 'technician') {
            $start = $filters['start_date'] ?? now()->startOfMonth()->toDateString();
            $end = $filters['end_date'] ?? now()->toDateString();

            $technicians = \App\Models\ServiceTechnician::join('services', 'service_technicians.service_id', '=', 'services.id')
                ->whereBetween('services.service_date', [$start, $end])
                ->selectRaw('service_technicians.user_id, COUNT(*) as job_count, SUM(services.charge) as total_revenue, AVG(TIMESTAMPDIFF(MINUTE, services.started_at, services.completed_at)) as avg_minutes')
                ->groupBy('service_technicians.user_id')
                ->with('user')
                ->get()
                ->map(function ($t) {
                    $t->technician_name = $t->user?->name ?? 'Unknown';
                    $t->avg_duration = $t->avg_minutes ? round($t->avg_minutes / 60, 1) : null;
                    return $t;
                });

            $sheet->setCellValue('A1', 'Name');
            $sheet->setCellValue('B1', 'Job Count');
            $sheet->setCellValue('C1', 'Total Revenue');
            $sheet->setCellValue('D1', 'Avg Duration (hrs)');
            $row = 2;
            foreach ($technicians as $t) {
                $sheet->setCellValue("A{$row}", $t->technician_name ?? '');
                $sheet->setCellValue("B{$row}", $t->job_count ?? 0);
                $sheet->setCellValue("C{$row}", $t->total_revenue ?? 0);
                $sheet->setCellValue("D{$row}", $t->avg_duration ?? '');
                $row++;
            }
            $lastCol = 'D';
        } elseif ($type === 'customer-lifetime') {
            $topCustomers = \App\Models\Customer::withCount(['services'])
                ->withSum('services', 'charge')
                ->having('services_count', '>', 0)
                ->orderByDesc('services_sum_charge')
                ->limit(20)
                ->get()
                ->map(function ($c) {
                    $c->lifetime_value = $c->services_sum_charge ?? 0;
                    $c->avg_per_visit = $c->services_count > 0 ? $c->lifetime_value / $c->services_count : 0;
                    return $c;
                });

            $sheet->setCellValue('A1', 'Customer');
            $sheet->setCellValue('B1', 'Services Count');
            $sheet->setCellValue('C1', 'Lifetime Value');
            $sheet->setCellValue('D1', 'Avg Per Visit');
            $row = 2;
            foreach ($topCustomers as $c) {
                $sheet->setCellValue("A{$row}", $c->name ?? '');
                $sheet->setCellValue("B{$row}", $c->services_count ?? 0);
                $sheet->setCellValue("C{$row}", $c->lifetime_value ?? 0);
                $sheet->setCellValue("D{$row}", $c->avg_per_visit ?? 0);
                $row++;
            }
            $lastCol = 'D';
        } elseif ($type === 'ar-aging') {
            $sheet->setCellValue('A1', 'Invoice Number');
            $sheet->setCellValue('B1', 'Customer');
            $sheet->setCellValue('C1', 'Days Overdue');
            $sheet->setCellValue('D1', 'Remaining');
            $sheet->setCellValue('E1', 'Age Group');
            $row = 2;
            foreach (($report['invoices'] ?? []) as $i) {
                $sheet->setCellValue("A{$row}", $i->invoice_number ?? '');
                $sheet->setCellValue("B{$row}", $i->customer?->name ?? '');
                $sheet->setCellValue("C{$row}", $i->days_overdue ?? 0);
                $sheet->setCellValue("D{$row}", $i->remaining ?? 0);
                $sheet->setCellValue("E{$row}", $i->age_group ?? '');
                $row++;
            }
            $lastCol = 'E';
        } elseif ($type === 'parts-usage') {
            $sheet->setCellValue('A1', 'Product');
            $sheet->setCellValue('B1', 'Category');
            $sheet->setCellValue('C1', 'Total Qty');
            $sheet->setCellValue('D1', 'Unit Cost');
            $sheet->setCellValue('E1', 'Total Cost');
            $row = 2;
            foreach (($report['usages'] ?? []) as $u) {
                $sheet->setCellValue("A{$row}", $u->product_name ?? '');
                $sheet->setCellValue("B{$row}", $u->category ?? '');
                $sheet->setCellValue("C{$row}", $u->total_qty ?? 0);
                $sheet->setCellValue("D{$row}", $u->unit_cost ?? 0);
                $sheet->setCellValue("E{$row}", $u->total_cost ?? 0);
                $row++;
            }
            $lastCol = 'E';
        } elseif ($type === 'branch-comparison') {
            $sheet->setCellValue('A1', 'Branch');
            $sheet->setCellValue('B1', 'Service Count');
            $sheet->setCellValue('C1', 'Service Revenue');
            $sheet->setCellValue('D1', 'POS Count');
            $sheet->setCellValue('E1', 'POS Revenue');
            $sheet->setCellValue('F1', 'Total Revenue');
            $row = 2;
            foreach (($report['branches'] ?? []) as $b) {
                $sheet->setCellValue("A{$row}", $b['name'] ?? '');
                $sheet->setCellValue("B{$row}", $b['service_count'] ?? 0);
                $sheet->setCellValue("C{$row}", $b['service_revenue'] ?? 0);
                $sheet->setCellValue("D{$row}", $b['pos_count'] ?? 0);
                $sheet->setCellValue("E{$row}", $b['pos_revenue'] ?? 0);
                $sheet->setCellValue("F{$row}", $b['total_revenue'] ?? 0);
                $row++;
            }
            $lastCol = 'F';
        } elseif ($type === 'cash-flow') {
            $sheet->setCellValue('A1', 'Date');
            $sheet->setCellValue('B1', 'Income');
            $sheet->setCellValue('C1', 'Expense');
            $sheet->setCellValue('D1', 'Net');
            $row = 2;
            foreach (($report['daily'] ?? []) as $d) {
                $sheet->setCellValue("A{$row}", $d['date'] ?? '');
                $sheet->setCellValue("B{$row}", $d['income'] ?? 0);
                $sheet->setCellValue("C{$row}", $d['expense'] ?? 0);
                $sheet->setCellValue("D{$row}", $d['net'] ?? 0);
                $row++;
            }
            $lastCol = 'D';
        } elseif ($type === 'general-ledger') {
            $start = $filters['start_date'] ?? now()->startOfMonth()->toDateString();
            $end = $filters['end_date'] ?? now()->toDateString();

            $entries = \App\Models\JournalEntry::with('lines.account')
                ->whereBetween('entry_date', [$start, $end])
                ->orderBy('entry_date')
                ->orderBy('id')
                ->get()
                ->map(function ($entry) {
                    $entry->total_debit = $entry->lines->sum('debit');
                    $entry->total_credit = $entry->lines->sum('credit');
                    return $entry;
                });

            $sheet->setCellValue('A1', 'Entry Number');
            $sheet->setCellValue('B1', 'Date');
            $sheet->setCellValue('C1', 'Description');
            $sheet->setCellValue('D1', 'Debit');
            $sheet->setCellValue('E1', 'Credit');
            $row = 2;
            foreach ($entries as $e) {
                $sheet->setCellValue("A{$row}", $e->entry_number ?? '');
                $sheet->setCellValue("B{$row}", $e->entry_date ?? '');
                $sheet->setCellValue("C{$row}", $e->description ?? '');
                $sheet->setCellValue("D{$row}", $e->total_debit ?? 0);
                $sheet->setCellValue("E{$row}", $e->total_credit ?? 0);
                $row++;
            }
            $lastCol = 'E';
        } elseif ($type === 'profit-loss') {
            $start = $filters['start_date'] ?? now()->startOfYear()->toDateString();
            $end = $filters['end_date'] ?? now()->toDateString();

            $revenueAccounts = \App\Models\ChartOfAccount::where('type', 'revenue')->where('is_active', true)->get();
            $cogsAccounts = \App\Models\ChartOfAccount::whereIn('name', ['Cost of Goods Sold', 'COGS'])
                ->orWhere('code', '5100')
                ->where('is_active', true)
                ->get();
            $expenseAccounts = \App\Models\ChartOfAccount::where('type', 'expense')->where('is_active', true)->get();

            $revenueAccounts->each(function ($a) use ($start, $end) {
                $a->balance = \App\Models\JournalEntryLine::where('chart_of_account_id', $a->id)
                    ->whereHas('journalEntry', fn($q) => $q->whereBetween('entry_date', [$start, $end]))
                    ->sum('credit');
            });
            $cogsAccounts->each(function ($a) use ($start, $end) {
                $a->balance = \App\Models\JournalEntryLine::where('chart_of_account_id', $a->id)
                    ->whereHas('journalEntry', fn($q) => $q->whereBetween('entry_date', [$start, $end]))
                    ->sum('debit');
            });
            $expenseAccounts->each(function ($a) use ($start, $end) {
                $a->balance = \App\Models\JournalEntryLine::where('chart_of_account_id', $a->id)
                    ->whereHas('journalEntry', fn($q) => $q->whereBetween('entry_date', [$start, $end]))
                    ->sum('debit');
            });

            $sheet->setCellValue('A1', 'Account');
            $sheet->setCellValue('B1', 'Amount');
            $row = 2;
            foreach ($revenueAccounts as $a) {
                $sheet->setCellValue("A{$row}", $a->name ?? '');
                $sheet->setCellValue("B{$row}", $a->balance ?? 0);
                $row++;
            }
            foreach ($cogsAccounts as $a) {
                $sheet->setCellValue("A{$row}", $a->name ?? '');
                $sheet->setCellValue("B{$row}", $a->balance ?? 0);
                $row++;
            }
            foreach ($expenseAccounts as $a) {
                $sheet->setCellValue("A{$row}", $a->name ?? '');
                $sheet->setCellValue("B{$row}", $a->balance ?? 0);
                $row++;
            }
            $lastCol = 'B';
        } elseif ($type === 'balance-sheet') {
            $endDate = $filters['end_date'] ?? now()->toDateString();

            $assetAccounts = \App\Models\ChartOfAccount::where('type', 'asset')->where('is_active', true)->get();
            $liabilityAccounts = \App\Models\ChartOfAccount::where('type', 'liability')->where('is_active', true)->get();
            $equityAccounts = \App\Models\ChartOfAccount::where('type', 'equity')->where('is_active', true)->get();

            $assetAccounts->each(function ($a) use ($endDate) {
                $debit = \App\Models\JournalEntryLine::where('chart_of_account_id', $a->id)
                    ->whereHas('journalEntry', fn($q) => $q->where('entry_date', '<=', $endDate))
                    ->sum('debit');
                $credit = \App\Models\JournalEntryLine::where('chart_of_account_id', $a->id)
                    ->whereHas('journalEntry', fn($q) => $q->where('entry_date', '<=', $endDate))
                    ->sum('credit');
                $a->balance = $debit - $credit;
            });
            $liabilityAccounts->each(function ($a) use ($endDate) {
                $debit = \App\Models\JournalEntryLine::where('chart_of_account_id', $a->id)
                    ->whereHas('journalEntry', fn($q) => $q->where('entry_date', '<=', $endDate))
                    ->sum('debit');
                $credit = \App\Models\JournalEntryLine::where('chart_of_account_id', $a->id)
                    ->whereHas('journalEntry', fn($q) => $q->where('entry_date', '<=', $endDate))
                    ->sum('credit');
                $a->balance = $credit - $debit;
            });
            $equityAccounts->each(function ($a) use ($endDate) {
                $credit = \App\Models\JournalEntryLine::where('chart_of_account_id', $a->id)
                    ->whereHas('journalEntry', fn($q) => $q->where('entry_date', '<=', $endDate))
                    ->sum('credit');
                $debit = \App\Models\JournalEntryLine::where('chart_of_account_id', $a->id)
                    ->whereHas('journalEntry', fn($q) => $q->where('entry_date', '<=', $endDate))
                    ->sum('debit');
                $a->balance = $credit - $debit;
            });

            $sheet->setCellValue('A1', 'Account');
            $sheet->setCellValue('B1', 'Amount');
            $row = 2;
            foreach ($assetAccounts as $a) {
                $sheet->setCellValue("A{$row}", $a->name ?? '');
                $sheet->setCellValue("B{$row}", $a->balance ?? 0);
                $row++;
            }
            foreach ($liabilityAccounts as $a) {
                $sheet->setCellValue("A{$row}", $a->name ?? '');
                $sheet->setCellValue("B{$row}", $a->balance ?? 0);
                $row++;
            }
            foreach ($equityAccounts as $a) {
                $sheet->setCellValue("A{$row}", $a->name ?? '');
                $sheet->setCellValue("B{$row}", $a->balance ?? 0);
                $row++;
            }
            $lastCol = 'B';
        }

        // Styling
        foreach (range('A', $lastCol) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $sheet->getStyle("A1:{$lastCol}1")->getFont()->setBold(true);

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
                $t->technician_name = $t->user?->name ?? 'Unknown';
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
            ->with(['services' => fn($q) => $q->latest('service_date')])
            ->having('services_count', '>', 0)
            ->orderByDesc('services_sum_charge')
            ->limit(20)
            ->get()
            ->map(function ($c) {
                $c->last_service = $c->services->first()?->service_date;
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

    public function arAging(ReportService $reportService)
    {
        $report = $reportService->arAgingReport();
        return view('reports.ar-aging', compact('report'));
    }

    public function partsUsage(Request $request, ReportService $reportService)
    {
        $filters = $request->only(['start_date', 'end_date']);
        $report = $reportService->partsUsageReport($filters);
        return view('reports.parts-usage', compact('report', 'filters'));
    }

    public function branchComparison(Request $request, ReportService $reportService)
    {
        $filters = $request->only(['start_date', 'end_date']);
        $report = $reportService->branchComparison($filters);
        return view('reports.branch-comparison', compact('report', 'filters'));
    }

    public function cashFlow(Request $request, ReportService $reportService)
    {
        $filters = $request->only(['start_date', 'end_date']);
        $report = $reportService->cashFlowReport($filters);
        return view('reports.cash-flow', compact('report', 'filters'));
    }

    public function generalLedger(Request $request)
    {
        $start = $request->get('start_date', now()->startOfMonth()->toDateString());
        $end = $request->get('end_date', now()->toDateString());
        $accountId = $request->get('account_id');

        $query = \App\Models\JournalEntry::with('lines.account')
            ->whereBetween('entry_date', [$start, $end])
            ->when($accountId, function ($q) use ($accountId) {
                $q->whereHas('lines', fn($l) => $l->where('chart_of_account_id', $accountId));
            })
            ->orderBy('entry_date')
            ->orderBy('id');

        $entries = $query->get()->map(function ($entry) {
            $entry->total_debit = $entry->lines->sum('debit');
            $entry->total_credit = $entry->lines->sum('credit');
            $entry->account_name = $entry->lines->first()?->account?->name;
            $entry->account_code = $entry->lines->first()?->account?->code;
            return $entry;
        });

        $totalDebit = $entries->sum('total_debit');
        $totalCredit = $entries->sum('total_credit');
        $totalEntries = $entries->count();

        $accounts = \App\Models\ChartOfAccount::where('is_active', true)->orderBy('code')->get();

        return view('reports.general-ledger', compact(
            'entries', 'totalDebit', 'totalCredit', 'totalEntries', 'accounts'
        ));
    }

    public function profitLoss(Request $request)
    {
        $start = $request->get('start_date', now()->startOfYear()->toDateString());
        $end = $request->get('end_date', now()->toDateString());

        $revenueAccounts = \App\Models\ChartOfAccount::where('type', 'revenue')->where('is_active', true)->get();
        $cogsAccounts = \App\Models\ChartOfAccount::whereIn('name', ['Cost of Goods Sold', 'COGS'])
            ->orWhere('code', '5100')
            ->where('is_active', true)
            ->get();
        $expenseAccounts = \App\Models\ChartOfAccount::where('type', 'expense')->where('is_active', true)->get();

        $revenueAccounts->each(function ($a) use ($start, $end) {
            $a->balance = \App\Models\JournalEntryLine::where('chart_of_account_id', $a->id)
                ->whereHas('journalEntry', fn($q) => $q->whereBetween('entry_date', [$start, $end]))
                ->sum('credit');
        });

        $cogsAccounts->each(function ($a) use ($start, $end) {
            $a->balance = \App\Models\JournalEntryLine::where('chart_of_account_id', $a->id)
                ->whereHas('journalEntry', fn($q) => $q->whereBetween('entry_date', [$start, $end]))
                ->sum('debit');
        });

        $expenseAccounts->each(function ($a) use ($start, $end) {
            $a->balance = \App\Models\JournalEntryLine::where('chart_of_account_id', $a->id)
                ->whereHas('journalEntry', fn($q) => $q->whereBetween('entry_date', [$start, $end]))
                ->sum('debit');
        });

        $totalRevenue = $revenueAccounts->sum('balance');
        $totalCogs = $cogsAccounts->sum('balance');
        $totalExpenses = $expenseAccounts->sum('balance');
        $grossProfit = $totalRevenue - $totalCogs;
        $netProfit = $grossProfit - $totalExpenses;

        return view('reports.profit-loss', compact(
            'revenueAccounts', 'cogsAccounts', 'expenseAccounts',
            'totalRevenue', 'totalCogs', 'totalExpenses', 'grossProfit', 'netProfit'
        ));
    }

    public function balanceSheet(Request $request)
    {
        $endDate = $request->get('end_date', now()->toDateString());

        $assetAccounts = \App\Models\ChartOfAccount::where('type', 'asset')->where('is_active', true)->get();
        $liabilityAccounts = \App\Models\ChartOfAccount::where('type', 'liability')->where('is_active', true)->get();
        $equityAccounts = \App\Models\ChartOfAccount::where('type', 'equity')->where('is_active', true)->get();

        $assetAccounts->each(function ($a) use ($endDate) {
            $debit = \App\Models\JournalEntryLine::where('chart_of_account_id', $a->id)
                ->whereHas('journalEntry', fn($q) => $q->where('entry_date', '<=', $endDate))
                ->sum('debit');
            $credit = \App\Models\JournalEntryLine::where('chart_of_account_id', $a->id)
                ->whereHas('journalEntry', fn($q) => $q->where('entry_date', '<=', $endDate))
                ->sum('credit');
            $a->balance = $debit - $credit;
        });

        $liabilityAccounts->each(function ($a) use ($endDate) {
            $debit = \App\Models\JournalEntryLine::where('chart_of_account_id', $a->id)
                ->whereHas('journalEntry', fn($q) => $q->where('entry_date', '<=', $endDate))
                ->sum('debit');
            $credit = \App\Models\JournalEntryLine::where('chart_of_account_id', $a->id)
                ->whereHas('journalEntry', fn($q) => $q->where('entry_date', '<=', $endDate))
                ->sum('credit');
            $a->balance = $credit - $debit;
        });

        $equityAccounts->each(function ($a) use ($endDate) {
            $credit = \App\Models\JournalEntryLine::where('chart_of_account_id', $a->id)
                ->whereHas('journalEntry', fn($q) => $q->where('entry_date', '<=', $endDate))
                ->sum('credit');
            $debit = \App\Models\JournalEntryLine::where('chart_of_account_id', $a->id)
                ->whereHas('journalEntry', fn($q) => $q->where('entry_date', '<=', $endDate))
                ->sum('debit');
            $a->balance = $credit - $debit;
        });

        $totalAssets = $assetAccounts->sum('balance');
        $totalLiabilities = $liabilityAccounts->sum('balance');
        $totalEquity = $equityAccounts->sum('balance');

        $startOfYear = now()->startOfYear()->toDateString();
        $pnlRevenue = \App\Models\JournalEntryLine::whereHas('account', fn($q) => $q->where('type', 'revenue'))
            ->whereHas('journalEntry', fn($q) => $q->whereBetween('entry_date', [$startOfYear, $endDate]))
            ->sum('credit');
        $pnlExpense = \App\Models\JournalEntryLine::whereHas('account', fn($q) => $q->whereIn('type', ['expense']))
            ->whereHas('journalEntry', fn($q) => $q->whereBetween('entry_date', [$startOfYear, $endDate]))
            ->sum('debit');
        $pnlCogs = \App\Models\JournalEntryLine::whereHas('account', fn($q) => $q->whereIn('name', ['Cost of Goods Sold', 'COGS'])->orWhere('code', '5100'))
            ->whereHas('journalEntry', fn($q) => $q->whereBetween('entry_date', [$startOfYear, $endDate]))
            ->sum('debit');
        $netProfit = $pnlRevenue - $pnlCogs - $pnlExpense;

        $balTotal = $totalLiabilities + $totalEquity + $netProfit;
        $difference = $totalAssets - $balTotal;
        $balanced = abs($difference) < 0.01;

        return view('reports.balance-sheet', compact(
            'assetAccounts', 'liabilityAccounts', 'equityAccounts',
            'totalAssets', 'totalLiabilities', 'totalEquity',
            'netProfit', 'difference', 'balanced'
        ));
    }

    private function technicianReportData(array $filters): array
    {
        $start = $filters['start_date'] ?? now()->startOfMonth()->toDateString();
        $end = $filters['end_date'] ?? now()->toDateString();

        $technicians = \App\Models\ServiceTechnician::join('services', 'service_technicians.service_id', '=', 'services.id')
            ->whereBetween('services.service_date', [$start, $end])
            ->selectRaw('service_technicians.user_id, COUNT(*) as job_count, SUM(services.charge) as total_revenue, AVG(TIMESTAMPDIFF(MINUTE, services.started_at, services.completed_at)) as avg_minutes')
            ->groupBy('service_technicians.user_id')
            ->with('user')
            ->get()
            ->map(function ($t) {
                $t->technician_name = $t->user?->name ?? 'Unknown';
                $t->avg_duration = $t->avg_minutes ? round($t->avg_minutes / 60, 1) : null;
                return $t;
            });

        return [
            'technicians' => $technicians,
            'total_jobs' => $technicians->sum('job_count'),
            'total_revenue' => $technicians->sum('total_revenue'),
        ];
    }

    private function customerLifetimeReportData(): array
    {
        $customers = \App\Models\Customer::withCount(['services'])
            ->withSum('services', 'charge')
            ->with(['services' => fn($q) => $q->latest('service_date')])
            ->having('services_count', '>', 0)
            ->orderByDesc('services_sum_charge')
            ->limit(20)
            ->get()
            ->map(function ($c) {
                $c->last_service = $c->services->first()?->service_date;
                $c->lifetime_value = $c->services_sum_charge ?? 0;
                $c->avg_per_visit = $c->services_count > 0 ? $c->lifetime_value / $c->services_count : 0;
                return $c;
            });

        return ['customers' => $customers];
    }

    private function generalLedgerReportData(array $filters): array
    {
        $start = $filters['start_date'] ?? now()->startOfMonth()->toDateString();
        $end = $filters['end_date'] ?? now()->toDateString();

        $entries = \App\Models\JournalEntry::with('lines.account')
            ->whereBetween('entry_date', [$start, $end])
            ->orderBy('entry_date')
            ->orderBy('id')
            ->get()
            ->map(function ($entry) {
                $entry->total_debit = $entry->lines->sum('debit');
                $entry->total_credit = $entry->lines->sum('credit');
                $entry->account_name = $entry->lines->first()?->account?->name;
                $entry->account_code = $entry->lines->first()?->account?->code;
                return $entry;
            });

        return [
            'entries' => $entries,
            'total_debit' => $entries->sum('total_debit'),
            'total_credit' => $entries->sum('total_credit'),
        ];
    }

    private function profitLossReportData(array $filters): array
    {
        $start = $filters['start_date'] ?? now()->startOfYear()->toDateString();
        $end = $filters['end_date'] ?? now()->toDateString();

        $revenueAccounts = \App\Models\ChartOfAccount::where('type', 'revenue')->where('is_active', true)->get();
        $cogsAccounts = \App\Models\ChartOfAccount::whereIn('name', ['Cost of Goods Sold', 'COGS'])
            ->orWhere('code', '5100')
            ->where('is_active', true)
            ->get();
        $expenseAccounts = \App\Models\ChartOfAccount::where('type', 'expense')->where('is_active', true)->get();

        $revenueAccounts->each(function ($a) use ($start, $end) {
            $a->balance = \App\Models\JournalEntryLine::where('chart_of_account_id', $a->id)
                ->whereHas('journalEntry', fn($q) => $q->whereBetween('entry_date', [$start, $end]))
                ->sum('credit');
        });
        $cogsAccounts->each(function ($a) use ($start, $end) {
            $a->balance = \App\Models\JournalEntryLine::where('chart_of_account_id', $a->id)
                ->whereHas('journalEntry', fn($q) => $q->whereBetween('entry_date', [$start, $end]))
                ->sum('debit');
        });
        $expenseAccounts->each(function ($a) use ($start, $end) {
            $a->balance = \App\Models\JournalEntryLine::where('chart_of_account_id', $a->id)
                ->whereHas('journalEntry', fn($q) => $q->whereBetween('entry_date', [$start, $end]))
                ->sum('debit');
        });

        $totalRevenue = $revenueAccounts->sum('balance');
        $totalCogs = $cogsAccounts->sum('balance');
        $totalExpenses = $expenseAccounts->sum('balance');

        return [
            'revenue_accounts' => $revenueAccounts,
            'cogs_accounts' => $cogsAccounts,
            'expense_accounts' => $expenseAccounts,
            'total_revenue' => $totalRevenue,
            'total_cogs' => $totalCogs,
            'total_expenses' => $totalExpenses,
            'gross_profit' => $totalRevenue - $totalCogs,
            'net_profit' => $totalRevenue - $totalCogs - $totalExpenses,
        ];
    }

    private function balanceSheetReportData(array $filters): array
    {
        $endDate = $filters['end_date'] ?? now()->toDateString();

        $assetAccounts = \App\Models\ChartOfAccount::where('type', 'asset')->where('is_active', true)->get();
        $liabilityAccounts = \App\Models\ChartOfAccount::where('type', 'liability')->where('is_active', true)->get();
        $equityAccounts = \App\Models\ChartOfAccount::where('type', 'equity')->where('is_active', true)->get();

        $assetAccounts->each(function ($a) use ($endDate) {
            $debit = \App\Models\JournalEntryLine::where('chart_of_account_id', $a->id)
                ->whereHas('journalEntry', fn($q) => $q->where('entry_date', '<=', $endDate))
                ->sum('debit');
            $credit = \App\Models\JournalEntryLine::where('chart_of_account_id', $a->id)
                ->whereHas('journalEntry', fn($q) => $q->where('entry_date', '<=', $endDate))
                ->sum('credit');
            $a->balance = $debit - $credit;
        });
        $liabilityAccounts->each(function ($a) use ($endDate) {
            $debit = \App\Models\JournalEntryLine::where('chart_of_account_id', $a->id)
                ->whereHas('journalEntry', fn($q) => $q->where('entry_date', '<=', $endDate))
                ->sum('debit');
            $credit = \App\Models\JournalEntryLine::where('chart_of_account_id', $a->id)
                ->whereHas('journalEntry', fn($q) => $q->where('entry_date', '<=', $endDate))
                ->sum('credit');
            $a->balance = $credit - $debit;
        });
        $equityAccounts->each(function ($a) use ($endDate) {
            $credit = \App\Models\JournalEntryLine::where('chart_of_account_id', $a->id)
                ->whereHas('journalEntry', fn($q) => $q->where('entry_date', '<=', $endDate))
                ->sum('credit');
            $debit = \App\Models\JournalEntryLine::where('chart_of_account_id', $a->id)
                ->whereHas('journalEntry', fn($q) => $q->where('entry_date', '<=', $endDate))
                ->sum('debit');
            $a->balance = $credit - $debit;
        });

        return [
            'asset_accounts' => $assetAccounts,
            'liability_accounts' => $liabilityAccounts,
            'equity_accounts' => $equityAccounts,
            'total_assets' => $assetAccounts->sum('balance'),
            'total_liabilities' => $liabilityAccounts->sum('balance'),
            'total_equity' => $equityAccounts->sum('balance'),
        ];
    }
}
