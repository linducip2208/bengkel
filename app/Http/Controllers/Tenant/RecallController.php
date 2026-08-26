<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Product;
use App\Models\Recall;
use App\Models\VehicleBrand;
use Illuminate\Http\Request;

class RecallController extends Controller
{
    public function index(Request $request)
    {
        $query = Recall::with(['product', 'vehicleBrand']);
        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('title', 'like', "%{$s}%")
                    ->orWhere('description', 'like', "%{$s}%");
            });
        }
        $recalls = $query->latest()->paginate(20)->withQueryString();

        return view('recalls.index', compact('recalls'));
    }

    public function create()
    {
        $products = Product::orderBy('name')->get();
        $vehicleBrands = VehicleBrand::orderBy('vehicle_brand')->get();

        return view('recalls.create', compact('products', 'vehicleBrands'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'nullable|exists:products,id',
            'vehicle_brand_id' => 'nullable|exists:vehicle_brands,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:5000',
            'issue_date' => 'required|date',
            'severity' => 'required|in:low,medium,high,critical',
            'is_active' => 'boolean',
        ]);
        $validated['is_active'] = $request->boolean('is_active');

        $recall = Recall::create($validated);
        ActivityLog::record('recall.create', $recall, "Recall dibuat: {$recall->title}");

        return redirect()->route('recalls.index')->with('success', 'Recall berhasil dibuat.');
    }

    public function edit(Recall $recall)
    {
        $products = Product::orderBy('name')->get();
        $vehicleBrands = VehicleBrand::orderBy('vehicle_brand')->get();

        return view('recalls.edit', compact('recall', 'products', 'vehicleBrands'));
    }

    public function update(Request $request, Recall $recall)
    {
        $validated = $request->validate([
            'product_id' => 'nullable|exists:products,id',
            'vehicle_brand_id' => 'nullable|exists:vehicle_brands,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:5000',
            'issue_date' => 'required|date',
            'severity' => 'required|in:low,medium,high,critical',
            'is_active' => 'boolean',
        ]);
        $validated['is_active'] = $request->boolean('is_active');

        $recall->update($validated);
        ActivityLog::record('recall.update', $recall, "Recall diperbarui: {$recall->title}");

        return redirect()->route('recalls.index')->with('success', 'Recall berhasil diperbarui.');
    }

    public function destroy(Recall $recall)
    {
        ActivityLog::record('recall.delete', $recall, "Recall dihapus: {$recall->title}");
        $recall->delete();

        return back()->with('success', 'Recall berhasil dihapus.');
    }
}
