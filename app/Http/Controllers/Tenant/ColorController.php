<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Color;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ColorController extends Controller
{
    public function index(Request $request)
    {
        $query = Color::query();
        if ($request->filled('search')) {
            $query->where('color', 'like', "%{$request->search}%");
        }
        $colors = $query->orderBy('color')->paginate(15)->withQueryString();

        return view('colors.index', compact('colors'));
    }

    public function create()
    {
        return view('colors.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('colors', 'color')->whereNull('deleted_at')],
            'hex_code' => 'nullable|string|max:7',
            'is_active' => 'nullable|boolean',
        ]);

        Color::create([
            'color' => $validated['name'],
            'hex_code' => $validated['hex_code'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('colors.index')->with('success', 'Warna berhasil ditambahkan.');
    }

    public function edit(Color $color)
    {
        return view('colors.edit', compact('color'));
    }

    public function update(Request $request, Color $color)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('colors', 'color')->whereNull('deleted_at')->ignore($color->id)],
            'hex_code' => 'nullable|string|max:7',
            'is_active' => 'nullable|boolean',
        ]);

        $color->update([
            'color' => $validated['name'],
            'hex_code' => $validated['hex_code'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('colors.index')->with('success', 'Warna berhasil diperbarui.');
    }

    public function destroy(Color $color)
    {
        $color->delete();

        return redirect()->route('colors.index')->with('success', 'Warna berhasil dihapus.');
    }
}
