<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\CustomField;
use Illuminate\Http\Request;

class CustomFieldController extends Controller
{
    private const MODULES = ['customer', 'vehicle', 'service', 'invoice', 'product', 'supplier'];

    private const TYPES = ['text', 'number', 'date', 'select', 'boolean', 'textarea'];

    public function index(Request $request)
    {
        $query = CustomField::query();
        if ($request->filled('module')) {
            $query->where('module', $request->module);
        }
        $fields = $query->orderBy('module')->orderBy('sort_order')->paginate(20)->withQueryString();

        return view('custom-fields.index', [
            'fields' => $fields,
            'modules' => self::MODULES,
        ]);
    }

    public function create()
    {
        return view('custom-fields.create', [
            'modules' => self::MODULES,
            'types' => self::TYPES,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'module' => 'required|string|in:'.implode(',', self::MODULES),
            'field_name' => 'required|string|max:100',
            'field_type' => 'required|in:'.implode(',', self::TYPES),
            'options' => 'nullable|string',
            'is_required' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $options = null;
        if (in_array($validated['field_type'], ['select']) && ! empty($validated['options'])) {
            $options = array_values(array_filter(array_map('trim', explode("\n", $validated['options']))));
        }

        CustomField::create([
            'module' => $validated['module'],
            'field_name' => $validated['field_name'],
            'field_type' => $validated['field_type'],
            'options' => $options,
            'is_required' => $request->boolean('is_required'),
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('custom-fields.index')->with('success', 'Custom field ditambahkan.');
    }

    public function edit(CustomField $customField)
    {
        return view('custom-fields.edit', [
            'field' => $customField,
            'modules' => self::MODULES,
            'types' => self::TYPES,
        ]);
    }

    public function update(Request $request, CustomField $customField)
    {
        $validated = $request->validate([
            'module' => 'required|string|in:'.implode(',', self::MODULES),
            'field_name' => 'required|string|max:100',
            'field_type' => 'required|in:'.implode(',', self::TYPES),
            'options' => 'nullable|string',
            'is_required' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $options = null;
        if (in_array($validated['field_type'], ['select']) && ! empty($validated['options'])) {
            $options = array_values(array_filter(array_map('trim', explode("\n", $validated['options']))));
        }

        $customField->update([
            'module' => $validated['module'],
            'field_name' => $validated['field_name'],
            'field_type' => $validated['field_type'],
            'options' => $options,
            'is_required' => $request->boolean('is_required'),
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('custom-fields.index')->with('success', 'Custom field diperbarui.');
    }

    public function destroy(CustomField $customField)
    {
        if ($customField->values()->exists()) {
            return back()->with('error', 'Custom field tidak bisa dihapus karena masih punya data tersimpan.');
        }
        $customField->delete();

        return redirect()->route('custom-fields.index')->with('success', 'Custom field dihapus.');
    }
}
