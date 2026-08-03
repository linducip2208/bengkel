<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Service;
use Illuminate\Http\Request;

class TrackingController extends Controller
{
    /** Public — customer track service status via tracking_token */
    public function show(string $token)
    {
        $service = Service::withoutGlobalScopes()
            ->with(['customer', 'vehicle.vehicleBrand', 'jobcardDetail', 'invoice', 'serviceObservationPoints.observationPoint'])
            ->where('tracking_token', $token)
            ->firstOrFail();

        $review = Review::where('service_id', $service->id)->first();
        return view('public.tracking-status', compact('service', 'review'));
    }

    /** Public — submit review via tracking link */
    public function review(Request $request, string $token)
    {
        $service = Service::withoutGlobalScopes()->where('tracking_token', $token)->firstOrFail();

        $validated = $request->validate([
            'reviewer_name' => 'required|string|max:255',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        Review::withoutGlobalScopes()->updateOrCreate(
            ['service_id' => $service->id],
            $validated + [
                'customer_id' => $service->customer_id,
                'branch_id' => $service->branch_id,
                'is_published' => false, // admin approve dulu sebelum publik
            ]
        );

        return redirect()->route('public.tracking', $token)->with('success', 'Terima kasih atas review Anda!');
    }
}
