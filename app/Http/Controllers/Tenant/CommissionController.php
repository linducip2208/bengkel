<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\ServiceTechnician;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CommissionController extends Controller
{
    public function index(Request $request)
    {
        $query = ServiceTechnician::with(['service.customer', 'service.vehicle', 'user'])
            ->whereHas('service', fn($q) => $q->where('done_status', 2));

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('status')) {
            if ($request->status === 'unpaid') {
                $query->whereNull('paid_at');
            } elseif ($request->status === 'paid') {
                $query->whereNotNull('paid_at');
            }
        }
        if ($request->filled('date_from')) {
            $query->whereHas('service', fn($q) => $q->whereDate('service_date', '>=', $request->date_from));
        }
        if ($request->filled('date_to')) {
            $query->whereHas('service', fn($q) => $q->whereDate('service_date', '<=', $request->date_to));
        }

        $rows = $query->latest()->paginate(30)->withQueryString();

        $summary = [
            'total_unpaid' => ServiceTechnician::unpaid()
                ->whereHas('service', fn($q) => $q->where('done_status', 2))
                ->sum('commission_amt') ?? 0,
            'total_paid_month' => ServiceTechnician::paid()
                ->whereMonth('paid_at', now()->month)
                ->whereYear('paid_at', now()->year)
                ->sum('commission_amt') ?? 0,
            'pending_count' => ServiceTechnician::unpaid()
                ->whereHas('service', fn($q) => $q->where('done_status', 2))
                ->count(),
        ];

        $technicians = User::orderBy('name')->get();

        return view('commissions.index', compact('rows', 'summary', 'technicians'));
    }

    public function markPaid(Request $request, ServiceTechnician $serviceTechnician)
    {
        if ($serviceTechnician->paid_at) {
            return back()->with('error', 'Komisi ini sudah dibayar.');
        }
        $serviceTechnician->update([
            'paid_at' => now(),
            'paid_by' => auth()->id(),
            'notes' => $request->input('notes', $serviceTechnician->notes),
        ]);
        return back()->with('success', 'Komisi ditandai sudah dibayar.');
    }

    public function markPaidBatch(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:service_technicians,id',
        ]);

        $count = ServiceTechnician::whereIn('id', $validated['ids'])
            ->whereNull('paid_at')
            ->update([
                'paid_at' => now(),
                'paid_by' => auth()->id(),
            ]);

        return back()->with('success', "{$count} komisi ditandai sudah dibayar.");
    }

    public function report(Request $request)
    {
        $from = $request->date_from ?? now()->startOfMonth()->toDateString();
        $to = $request->date_to ?? now()->endOfMonth()->toDateString();

        $rows = DB::table('service_technicians')
            ->join('users', 'service_technicians.user_id', '=', 'users.id')
            ->join('services', 'service_technicians.service_id', '=', 'services.id')
            ->whereBetween('services.service_date', [$from, $to . ' 23:59:59'])
            ->where('services.done_status', 2)
            ->whereNull('service_technicians.deleted_at')
            ->select(
                'users.id as user_id',
                'users.name as technician',
                DB::raw('COUNT(service_technicians.id) as service_count'),
                DB::raw('SUM(service_technicians.commission_amt) as total_commission'),
                DB::raw('SUM(CASE WHEN service_technicians.paid_at IS NULL THEN service_technicians.commission_amt ELSE 0 END) as unpaid')
            )
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total_commission')
            ->get();

        return view('commissions.report', compact('rows', 'from', 'to'));
    }
}
