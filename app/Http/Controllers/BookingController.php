<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Branch;
use App\Models\RepairCategory;
use App\Models\Service;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    /** Public: form booking */
    public function publicForm()
    {
        $branches = Branch::where('is_active', true)->orderBy('name')->get();
        $categories = RepairCategory::where('is_active', true)->orderBy('repair_category_name')->get();
        return view('public.booking-form', compact('branches', 'categories'));
    }

    public function publicStore(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:30',
            'email' => 'nullable|email|max:255',
            'vehicle_plate' => 'nullable|string|max:20',
            'vehicle_brand' => 'nullable|string|max:50',
            'vehicle_model' => 'nullable|string|max:100',
            'booking_at' => 'required|date|after:now',
            'complaint' => 'nullable|string|max:1000',
        ]);

        Booking::withoutGlobalScopes()->create($validated + ['status' => 'pending']);

        return view('public.booking-success', ['name' => $validated['name'], 'booking_at' => $validated['booking_at']]);
    }

    /** Admin */
    public function adminIndex(Request $request)
    {
        $query = Booking::with('customer', 'service');
        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('date_from')) $query->whereDate('booking_at', '>=', $request->date_from);
        if ($request->filled('date_to')) $query->whereDate('booking_at', '<=', $request->date_to);
        $bookings = $query->latest('booking_at')->paginate(20)->withQueryString();

        $summary = [
            'pending' => Booking::where('status', 'pending')->count(),
            'today' => Booking::whereDate('booking_at', today())->count(),
            'this_week' => Booking::whereBetween('booking_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
        ];

        return view('bookings.index', compact('bookings', 'summary'));
    }

    public function adminUpdate(Request $request, Booking $booking)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,confirmed,in_progress,done,cancelled',
            'admin_notes' => 'nullable|string',
        ]);
        $booking->update($validated);
        return back()->with('success', 'Status booking diperbarui.');
    }

    public function adminDestroy(Booking $booking)
    {
        $booking->delete();
        return back()->with('success', 'Booking dihapus.');
    }

    public function calendar() { return view('bookings.calendar'); }

    public function calendarEvents()
    {
        $start = request('start', now()->startOfMonth()->toDateString());
        $end = request('end', now()->endOfMonth()->toDateString());
        $bookings = Booking::whereBetween('booking_date', [$start, $end])->get()->map(fn($b) => [
            'id' => $b->id, 'title' => ($b->customer_name ?? 'Booking') . ' - ' . ($b->vehicle_plate ?? ''),
            'start' => $b->booking_date->format('Y-m-d\TH:i'), 'backgroundColor' => $b->status === 'confirmed' ? '#10b981' : '#f59e0b', 'url' => route('bookings.index'),
        ]);
        $services = Service::with('customer')->whereBetween('service_date', [$start, $end])->get()->map(fn($s) => [
            'id' => 'svc-'.$s->id, 'title' => '🔧 '.($s->customer->name ?? 'Service').' - '.$s->title,
            'start' => $s->service_date->format('Y-m-d\TH:i'), 'backgroundColor' => '#3b82f6', 'url' => route('services.show', $s),
        ]);
        return response()->json($bookings->concat($services));
    }
}
