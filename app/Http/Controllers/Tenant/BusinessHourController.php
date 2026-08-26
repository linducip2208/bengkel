<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\BusinessHour;
use Illuminate\Http\Request;

class BusinessHourController extends Controller
{
    public function create(Request $request)
    {
        $branches = Branch::where('is_active', true)->orderBy('name')->get();
        $selectedBranchId = $request->get('branch_id');

        return view('business-hours.create', compact('branches', 'selectedBranchId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'day_of_week' => 'required|integer|between:0,6',
            'open_time' => 'required|date_format:H:i',
            'close_time' => 'required|date_format:H:i|after:open_time',
            'is_closed' => 'nullable|boolean',
        ]);

        $exists = BusinessHour::where('branch_id', $validated['branch_id'])
            ->where('day_of_week', $validated['day_of_week'])
            ->exists();
        if ($exists) {
            return back()->withInput()->with('error', 'Jam operasional untuk hari tersebut sudah ada. Edit yang sudah ada.');
        }

        BusinessHour::create([
            'branch_id' => $validated['branch_id'],
            'day_of_week' => $validated['day_of_week'],
            'open_time' => $validated['open_time'],
            'close_time' => $validated['close_time'],
            'is_closed' => $request->boolean('is_closed'),
        ]);

        return redirect()->route('branches.show', $validated['branch_id'])->with('success', 'Jam operasional ditambahkan.');
    }

    public function edit(BusinessHour $businessHour)
    {
        $branches = Branch::where('is_active', true)->orderBy('name')->get();

        return view('business-hours.edit', compact('businessHour', 'branches'));
    }

    public function update(Request $request, BusinessHour $businessHour)
    {
        $validated = $request->validate([
            'day_of_week' => 'required|integer|between:0,6',
            'open_time' => 'required|date_format:H:i',
            'close_time' => 'required|date_format:H:i|after:open_time',
            'is_closed' => 'nullable|boolean',
        ]);

        $businessHour->update([
            'day_of_week' => $validated['day_of_week'],
            'open_time' => $validated['open_time'],
            'close_time' => $validated['close_time'],
            'is_closed' => $request->boolean('is_closed'),
        ]);

        return redirect()->route('branches.show', $businessHour->branch_id)->with('success', 'Jam operasional diperbarui.');
    }

    public function destroy(BusinessHour $businessHour)
    {
        $branchId = $businessHour->branch_id;
        $businessHour->delete();

        return redirect()->route('branches.show', $branchId)->with('success', 'Jam operasional dihapus.');
    }
}
