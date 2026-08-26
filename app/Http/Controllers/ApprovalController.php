<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        $service = DB::transaction(function () use ($token) {
            $locked = Service::withoutGlobalScopes()->where('approval_token', $token)->lockForUpdate()->firstOrFail();
            if ((int) $locked->workflow_status === 4 && $locked->is_approved) {
                return $locked;
            }
            if ((int) $locked->workflow_status !== 3) {
                abort(409, 'Estimasi tidak sedang menunggu persetujuan.');
            }
            $locked->update(['is_approved' => true, 'approved_at' => now(), 'workflow_status' => 4]);
            ActivityLog::record('estimate.approved', $locked, 'Estimasi disetujui customer melalui tautan publik.');

            return $locked;
        });

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
        $service = DB::transaction(function () use ($token) {
            $locked = Service::withoutGlobalScopes()->where('approval_token', $token)->lockForUpdate()->firstOrFail();
            if ($locked->cancelled_at) {
                return $locked;
            }
            if ((int) $locked->workflow_status !== 3) {
                abort(409, 'Estimasi tidak sedang menunggu persetujuan.');
            }
            $locked->update(['is_approved' => false, 'cancelled_at' => now(), 'cancel_reason' => 'Ditolak customer via WhatsApp']);
            ActivityLog::record('estimate.rejected', $locked, 'Estimasi ditolak customer melalui tautan publik.');

            return $locked;
        });

        return redirect()->route('public.approval.reject', $token)
            ->with('success', 'Estimasi servis telah ditolak. Kami akan menghubungi Anda.');
    }
}
