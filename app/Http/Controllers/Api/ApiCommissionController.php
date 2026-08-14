<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ServiceTechnician;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiCommissionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = ServiceTechnician::query()->with(['service', 'user', 'paidBy']);

        if ($request->boolean('unpaid')) {
            $query->unpaid();
        }

        if ($request->filled('technician_id')) {
            $query->where('user_id', $request->get('technician_id'));
        }

        $commissions = $query->latest()->paginate($request->get('per_page', 20));

        return response()->json($commissions);
    }

    public function markPaid(ServiceTechnician $serviceTechnician): JsonResponse
    {
        if ($serviceTechnician->paid_at) {
            return response()->json(['message' => 'Commission already paid.'], 422);
        }

        $serviceTechnician->update([
            'paid_at' => now(),
            'paid_by' => auth()->id(),
        ]);

        return response()->json($serviceTechnician->load(['service', 'user', 'paidBy']));
    }
}
