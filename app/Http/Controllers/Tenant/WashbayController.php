<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Service;
use App\Models\Washbay;
use Illuminate\Http\Request;

class WashbayController extends Controller
{
    public function index(Request $request)
    {
        $query = Washbay::with(['branch', 'currentService.customer', 'currentService.vehicle']);
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        $washbays = $query->orderBy('branch_id')->orderBy('name')->get();
        $branches = Branch::orderBy('name')->get();

        return view('washbays.index', compact('washbays', 'branches'));
    }

    public function create(Request $request)
    {
        $branches = Branch::where('is_active', true)->orderBy('name')->get();
        $selectedBranchId = $request->get('branch_id');

        return view('washbays.create', compact('branches', 'selectedBranchId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'branch_id' => 'nullable|exists:branches,id',
            'status' => 'required|in:available,in_use,maintenance',
        ]);

        Washbay::create($validated);

        if (! empty($validated['branch_id'])) {
            return redirect()->route('branches.show', $validated['branch_id'])->with('success', 'Washbay ditambahkan.');
        }

        return redirect()->route('washbays.index')->with('success', 'Washbay ditambahkan.');
    }

    public function edit(Washbay $washbay)
    {
        $branches = Branch::where('is_active', true)->orderBy('name')->get();
        $activeServices = Service::whereIn('done_status', [0, 1])
            ->with(['customer', 'vehicle'])
            ->latest()
            ->limit(50)
            ->get();

        return view('washbays.edit', compact('washbay', 'branches', 'activeServices'));
    }

    public function update(Request $request, Washbay $washbay)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'branch_id' => 'nullable|exists:branches,id',
            'status' => 'required|in:available,in_use,maintenance',
            'current_service_id' => 'nullable|exists:services,id',
        ]);

        if ($validated['status'] !== 'in_use') {
            $validated['current_service_id'] = null;
        }

        $washbay->update($validated);

        return redirect()->route('washbays.index')->with('success', 'Washbay diperbarui.');
    }

    public function destroy(Washbay $washbay)
    {
        if ($washbay->status === 'in_use' && $washbay->current_service_id) {
            return back()->with('error', 'Washbay sedang dipakai. Selesaikan service dulu sebelum dihapus.');
        }
        $washbay->delete();

        return redirect()->route('washbays.index')->with('success', 'Washbay dihapus.');
    }

    public function occupy(Request $request, Washbay $washbay)
    {
        $validated = $request->validate([
            'service_id' => 'required|exists:services,id',
        ]);

        if ($washbay->status === 'in_use') {
            return back()->with('error', 'Washbay sudah dipakai.');
        }

        $washbay->update([
            'status' => 'in_use',
            'current_service_id' => $validated['service_id'],
        ]);

        return back()->with('success', 'Washbay sekarang dipakai untuk service.');
    }

    public function release(Washbay $washbay)
    {
        $washbay->update([
            'status' => 'available',
            'current_service_id' => null,
        ]);

        return back()->with('success', 'Washbay dikosongkan.');
    }
}
