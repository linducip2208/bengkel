<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Attendance;
use App\Models\Salary;
use App\Models\ServiceTechnician;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HrmController extends Controller
{
    // === ATTENDANCE ===
    public function attendanceIndex(Request $request)
    {
        $month = $request->month ?? now()->format('Y-m');
        [$year, $monthNum] = explode('-', $month);

        $users = User::orderBy('name')->get();
        $attendances = Attendance::whereYear('date', $year)->whereMonth('date', $monthNum)
            ->get()->groupBy('user_id');

        return view('hrm.attendance', compact('users', 'attendances', 'month', 'year', 'monthNum'));
    }

    public function clockIn(Request $request)
    {
        $today = today();
        $existing = Attendance::where('user_id', auth()->id())->where('date', $today)->first();
        if ($existing && $existing->clock_in) return back()->with('error', 'Sudah clock-in hari ini.');

        $clockIn = now()->format('H:i:s');
        $isLate = now()->hour >= 9; // > jam 9 = late

        Attendance::updateOrCreate(
            ['user_id' => auth()->id(), 'date' => $today],
            [
                'branch_id' => session('current_branch_id'),
                'clock_in' => $clockIn,
                'clock_in_location' => $request->input('location'),
                'status' => $isLate ? 'late' : 'present',
            ]
        );
        ActivityLog::record('attendance.clock_in', null, auth()->user()->name . ' clock-in jam ' . $clockIn);
        return back()->with('success', 'Clock-in tercatat: ' . $clockIn);
    }

    public function clockOut(Request $request)
    {
        $today = today();
        $att = Attendance::where('user_id', auth()->id())->where('date', $today)->first();
        if (!$att || !$att->clock_in) return back()->with('error', 'Belum clock-in.');
        if ($att->clock_out) return back()->with('error', 'Sudah clock-out.');

        $att->update(['clock_out' => now()->format('H:i:s'), 'clock_out_location' => $request->input('location')]);
        ActivityLog::record('attendance.clock_out', null, auth()->user()->name . ' clock-out');
        return back()->with('success', 'Clock-out tercatat.');
    }

    // === SALARY ===
    public function salaryIndex(Request $request)
    {
        $year = $request->year ?? now()->year;
        $month = $request->month ?? now()->month;

        $salaries = Salary::with('user')->where('period_year', $year)->where('period_month', $month)->get();
        $users = User::orderBy('name')->get();

        return view('hrm.salary', compact('salaries', 'users', 'year', 'month'));
    }

    public function salaryGenerate(Request $request)
    {
        $validated = $request->validate([
            'period_year' => 'required|integer|min:2020|max:2100',
            'period_month' => 'required|integer|between:1,12',
        ]);

        $users = User::where('base_salary', '>', 0)->get();
        DB::transaction(function () use ($users, $validated) {
            foreach ($users as $u) {
                $daysPresent = Attendance::where('user_id', $u->id)
                    ->whereYear('date', $validated['period_year'])
                    ->whereMonth('date', $validated['period_month'])
                    ->whereIn('status', ['present', 'late'])->count();
                $daysAbsent = Attendance::where('user_id', $u->id)
                    ->whereYear('date', $validated['period_year'])
                    ->whereMonth('date', $validated['period_month'])
                    ->where('status', 'absent')->count();

                $commission = ServiceTechnician::where('user_id', $u->id)
                    ->whereHas('service', fn($q) => $q->whereYear('service_date', $validated['period_year'])->whereMonth('service_date', $validated['period_month']))
                    ->sum('commission_amt') ?? 0;

                $base = (float) $u->base_salary;
                $net = $base + $commission;

                Salary::updateOrCreate(
                    ['user_id' => $u->id, 'period_year' => $validated['period_year'], 'period_month' => $validated['period_month']],
                    [
                        'base_salary' => $base,
                        'commission_total' => $commission,
                        'allowance' => 0,
                        'deduction' => 0,
                        'net_salary' => $net,
                        'days_present' => $daysPresent,
                        'days_absent' => $daysAbsent,
                        'status' => 'draft',
                    ]
                );
            }
        });

        ActivityLog::record('salary.generate', null, "Generate gaji periode {$validated['period_month']}/{$validated['period_year']}");
        return back()->with('success', "Gaji generated untuk {$users->count()} karyawan.");
    }

    public function salaryMarkPaid(Salary $salary)
    {
        $salary->update(['status' => 'paid', 'paid_at' => now()]);
        ActivityLog::record('salary.paid', $salary, "Gaji dibayar untuk user {$salary->user->name}");
        return back()->with('success', 'Gaji ditandai sudah dibayar.');
    }

    public function salarySlip(Salary $salary)
    {
        $salary->load('user');
        $pdf = Pdf::loadView('hrm.salary-slip', compact('salary'));
        return $pdf->download("slip-gaji-{$salary->user->name}-{$salary->period_month}-{$salary->period_year}.pdf");
    }
}
