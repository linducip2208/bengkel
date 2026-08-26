<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Note;
use App\Models\Service;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NoteController extends Controller
{
    private const NOTABLE_MAP = [
        'customer' => Customer::class,
        'vehicle' => Vehicle::class,
        'service' => Service::class,
        'invoice' => Invoice::class,
    ];

    public function index(Request $request)
    {
        $query = Note::with(['notable', 'author']);
        if ($request->filled('notable_type')) {
            $cls = self::NOTABLE_MAP[$request->notable_type] ?? null;
            if ($cls) {
                $query->where('notable_type', $cls);
            }
        }
        $notes = $query->latest()->paginate(20)->withQueryString();

        return view('notes.index', [
            'notes' => $notes,
            'notableTypes' => array_keys(self::NOTABLE_MAP),
        ]);
    }

    public function create(Request $request)
    {
        return view('notes.create', [
            'notableTypes' => array_keys(self::NOTABLE_MAP),
            'selectedType' => $request->get('type'),
            'selectedId' => $request->get('id'),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'notable_type' => ['required', Rule::in(array_keys(self::NOTABLE_MAP))],
            'notable_id' => 'required|integer',
            'content' => 'required|string',
        ]);

        $cls = self::NOTABLE_MAP[$validated['notable_type']];
        if (! $cls::find($validated['notable_id'])) {
            return back()->withInput()->with('error', 'Target entity tidak ditemukan.');
        }

        Note::create([
            'notable_type' => $cls,
            'notable_id' => $validated['notable_id'],
            'content' => $validated['content'],
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('notes.index')->with('success', 'Catatan ditambahkan.');
    }

    public function destroy(Note $note)
    {
        $note->delete();

        return redirect()->route('notes.index')->with('success', 'Catatan dihapus.');
    }
}
