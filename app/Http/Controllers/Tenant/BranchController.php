<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Company;
use App\Support\IdentityNormalizer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BranchController extends Controller
{
    public function index(Request $request)
    {
        $query = Branch::query();
        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%")
                ->orWhere('code', 'like', "%{$request->search}%");
        }
        $branches = $query->orderBy('name')->paginate(15)->withQueryString();

        return view('branches.index', compact('branches'));
    }

    public function create()
    {
        $companies = Company::orderBy('name')->get();

        return view('branches.create', compact('companies'));
    }

    public function store(Request $request)
    {
        $request->merge(['code' => IdentityNormalizer::branchCode($request->input('code'))]);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => ['required', 'string', 'max:20', Rule::unique('branches', 'code')],
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'company_id' => 'nullable|exists:companies,id',
            'is_active' => 'nullable|boolean',
        ]);

        Branch::create([
            'name' => $validated['name'],
            'code' => $validated['code'],
            'company_id' => $validated['company_id'] ?? null,
            'address' => $validated['address'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('branches.index')->with('success', 'Cabang berhasil ditambahkan.');
    }

    public function show(Branch $branch)
    {
        $branch->load(['businessHours', 'holidays', 'washbays']);

        return view('branches.show', compact('branch'));
    }

    public function edit(Branch $branch)
    {
        $companies = Company::orderBy('name')->get();

        return view('branches.edit', compact('branch', 'companies'));
    }

    public function update(Request $request, Branch $branch)
    {
        $request->merge(['code' => IdentityNormalizer::branchCode($request->input('code'))]);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => ['required', 'string', 'max:20', Rule::unique('branches', 'code')->ignore($branch->id)],
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'company_id' => 'nullable|exists:companies,id',
            'is_active' => 'nullable|boolean',
        ]);

        $branch->update([
            'name' => $validated['name'],
            'code' => $validated['code'],
            'company_id' => $validated['company_id'] ?? null,
            'address' => $validated['address'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('branches.index')->with('success', 'Cabang berhasil diperbarui.');
    }

    public function destroy(Branch $branch)
    {
        // FK integrity check harus lihat SEMUA data lintas cabang,
        // bukan terbatas branch aktif di session — bypass BranchScope.
        if ($branch->services()->withoutGlobalScopes()->exists()
            || $branch->customers()->withoutGlobalScopes()->exists()
            || $branch->vehicles()->withoutGlobalScopes()->exists()
            || $branch->invoices()->withoutGlobalScopes()->exists()
            || $branch->products()->withoutGlobalScopes()->exists()
            || $branch->sales()->withoutGlobalScopes()->exists()
            || $branch->purchases()->withoutGlobalScopes()->exists()
        ) {
            return back()->with('error', 'Cabang tidak bisa dihapus karena masih punya data operasional terdaftar.');
        }
        $branch->delete();

        return redirect()->route('branches.index')->with('success', 'Cabang berhasil dihapus.');
    }

    public function switchBranch(Request $request)
    {
        $branchId = $request->input('branch_id');
        if ($branchId === '' || $branchId === null) {
            $request->session()->forget('current_branch_id');
        } else {
            $branch = Branch::where('is_active', true)->findOrFail($branchId);

            // Users with explicit branch assignments may only switch to
            // branches they belong to (super_admin/admin unrestricted,
            // legacy accounts without assignments keep global access).
            if (! auth()->user()->hasBranchAccess((int) $branch->id)) {
                abort(403, 'Anda tidak terdaftar di cabang ini.');
            }

            $request->session()->put('current_branch_id', $branch->id);
        }

        return back()->with('success', 'Cabang aktif diubah.');
    }
}
