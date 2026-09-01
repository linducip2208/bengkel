<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Service;
use App\Models\ServiceEstimate;
use App\Services\EstimateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Legacy service-level approval links (/approve/{token}, /reject/{token}).
 *
 * When the service has an active ServiceEstimate the flow targets the
 * CURRENT estimate version (estimate-centric locking, see EstimateService);
 * legacy services without estimates keep the original behaviour.
 */
class ApprovalController extends Controller
{
    public function __construct(protected EstimateService $estimates) {}

    private function findByToken(string $token): Service
    {
        return Service::withoutGlobalScopes()
            ->with(['customer', 'vehicle.vehicleBrand', 'repairCategory'])
            ->where('approval_token', $token)
            ->firstOrFail();
    }

    private function approvableEstimate(Service $service): ?ServiceEstimate
    {
        $estimate = $this->estimates->latestActiveEstimate($service);

        return $estimate !== null && in_array($estimate->status, ServiceEstimate::APPROVABLE_STATUSES, true)
            ? $estimate
            : null;
    }

    public function showApprove(string $token)
    {
        $service = $this->findByToken($token);
        $estimate = $this->approvableEstimate($service);

        // Prefer the dedicated estimate approval page for the current version.
        if ($estimate !== null) {
            return redirect()->route('public.estimate.show', $estimate->getOrCreatePublicToken());
        }

        return view('public.approve', compact('service'));
    }

    public function approve(Request $request, string $token)
    {
        $service = $this->findByToken($token);
        $estimate = $this->approvableEstimate($service);

        if ($estimate !== null) {
            $this->estimates->approve($estimate, 'public_link');

            return redirect()
                ->route('public.estimate.show', $estimate->public_token)
                ->with('success', 'Terima kasih! Estimasi servis telah disetujui.');
        }

        DB::transaction(function () use ($token) {
            $locked = Service::withoutGlobalScopes()->where('approval_token', $token)->lockForUpdate()->firstOrFail();

            if ((int) $locked->workflow_status === 4 && $locked->is_approved) {
                return $locked; // idempotent
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
        $estimate = $this->approvableEstimate($service);

        if ($estimate !== null) {
            return redirect()->route('public.estimate.show', $estimate->getOrCreatePublicToken());
        }

        return view('public.reject', compact('service'));
    }

    public function reject(Request $request, string $token)
    {
        $service = $this->findByToken($token);
        $estimate = $this->approvableEstimate($service);

        if ($estimate !== null) {
            $reason = trim((string) $request->input('reason', ''));
            $this->estimates->reject($estimate, $reason !== '' ? $reason : 'Ditolak customer via WhatsApp');

            return redirect()
                ->route('public.estimate.show', $estimate->public_token)
                ->with('success', 'Estimasi telah ditolak. Kami akan menghubungi Anda untuk revisi.');
        }

        DB::transaction(function () use ($token) {
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
