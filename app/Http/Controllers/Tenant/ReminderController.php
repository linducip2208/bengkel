<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Reminder;
use App\Models\Vehicle;
use App\Services\ReminderService;
use Illuminate\Http\Request;

class ReminderController extends Controller
{
    public function index(Request $request)
    {
        $query = Reminder::with(['customer', 'vehicle']);

        if ($request->filled('type')) {
            $query->where('reminder_type', $request->type);
        }
        if ($request->filled('status')) {
            if ($request->status === 'sent') {
                $query->where('sent', true);
            } elseif ($request->status === 'pending') {
                $query->where('sent', false);
            }
        }

        $reminders = $query->latest()->paginate(20);

        return view('reminders.index', compact('reminders'));
    }

    public function create()
    {
        $customers = Customer::orderBy('name')->get();

        return view('reminders.create', compact('customers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'vehicle_id' => 'required|exists:vehicles,id',
            'reminder_type' => 'required|in:service,insurance,stnk',
            'reminder_date' => 'required|date',
            'message' => 'nullable|string',
        ]);

        $validated['created_by'] = auth()->id();

        Reminder::create($validated);

        return redirect()->route('reminders.index')
            ->with('success', 'Reminder created successfully.');
    }

    public function show(Reminder $reminder)
    {
        return view('reminders.show', compact('reminder'));
    }

    public function edit(Reminder $reminder)
    {
        $customers = Customer::orderBy('name')->get();
        $vehicles = Vehicle::where('customer_id', $reminder->customer_id)->orderBy('number_plate')->get();

        return view('reminders.edit', compact('reminder', 'customers', 'vehicles'));
    }

    public function update(Request $request, Reminder $reminder)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'vehicle_id' => 'required|exists:vehicles,id',
            'reminder_type' => 'required|in:service,insurance,stnk',
            'reminder_date' => 'required|date',
            'message' => 'nullable|string',
        ]);

        $reminder->update($validated);

        return redirect()->route('reminders.index')
            ->with('success', 'Reminder updated successfully.');
    }

    public function destroy(Reminder $reminder)
    {
        $reminder->delete();

        return redirect()->route('reminders.index')
            ->with('success', 'Reminder deleted successfully.');
    }

    public function send(Reminder $reminder, ReminderService $reminderService)
    {
        $reminderService->sendSingleReminder($reminder);

        return redirect()->route('reminders.index')
            ->with('success', 'Reminder sent successfully.');
    }

    public function sendScheduled(ReminderService $reminderService)
    {
        $count = $reminderService->sendDueReminders();

        return redirect()->route('reminders.index')
            ->with('success', "{$count} reminder(s) sent successfully.");
    }
}
