<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Service;
use Illuminate\Http\Request;

class PublicSurveyController extends Controller
{
    /** Public — customer rates service after completion via survey_token */
    public function show(string $token)
    {
        $service = Service::withoutGlobalScopes()
            ->with(['customer', 'vehicle.vehicleBrand'])
            ->where('survey_token', $token)
            ->firstOrFail();

        $existing = Review::where('service_id', $service->id)->first();

        return view('public.survey', compact('service', 'existing'));
    }

    /** Public — save NPS survey rating */
    public function store(Request $request, string $token)
    {
        $service = Service::withoutGlobalScopes()->where('survey_token', $token)->firstOrFail();

        $validated = $request->validate([
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

        return redirect()->route('survey.show', $token)->with('success', 'Terima kasih atas rating Anda!');
    }
}
