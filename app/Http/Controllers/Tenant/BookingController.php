<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Service;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function calendar()
    {
        return view('bookings.calendar');
    }

    /** JSON feed for FullCalendar */
    public function calendarEvents()
    {
        $start = request('start', now()->startOfMonth()->toDateString());
        $end = request('end', now()->endOfMonth()->toDateString());

        $bookings = Booking::whereBetween('booking_at', [$start, $end])->get()->map(fn($b) => [
            'id' => $b->id,
            'title' => ($b->name ?? 'Booking') . ' - ' . ($b->vehicle_plate ?? ''),
            'start' => $b->booking_at->format('Y-m-d\TH:i'),
            'backgroundColor' => $b->status === 'confirmed' ? '#10b981' : '#f59e0b',
            'url' => route('bookings.index'),
        ]);

        $services = Service::with('customer')->whereBetween('service_date', [$start, $end])->get()->map(fn($s) => [
            'id' => 'svc-' . $s->id,
            'title' => '🔧 ' . ($s->customer->name ?? 'Service') . ' - ' . $s->title,
            'start' => $s->service_date->format('Y-m-d\TH:i'),
            'backgroundColor' => '#3b82f6',
            'url' => route('services.show', $s),
        ]);

        return response()->json($bookings->concat($services));
    }

    // PUT update booking status from admin
    public function adminUpdate(Request $request, Booking $booking)
    {
        $booking->update(['status' => $request->status, 'admin_notes' => $request->notes]);
        return back()->with('success', 'Booking diupdate.');
    }

    public function adminDestroy(Booking $booking) { $booking->delete(); return back()->with('success', 'Booking dihapus.'); }

    public function publicForm() { return view('public.booking-form'); }

    public function publicStore(Request $request)
    {
        $v = $request->validate([
            'name' => 'required|string', 'phone' => 'required|string',
            'vehicle_plate' => 'required|string', 'vehicle_brand' => 'nullable|string',
            'booking_at' => 'required|date', 'service_type' => 'nullable|string', 'notes' => 'nullable|string',
        ]);
        Booking::create(array_merge($v, ['status' => 'pending']));
        return view('public.booking-success');
    }
}
