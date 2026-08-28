<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiBookingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Booking::query()->with(['customer', 'service', 'technician']);

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->filled('technician_id')) {
            $query->where('technician_id', $request->get('technician_id'));
        }

        if ($dateFrom = $request->get('date_from')) {
            $query->whereDate('booking_at', '>=', $dateFrom);
        }

        if ($dateTo = $request->get('date_to')) {
            $query->whereDate('booking_at', '<=', $dateTo);
        }

        $bookings = $query->latest('booking_at')->paginate($request->get('per_page', 20));

        return response()->json($bookings);
    }

    public function show(Booking $booking): JsonResponse
    {
        return response()->json($booking->load(['customer', 'service', 'technician']));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:30',
            'vehicle_plate' => 'nullable|string|max:20',
            'vehicle_brand' => 'nullable|string|max:50',
            'vehicle_model' => 'nullable|string|max:100',
            'booking_at' => 'required|date',
            'complaint' => 'nullable|string|max:1000',
            'repair_category_id' => 'nullable|exists:repair_categories,id',
        ]);

        $booking = Booking::create($validated + ['status' => 'pending']);

        return response()->json($booking->load(['customer', 'technician']), 201);
    }

    public function update(Request $request, Booking $booking): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,confirmed,in_progress,done,cancelled',
            'admin_notes' => 'nullable|string',
        ]);

        $booking->update($validated);

        return response()->json($booking);
    }

    public function destroy(Booking $booking): JsonResponse
    {
        $booking->delete();

        return response()->json(['message' => 'Booking deleted.']);
    }

    public function convert(Booking $booking): JsonResponse
    {
        try {
            $service = app(BookingService::class)->convertToService($booking);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Booking dikonversi ke service.',
            'service_id' => $service->id,
            'job_no' => $service->job_no,
        ], 201);
    }
}
