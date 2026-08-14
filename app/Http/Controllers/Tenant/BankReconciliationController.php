<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\BankReconciliation;
use App\Models\Expense;
use App\Models\Income;
use Illuminate\Http\Request;

class BankReconciliationController extends Controller
{
    public function index(Request $request)
    {
        $query = BankReconciliation::with('bankAccount');
        if ($request->filled('bank_account_id')) {
            $query->where('bank_account_id', $request->bank_account_id);
        }
        $reconciliations = $query->latest()->paginate(20)->withQueryString();
        $bankAccounts = BankAccount::orderBy('name')->get();

        return view('bank-reconciliations.index', compact('reconciliations', 'bankAccounts'));
    }

    public function create()
    {
        $bankAccounts = BankAccount::orderBy('name')->get();

        return view('bank-reconciliations.create', compact('bankAccounts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'statement_balance' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:5000',
        ]);

        $bankAccount = BankAccount::findOrFail($validated['bank_account_id']);
        $startDate = $validated['start_date'];
        $endDate = $validated['end_date'];

        $openingBalance = (float) $bankAccount->opening_balance;

        $income = (float) Income::query()
            ->whereBetween('income_date', [$startDate, $endDate])
            ->sum('amount');

        $expense = (float) Expense::query()
            ->whereBetween('expense_date', [$startDate, $endDate])
            ->sum('amount');

        $closingBalance = $openingBalance + $income - $expense;
        $statementBalance = (float) $validated['statement_balance'];
        $difference = $statementBalance - $closingBalance;

        $reconciliation = BankReconciliation::create([
            'bank_account_id' => $bankAccount->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'opening_balance' => $openingBalance,
            'closing_balance' => $closingBalance,
            'statement_balance' => $statementBalance,
            'difference' => $difference,
            'status' => 'draft',
            'notes' => $validated['notes'] ?? null,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('bank-reconciliations.show', $reconciliation)
            ->with('success', 'Rekonsiliasi bank berhasil dibuat.');
    }

    public function show(BankReconciliation $bankReconciliation)
    {
        $bankReconciliation->load(['bankAccount', 'creator']);

        $income = (float) Income::query()
            ->whereBetween('income_date', [$bankReconciliation->start_date->toDateString(), $bankReconciliation->end_date->toDateString()])
            ->sum('amount');

        $expense = (float) Expense::query()
            ->whereBetween('expense_date', [$bankReconciliation->start_date->toDateString(), $bankReconciliation->end_date->toDateString()])
            ->sum('amount');

        return view('bank-reconciliations.show', compact('bankReconciliation', 'income', 'expense'));
    }
}
