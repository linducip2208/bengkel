<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Holiday;
use Illuminate\Http\Request;

class HolidayController extends Controller
{
    public function index(Request $request)
    {
        $query = Holiday::with('branch');
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($request->filled('year')) {
            $query->whereYear('date', $request->year);
        }
        $holidays = $query->orderBy('date', 'desc')->paginate(20)->withQueryString();
        $branches = Branch::orderBy('name')->get();

        return view('holidays.index', compact('holidays', 'branches'));
    }

    public function create(Request $request)
    {
        $branches = Branch::where('is_active', true)->orderBy('name')->get();
        $selectedBranchId = $request->get('branch_id');

        return view('holidays.create', compact('branches', 'selectedBranchId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'name' => 'required|string|max:255',
            'date' => 'required|date',
            'is_recurring' => 'nullable|boolean',
        ]);

        Holiday::create([
            'branch_id' => $validated['branch_id'] ?? null,
            'name' => $validated['name'],
            'date' => $validated['date'],
            'is_recurring' => $request->boolean('is_recurring'),
        ]);

        if (! empty($validated['branch_id'])) {
            return redirect()->route('branches.show', $validated['branch_id'])->with('success', 'Hari libur ditambahkan.');
        }

        return redirect()->route('holidays.index')->with('success', 'Hari libur ditambahkan.');
    }

    public function edit(Holiday $holiday)
    {
        $branches = Branch::where('is_active', true)->orderBy('name')->get();

        return view('holidays.edit', compact('holiday', 'branches'));
    }

    public function update(Request $request, Holiday $holiday)
    {
        $validated = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'name' => 'required|string|max:255',
            'date' => 'required|date',
            'is_recurring' => 'nullable|boolean',
        ]);

        $holiday->update([
            'branch_id' => $validated['branch_id'] ?? null,
            'name' => $validated['name'],
            'date' => $validated['date'],
            'is_recurring' => $request->boolean('is_recurring'),
        ]);

        return redirect()->route('holidays.index')->with('success', 'Hari libur diperbarui.');
    }

    public function destroy(Holiday $holiday)
    {
        $holiday->delete();

        return redirect()->route('holidays.index')->with('success', 'Hari libur dihapus.');
    }
}
