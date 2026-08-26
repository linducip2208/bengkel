<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Subcontractor;
use Illuminate\Http\Request;

class SubcontractorController extends Controller
{
    public function index(Request $request)
    {
        $query = Subcontractor::orderBy('name');
        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }
        $subcontractors = $query->paginate(15)->withQueryString();

        return view('subcontractors.index', compact('subcontractors'));
    }

    public function create()
    {
        return view('subcontractors.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
            'specialty' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        Subcontractor::create($validated);

        return redirect()->route('subcontractors.index')
            ->with('success', 'Subkontraktor berhasil ditambahkan.');
    }

    public function show(Subcontractor $subcontractor)
    {
        $subcontractor->load('jobs.service');

        return view('subcontractors.show', compact('subcontractor'));
    }

    public function edit(Subcontractor $subcontractor)
    {
        return view('subcontractors.edit', compact('subcontractor'));
    }

    public function update(Request $request, Subcontractor $subcontractor)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
            'specialty' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $subcontractor->update($validated);

        return redirect()->route('subcontractors.index')
            ->with('success', 'Subkontraktor berhasil diperbarui.');
    }

    public function destroy(Subcontractor $subcontractor)
    {
        $subcontractor->delete();

        return redirect()->route('subcontractors.index')
            ->with('success', 'Subkontraktor berhasil dihapus.');
    }
}
