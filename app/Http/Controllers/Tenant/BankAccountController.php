<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\Branch;
use Illuminate\Http\Request;

class BankAccountController extends Controller
{
    public function index(Request $request)
    {
        $query = BankAccount::query()->with('branch');
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('bank_name', 'like', "%{$request->search}%")
                    ->orWhere('account_number', 'like', "%{$request->search}%")
                    ->orWhere('account_holder', 'like', "%{$request->search}%");
            });
        }
        $bankAccounts = $query->orderBy('bank_name')->orderBy('name')->paginate(15)->withQueryString();

        return view('bank-accounts.index', compact('bankAccounts'));
    }

    public function create()
    {
        $branches = Branch::orderBy('name')->get();

        return view('bank-accounts.create', compact('branches'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateData($request);

        $openingBalance = (float) ($validated['opening_balance'] ?? 0);

        BankAccount::create([
            'name' => $validated['name'],
            'bank_name' => $validated['bank_name'],
            'account_number' => $validated['account_number'],
            'account_holder' => $validated['account_holder'],
            'opening_balance' => $openingBalance,
            'current_balance' => $openingBalance,
            'branch_id' => $validated['branch_id'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('bank-accounts.index')->with('success', 'Rekening bank berhasil ditambahkan.');
    }

    public function edit(BankAccount $bankAccount)
    {
        $branches = Branch::orderBy('name')->get();

        return view('bank-accounts.edit', compact('bankAccount', 'branches'));
    }

    public function update(Request $request, BankAccount $bankAccount)
    {
        $validated = $this->validateData($request);

        $bankAccount->update([
            'name' => $validated['name'],
            'bank_name' => $validated['bank_name'],
            'account_number' => $validated['account_number'],
            'account_holder' => $validated['account_holder'],
            'opening_balance' => (float) ($validated['opening_balance'] ?? $bankAccount->opening_balance),
            'branch_id' => $validated['branch_id'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('bank-accounts.index')->with('success', 'Rekening bank berhasil diperbarui.');
    }

    public function destroy(BankAccount $bankAccount)
    {
        $bankAccount->delete();

        return redirect()->route('bank-accounts.index')->with('success', 'Rekening bank berhasil dihapus.');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'bank_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:50',
            'account_holder' => 'required|string|max:255',
            'opening_balance' => 'nullable|numeric|min:0',
            'branch_id' => 'nullable|exists:branches,id',
            'is_active' => 'nullable|boolean',
        ]);
    }
}
