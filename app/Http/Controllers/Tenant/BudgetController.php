<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Budget;
use App\Models\Expense;
use App\Models\Income;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BudgetController extends Controller
{
    public function index(Request $request)
    {
        $period = $request->get('period', now()->format('Y-m'));

        $query = Budget::with('branch')->where('period', $period);
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }
        $budgets = $query->orderBy('category')->orderBy('branch_id')->get();

        $actualMap = [];
        foreach ($budgets as $budget) {
            $key = ($budget->branch_id ?? 'all') . '|' . $budget->category;
            if (! isset($actualMap[$key])) {
                $actualMap[$key] = $this->actualForPeriod($period, $budget->category, $budget->branch_id);
            }
        }

        $periods = Budget::query()->distinct()->orderByDesc('period')->pluck('period');
        $branches = Branch::orderBy('name')->get();

        return view('budgets.index', compact('budgets', 'period', 'periods', 'branches', 'actualMap'));
    }

    public function create()
    {
        $branches = Branch::orderBy('name')->get();

        return view('budgets.create', compact('branches'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'category' => ['required', Rule::in(['revenue', 'expense'])],
            'period' => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:5000',
        ]);

        $validated['amount'] = (float) $validated['amount'];

        try {
            Budget::create($validated);
        } catch (\Illuminate\Database\QueryException $e) {
            return back()->withInput()->with('error', 'Budget untuk cabang, kategori, dan periode ini sudah ada.');
        }

        return redirect()->route('budgets.index', ['period' => $validated['period']])
            ->with('success', 'Budget berhasil ditambahkan.');
    }

    public function edit(Budget $budget)
    {
        $branches = Branch::orderBy('name')->get();

        return view('budgets.edit', compact('budget', 'branches'));
    }

    public function update(Request $request, Budget $budget)
    {
        $validated = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'category' => ['required', Rule::in(['revenue', 'expense'])],
            'period' => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:5000',
        ]);

        $validated['amount'] = (float) $validated['amount'];

        try {
            $budget->update($validated);
        } catch (\Illuminate\Database\QueryException $e) {
            return back()->withInput()->with('error', 'Budget untuk cabang, kategori, dan periode ini sudah ada.');
        }

        return redirect()->route('budgets.index', ['period' => $validated['period']])
            ->with('success', 'Budget berhasil diperbarui.');
    }

    public function destroy(Budget $budget)
    {
        $budget->delete();

        return redirect()->route('budgets.index')->with('success', 'Budget berhasil dihapus.');
    }

    private function actualForPeriod(string $period, string $category, ?int $branchId = null): float
    {
        [$year, $month] = array_pad(explode('-', $period), 2, null);
        if (! $year || ! $month) {
            return 0;
        }

        $start = \Carbon\Carbon::createFromDate((int) $year, (int) $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        if ($category === 'revenue') {
            $query = Income::query()->withoutBranchScope()->whereBetween('income_date', [$start, $end]);
        } else {
            $query = Expense::query()->withoutBranchScope()->whereBetween('expense_date', [$start, $end]);
        }

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return (float) $query->sum('amount');
    }
}
