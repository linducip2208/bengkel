<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;

class ApprovalController extends Controller
{
    private function findByToken(string $token): Service
    {
        return Service::withoutGlobalScopes()
            ->with(['customer', 'vehicle.vehicleBrand', 'repairCategory'])
            ->where('approval_token', $token)
            ->firstOrFail();
    }

    public function showApprove(string $token)
    {
        $service = $this->findByToken($token);
        return view('public.approve', compact('service'));
    }

    public function approve(Request $request, string $token)
    {
        $service = $this->findByToken($token);

        $service->update([
            'is_approved' => true,
            'approved_at' => now(),
            'workflow_status' => 4,
        ]);

        return redirect()->route('public.approval.approve', $token)
            ->with('success', 'Terima kasih! Estimasi servis telah disetujui.');
    }

    public function showReject(string $token)
    {
        $service = $this->findByToken($token);
        return view('public.reject', compact('service'));
    }

    public function reject(Request $request, string $token)
    {
        $service = $this->findByToken($token);

        $service->update([
            'is_approved' => false,
            'cancelled_at' => now(),
            'cancel_reason' => 'Ditolak customer via WhatsApp',
        ]);

        return redirect()->route('public.approval.reject', $token)
            ->with('success', 'Estimasi servis telah ditolak. Kami akan menghubungi Anda.');
    }
}
