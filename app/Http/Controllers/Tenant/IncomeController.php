<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\IncomeRequest;
use App\Models\BankAccount;
use App\Models\Customer;
use App\Models\Income;
use App\Models\PaymentMethod;
use App\Services\IncomeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IncomeController extends Controller
{
    public function __construct(
        protected IncomeService $incomeService
    ) {}

    public function index(Request $request): View
    {
        $incomes = Income::query()
            ->with(['customer', 'paymentMethod'])
            ->when($request->date_from, fn($q) => $q->whereDate('income_date', '>=', $request->date_from))
            ->when($request->date_to, fn($q) => $q->whereDate('income_date', '<=', $request->date_to))
            ->when($request->payment_method_id, fn($q) => $q->where('payment_method_id', $request->payment_method_id))
            ->when($request->search, fn($q) => $q->where(function ($q) use ($request) {
                $q->where('label', 'like', "%{$request->search}%")
                  ->orWhere('invoice_number', 'like', "%{$request->search}%");
            }))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $paymentMethods = PaymentMethod::where('is_active', true)->get();

        $totalAmount = Income::query()
            ->when($request->date_from, fn($q) => $q->whereDate('income_date', '>=', $request->date_from))
            ->when($request->date_to, fn($q) => $q->whereDate('income_date', '<=', $request->date_to))
            ->when($request->payment_method_id, fn($q) => $q->where('payment_method_id', $request->payment_method_id))
            ->when($request->search, fn($q) => $q->where(function ($q) use ($request) {
                $q->where('label', 'like', "%{$request->search}%")
                  ->orWhere('invoice_number', 'like', "%{$request->search}%");
            }))
            ->sum('amount');

        return view('incomes.index', compact('incomes', 'paymentMethods', 'totalAmount'));
    }

    public function create(): View
    {
        $customers = Customer::orderBy('name')->get();
        $paymentMethods = PaymentMethod::where('is_active', true)->get();
        $bankAccounts = BankAccount::where('is_active', true)->orderBy('name')->get();

        return view('incomes.create', compact('customers', 'paymentMethods', 'bankAccounts'));
    }

    public function store(IncomeRequest $request): RedirectResponse
    {
        $this->incomeService->create($request->validated());

        return redirect()->route('incomes.index')
            ->with('success', 'Pemasukan berhasil dicatat.');
    }

    public function show(Income $income): View
    {
        return view('incomes.show', compact('income'));
    }

    public function edit(Income $income): View
    {
        $customers = Customer::orderBy('name')->get();
        $paymentMethods = PaymentMethod::where('is_active', true)->get();
        $bankAccounts = BankAccount::where('is_active', true)->orderBy('name')->get();

        return view('incomes.edit', compact('income', 'customers', 'paymentMethods', 'bankAccounts'));
    }

    public function update(IncomeRequest $request, Income $income): RedirectResponse
    {
        $this->incomeService->update($income, $request->validated());

        return redirect()->route('incomes.index')
            ->with('success', 'Pemasukan berhasil diperbarui.');
    }

    public function destroy(Income $income): RedirectResponse
    {
        $income->delete();

        return redirect()->route('incomes.index')
            ->with('success', 'Pemasukan berhasil dihapus.');
    }
}
