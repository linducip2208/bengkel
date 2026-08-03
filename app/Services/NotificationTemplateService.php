<?php

namespace App\Services;

use App\Models\NotificationTemplate;
use Illuminate\Http\Request;

class NotificationTemplateService extends BaseService
{
    public function index(Request $request)
    {
        $query = NotificationTemplate::query();
        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }
        return $query->orderBy('name')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:notification_templates',
            'slug' => 'nullable|string|max:255|unique:notification_templates',
            'channel' => 'required|string|max:50',
            'subject' => 'nullable|string|max:255',
            'body' => 'required|string',
            'is_active' => 'boolean',
        ]);
        return NotificationTemplate::create($validated);
    }

    public function show($id)
    {
        return NotificationTemplate::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $model = NotificationTemplate::findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:notification_templates,name,' . $id,
            'slug' => 'nullable|string|max:255|unique:notification_templates,slug,' . $id,
            'channel' => 'required|string|max:50',
            'subject' => 'nullable|string|max:255',
            'body' => 'required|string',
            'is_active' => 'boolean',
        ]);
        $model->update($validated);
        return $model;
    }

    public function destroy($id)
    {
        $model = NotificationTemplate::findOrFail($id);
        $model->delete();
        return $model;
    }
}
