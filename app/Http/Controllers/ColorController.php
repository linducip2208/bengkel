<?php

namespace App\Http\Controllers;

use App\Http\Requests\ColorRequest;
use App\Models\Color;

class ColorController extends Controller
{
    public function index()
    {
        $colors = Color::orderBy('name')->paginate(15);
        return view('colors.index', compact('colors'));
    }

    public function create()
    {
        return view('colors.create');
    }

    public function store(ColorRequest $request)
    {
        Color::create($request->validated());
        return redirect()->route('colors.index')->with('success', 'Warna berhasil ditambahkan.');
    }

    public function edit(Color $color)
    {
        return view('colors.edit', compact('color'));
    }

    public function update(ColorRequest $request, Color $color)
    {
        $color->update($request->validated());
        return redirect()->route('colors.index')->with('success', 'Warna berhasil diperbarui.');
    }

    public function destroy(Color $color)
    {
        $color->delete();
        return redirect()->route('colors.index')->with('success', 'Warna berhasil dihapus.');
    }
}
