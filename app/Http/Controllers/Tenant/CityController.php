<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\State;
use Illuminate\Http\Request;

class CityController extends Controller
{
    public function index(Request $request)
    {
        $query = City::with('state.country');
        if ($request->filled('state_id')) {
            $query->where('state_id', $request->state_id);
        }
        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }
        $cities = $query->orderBy('name')->paginate(20)->withQueryString();
        $states = State::orderBy('name')->get();

        return view('cities.index', compact('cities', 'states'));
    }

    public function create()
    {
        $states = State::orderBy('name')->get();

        return view('cities.create', compact('states'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'state_id' => 'required|exists:states,id',
            'name' => 'required|string|max:255',
        ]);
        City::create($validated);

        return redirect()->route('cities.index')->with('success', 'Kota ditambahkan.');
    }

    public function edit(City $city)
    {
        $states = State::orderBy('name')->get();

        return view('cities.edit', compact('city', 'states'));
    }

    public function update(Request $request, City $city)
    {
        $validated = $request->validate([
            'state_id' => 'required|exists:states,id',
            'name' => 'required|string|max:255',
        ]);
        $city->update($validated);

        return redirect()->route('cities.index')->with('success', 'Kota diperbarui.');
    }

    public function destroy(City $city)
    {
        $city->delete();

        return redirect()->route('cities.index')->with('success', 'Kota dihapus.');
    }
}
