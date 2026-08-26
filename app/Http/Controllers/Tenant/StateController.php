<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\State;
use Illuminate\Http\Request;

class StateController extends Controller
{
    public function index(Request $request)
    {
        $query = State::with('country');
        if ($request->filled('country_id')) {
            $query->where('country_id', $request->country_id);
        }
        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }
        $states = $query->orderBy('name')->paginate(20)->withQueryString();
        $countries = Country::orderBy('name')->get();

        return view('states.index', compact('states', 'countries'));
    }

    public function create()
    {
        $countries = Country::orderBy('name')->get();

        return view('states.create', compact('countries'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'country_id' => 'required|exists:countries,id',
            'name' => 'required|string|max:255',
        ]);
        State::create($validated);

        return redirect()->route('states.index')->with('success', 'Provinsi ditambahkan.');
    }

    public function edit(State $state)
    {
        $countries = Country::orderBy('name')->get();

        return view('states.edit', compact('state', 'countries'));
    }

    public function update(Request $request, State $state)
    {
        $validated = $request->validate([
            'country_id' => 'required|exists:countries,id',
            'name' => 'required|string|max:255',
        ]);
        $state->update($validated);

        return redirect()->route('states.index')->with('success', 'Provinsi diperbarui.');
    }

    public function destroy(State $state)
    {
        if ($state->cities()->exists()) {
            return back()->with('error', 'Provinsi tidak bisa dihapus karena masih punya kota terdaftar.');
        }
        $state->delete();

        return redirect()->route('states.index')->with('success', 'Provinsi dihapus.');
    }
}
