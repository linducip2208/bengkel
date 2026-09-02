<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Service;
use App\Models\ServiceFinding;
use App\Services\WorkshopFlowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FindingController extends Controller
{
    public function __construct(protected WorkshopFlowService $flow) {}

    /**
     * Update a finding in place — the primary key never changes, so
     * repeatedly saving the same finding never creates duplicates.
     */
    public function update(Request $request, ServiceFinding $finding): RedirectResponse
    {
        abort_unless((bool) auth()->user()?->can('findings.update'), 403, 'Tidak punya izin mengubah temuan.');

        $validated = $request->validate([
            'title' => 'required|string|min:2|max:255',
            'description' => 'nullable|string|max:2000',
            'technician_note' => 'nullable|string|max:2000',
            'recommendation' => 'nullable|string|max:2000',
            'severity' => 'required|in:'.implode(',', ServiceFinding::SEVERITIES),
            'measurement_value' => 'nullable|numeric|min:0',
            'measurement_unit' => 'nullable|string|max:20',
        ]);

        $oldSeverity = $finding->severity;
        $finding->update($validated);

        ActivityLog::record('finding.updated', $finding, "Temuan {$finding->finding_number} diperbarui manual", [
            'old_severity' => $oldSeverity,
            'new_severity' => $finding->severity,
        ]);

        return redirect()
            ->to(route('services.show', $finding->service_id).'#tab-findings')
            ->with('success', "Temuan {$finding->finding_number} diperbarui.");
    }

    public function resolve(Request $request, ServiceFinding $finding, WorkshopFlowService $flow): RedirectResponse
    {
        abort_unless((bool) auth()->user()?->can('findings.resolve'), 403, 'Tidak punya izin menyelesaikan temuan.');

        $reason = trim((string) $request->input('reason', ''));
        $service = $finding->service()->withoutGlobalScopes()->first() ?? $finding->service;
        \assert($service instanceof Service);
        $flow->resolveFinding($finding, $service, $reason !== '' ? $reason : 'Diselesaikan manual oleh staf.');

        return redirect()
            ->to(route('services.show', $finding->service_id).'#tab-findings')
            ->with('success', "Temuan {$finding->finding_number} diselesaikan.");
    }

    public function defer(Request $request, ServiceFinding $finding, WorkshopFlowService $flow): RedirectResponse
    {
        abort_unless((bool) auth()->user()?->can('findings.resolve'), 403, 'Tidak punya izin menunda temuan.');

        $flow->deferFinding($finding, $request->input('reason'));

        return redirect()
            ->to(route('services.show', $finding->service_id).'#tab-findings')
            ->with('success', "Temuan {$finding->finding_number} ditunda (deferred).");
    }
}
