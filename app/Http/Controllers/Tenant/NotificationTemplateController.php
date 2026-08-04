<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\NotificationTemplate;
use Illuminate\Http\Request;

class NotificationTemplateController extends Controller
{
    public function index(Request $request)
    {
        $templates = NotificationTemplate::query()
            ->when($request->search, fn($q, $v) => $q->where('name', 'like', "%{$v}%"))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('notification-templates.index', compact('templates'));
    }

    public function create()
    {
        return view('notification-templates.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:notification_templates,slug'],
            'channel' => ['required', 'in:email,whatsapp'],
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        NotificationTemplate::create($validated);

        return redirect()->route('notification-templates.index')->with('success', 'Template berhasil dibuat.');
    }

    public function show($id)
    {
        $template = NotificationTemplate::findOrFail($id);
        return view('notification-templates.show', compact('template'));
    }

    public function edit($id)
    {
        $template = NotificationTemplate::findOrFail($id);
        return view('notification-templates.edit', compact('template'));
    }

    public function update(Request $request, $id)
    {
        $template = NotificationTemplate::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:notification_templates,slug,' . $template->id],
            'channel' => ['required', 'in:email,whatsapp'],
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $template->update($validated);

        return redirect()->route('notification-templates.index')->with('success', 'Template berhasil diperbarui.');
    }

    public function destroy($id)
    {
        NotificationTemplate::findOrFail($id)->delete();
        return redirect()->route('notification-templates.index')->with('success', 'Template berhasil dihapus.');
    }

    public function preview(NotificationTemplate $notificationTemplate)
    {
        $placeholders = [
            '{customer_name}' => 'Ahmad Fauzi',
            '{vehicle_plate}' => 'B 1234 XYZ',
            '{service_date}' => now()->format('d/m/Y'),
            '{job_no}' => 'JOB-2026-0001',
            '{invoice_number}' => 'INV-2026-0001',
            '{total_amount}' => 'Rp 1.500.000',
            '{payment_method}' => 'Transfer Bank',
            '{workshop_name}' => 'Aplikasi Bengkel Terbaik',
            '{workshop_phone}' => '0812-3456-7890',
        ];

        $subject = str_replace(array_keys($placeholders), array_values($placeholders), $notificationTemplate->subject ?? '');
        $body = str_replace(array_keys($placeholders), array_values($placeholders), $notificationTemplate->body ?? '');

        return view('notification-templates.preview', compact('notificationTemplate', 'subject', 'body'));
    }
}
