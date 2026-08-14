<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExpenseRequest;
use App\Models\BankAccount;
use App\Models\Expense;
use App\Services\ExpenseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    public function __construct(
        protected ExpenseService $expenseService
    ) {}

    public function index(Request $request): View
    {
        $expenses = Expense::query()
            ->when($request->date_from, fn($q) => $q->whereDate('expense_date', '>=', $request->date_from))
            ->when($request->date_to, fn($q) => $q->whereDate('expense_date', '<=', $request->date_to))
            ->when($request->search, fn($q) => $q->where('label', 'like', "%{$request->search}%"))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $totalAmount = Expense::query()
            ->when($request->date_from, fn($q) => $q->whereDate('expense_date', '>=', $request->date_from))
            ->when($request->date_to, fn($q) => $q->whereDate('expense_date', '<=', $request->date_to))
            ->when($request->search, fn($q) => $q->where('label', 'like', "%{$request->search}%"))
            ->sum('amount');

        return view('expenses.index', compact('expenses', 'totalAmount'));
    }

    public function create(): View
    {
        $bankAccounts = BankAccount::where('is_active', true)->orderBy('name')->get();

        return view('expenses.create', compact('bankAccounts'));
    }

    public function store(ExpenseRequest $request): RedirectResponse
    {
        $this->expenseService->create($request->validated());

        return redirect()->route('expenses.index')
            ->with('success', 'Pengeluaran berhasil dicatat.');
    }

    public function show(Expense $expense): View
    {
        return view('expenses.show', compact('expense'));
    }

    public function edit(Expense $expense): View
    {
        $bankAccounts = BankAccount::where('is_active', true)->orderBy('name')->get();

        return view('expenses.edit', compact('expense', 'bankAccounts'));
    }

    public function update(ExpenseRequest $request, Expense $expense): RedirectResponse
    {
        $expense->update($request->validated());

        return redirect()->route('expenses.index')
            ->with('success', 'Pengeluaran berhasil diperbarui.');
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        $expense->delete();

        return redirect()->route('expenses.index')
            ->with('success', 'Pengeluaran berhasil dihapus.');
    }
}
