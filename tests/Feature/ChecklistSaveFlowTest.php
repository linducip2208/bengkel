<?php

namespace Tests\Feature;

use App\Models\ServiceEstimate;
use App\Models\ServiceFinding;
use App\Models\ServiceObservationPoint;
use App\Models\User;
use App\Services\EstimateService;
use App\Services\ObservationService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Final UX fixes: continue button saves, authorization, legacy charge.
 */
class ChecklistSaveFlowTest extends WorkshopFlowTestCase
{
    /**
     * Regression: "Lanjut ke Temuan / Estimasi" must SAVE the checklist
     * (never navigate away with unsaved state) and land on #tab-findings.
     */
    public function test_continue_saves_critical_condition_creates_finding_and_redirects_to_findings(): void
    {
        [, , $pad] = $this->makeChecklistData();
        $service = $this->makeService();

        $response = $this->post(route('observations.save-checklist', $service), [
            'action' => 'continue',
            'points' => [
                $pad->id => ['condition_status' => 'critical', 'comment' => 'Kampas habis', 'measurement_value' => '1.2', 'measurement_unit' => 'mm'],
            ],
        ]);

        // Redirect ends in #tab-findings.
        $response->assertRedirect();
        $this->assertStringEndsWith('#tab-findings', $response->headers->get('Location'));

        // DB condition persisted.
        $row = ServiceObservationPoint::where('service_id', $service->id)->where('observation_point_id', $pad->id)->firstOrFail();
        $this->assertSame('critical', $row->condition_status);
        $this->assertEqualsWithDelta(1.2, (float) $row->measurement_value, 0.001);

        // Finding generated idempotently.
        $finding = ServiceFinding::where('service_id', $service->id)->firstOrFail();
        $this->assertSame(ServiceFinding::SEVERITY_CRITICAL, $finding->severity);
        $this->assertSame($row->id, $finding->service_observation_point_id);
    }

    public function test_draft_action_saves_and_returns_to_service(): void
    {
        [$oil] = $this->makeChecklistData();
        $service = $this->makeService();

        $response = $this->post(route('observations.save-checklist', $service), [
            'action' => 'draft',
            'points' => [
                $oil->id => ['condition_status' => 'ok'],
            ],
        ]);

        $response->assertRedirect(route('services.show', $service));
        $this->assertSame('ok', ServiceObservationPoint::where('service_id', $service->id)->where('observation_point_id', $oil->id)->firstOrFail()->condition_status);
    }

    public function test_continue_without_action_defaults_to_draft_redirect(): void
    {
        [$oil] = $this->makeChecklistData();
        $service = $this->makeService();

        // Legacy clients without action param still save (never lose state).
        $this->post(route('observations.save-checklist', $service), [
            'points' => [
                $oil->id => ['condition_status' => 'attention'],
            ],
        ])->assertRedirect(route('services.show', $service));

        $this->assertSame('attention', ServiceObservationPoint::where('service_id', $service->id)->where('observation_point_id', $oil->id)->firstOrFail()->condition_status);
    }

    /**
     * Authorization: view requires service.view/jobcard.view; write requires
     * service.edit OR findings.create — enforced server-side (403).
     */
    public function test_authorized_user_with_service_edit_can_update_checklist(): void
    {
        [$oil] = $this->makeChecklistData();
        $service = $this->makeService();

        Role::findOrCreate('sa', 'web');
        $sa = User::factory()->create(['is_active' => true]);
        $sa->assignRole('sa');
        Permission::findOrCreate('service.edit', 'web');
        Permission::findOrCreate('service.view', 'web');
        $sa->givePermissionTo(['service.edit', 'service.view']);
        $this->actingAs($sa);

        $this->post(route('observations.save-checklist', $service), [
            'points' => [$oil->id => ['condition_status' => 'ok']],
        ])->assertRedirect();

        $this->assertSame('ok', ServiceObservationPoint::where('service_id', $service->id)->where('observation_point_id', $oil->id)->firstOrFail()->condition_status);
    }

    public function test_authorized_user_with_findings_create_can_update_checklist(): void
    {
        [$oil] = $this->makeChecklistData();
        $service = $this->makeService();

        Role::findOrCreate('writer', 'web');
        $writer = User::factory()->create(['is_active' => true]);
        $writer->assignRole('writer');
        Permission::findOrCreate('findings.create', 'web');
        Permission::findOrCreate('service.view', 'web');
        $writer->givePermissionTo(['findings.create', 'service.view']);
        $this->actingAs($writer);

        $this->post(route('observations.save-checklist', $service), [
            'points' => [$oil->id => ['condition_status' => 'critical']],
        ])->assertRedirect();

        $this->assertSame('critical', ServiceObservationPoint::where('service_id', $service->id)->where('observation_point_id', $oil->id)->firstOrFail()->condition_status);
    }

    public function test_unauthorized_authenticated_user_gets_403_on_save(): void
    {
        [$oil] = $this->makeChecklistData();
        $service = $this->makeService();

        Role::findOrCreate('view_only', 'web');
        $viewer = User::factory()->create(['is_active' => true]);
        $viewer->assignRole('view_only');
        Permission::findOrCreate('service.view', 'web');
        $viewer->givePermissionTo('service.view');
        $this->actingAs($viewer);

        $this->post(route('observations.save-checklist', $service), [
            'points' => [$oil->id => ['condition_status' => 'critical']],
        ])->assertForbidden();

        // Nothing was written.
        $this->assertSame(0, ServiceObservationPoint::where('service_id', $service->id)->count());
        $this->assertSame(0, ServiceFinding::count());
    }

    public function test_unauthorized_user_cannot_create_findings_via_finding_endpoints(): void
    {
        [$oil] = $this->makeChecklistData();
        $service = $this->makeService();

        app(ObservationService::class)->saveCheckResults($service, [
            $oil->id => ['condition_status' => 'critical'],
        ]);
        $finding = ServiceFinding::where('service_id', $service->id)->firstOrFail();

        Role::findOrCreate('no_finding_perms', 'web');
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('no_finding_perms');
        $this->actingAs($user);

        $this->put(route('findings.update', $finding), [
            'title' => 'HACK',
            'severity' => 'attention',
        ])->assertForbidden();

        $this->post(route('findings.resolve', $finding))->assertForbidden();
        $this->post(route('findings.defer', [$service, $finding]))->assertForbidden();

        $finding->refresh();
        $this->assertSame('critical', $finding->severity);
        $this->assertNotSame(ServiceFinding::STATUS_RESOLVED, $finding->status);
    }

    public function test_checklist_page_renders_read_only_for_unauthorized_user(): void
    {
        [$oil] = $this->makeChecklistData();
        $service = $this->makeService();

        Role::findOrCreate('viewer_ro', 'web');
        $viewer = User::factory()->create(['is_active' => true]);
        $viewer->assignRole('viewer_ro');
        Permission::findOrCreate('service.view', 'web');
        $viewer->givePermissionTo('service.view');
        $this->actingAs($viewer);

        $this->get(route('observations.checklist', $service))
            ->assertOk()
            ->assertSee('fieldset disabled', false);
    }

    public function test_checklist_page_is_editable_for_authorized_user(): void
    {
        [$oil] = $this->makeChecklistData();
        $service = $this->makeService();

        $this->get(route('observations.checklist', $service))
            ->assertOk()
            ->assertDontSee('fieldset disabled', false);
    }
}

/**
 * Legacy service.charge conflict: edit form must never overwrite the
 * commercial approved amount once a ServiceEstimate exists.
 */
class LegacyServiceChargeTest extends WorkshopFlowTestCase
{
    protected function makeApprovedEstimate(Service $service): ServiceEstimate
    {
        [, , $pad] = $this->makeChecklistData();
        app(ObservationService::class)->saveCheckResults($service, [
            $pad->id => ['condition_status' => 'critical'],
        ]);
        $finding = ServiceFinding::where('service_id', $service->id)->firstOrFail();

        $package = app(WorkshopFlowService::class)->saveWorkPackage($service, [
            'title' => 'GANTI KAMPAS REM', 'service_finding_id' => $finding->id, 'standard_minutes' => 30,
        ], [
            ['item_type' => 'labor', 'description' => 'Jasa', 'quantity' => 1, 'unit_price' => 75000],
            ['item_type' => 'part', 'description' => 'Kampas', 'quantity' => 1, 'unit_price' => 180000],
        ]);

        $estimate = app(EstimateService::class)->createDraft($service, [], [], [$package->id]);
        app(EstimateService::class)->markSent($estimate, 'test');
        app(WorkshopFlowService::class)->submitGroupDecisions($estimate->fresh(), [
            ['group_id' => $estimate->groups()->firstOrFail()->id, 'decision' => 'approved'],
        ], 'public_link');

        return $estimate->fresh();
    }

    public function test_editing_service_does_not_modify_approved_estimate_or_charge(): void
    {
        $service = $this->makeService();
        $estimate = $this->makeApprovedEstimate($service);
        $service = $service->fresh();

        $chargeBefore = (string) $service->charge;
        $totalBefore = (string) $estimate->grand_total;
        $approvedBefore = (string) $estimate->approved_total;

        // Edit title/duration/customer — including a hostile charge payload.
        $this->put(route('services.update', $service), [
            'customer_id' => $service->customer_id,
            'vehicle_id' => $service->vehicle_id,
            'repair_category_id' => $service->repair_category_id,
            'title' => 'Judul Baru Setelah Edit',
            'service_date' => now()->addDay()->format('Y-m-d H:i:s'),
            'estimated_hours' => 3,
            'charge' => 999999,
        ])->assertRedirect();

        $service = $service->fresh();
        $estimate = $estimate->fresh();

        // Approved estimate untouched.
        $this->assertSame($totalBefore, (string) $estimate->grand_total);
        $this->assertSame($approvedBefore, (string) $estimate->approved_total);
        $this->assertSame(ServiceEstimate::STATUS_APPROVED, $estimate->status);

        // Approved service charge NOT overwritten by the hostile payload.
        $this->assertSame($chargeBefore, (string) $service->charge);
        $this->assertEqualsWithDelta(255000.0, (float) $service->charge, 0.01);

        // Other fields DID update.
        $this->assertSame('Judul Baru Setelah Edit', $service->title);
        $this->assertEqualsWithDelta(3.0, (float) $service->estimated_hours, 0.01);
    }

    public function test_legacy_service_without_estimate_keeps_charge_editable(): void
    {
        $service = $this->makeService();

        $this->put(route('services.update', $service), [
            'customer_id' => $service->customer_id,
            'vehicle_id' => $service->vehicle_id,
            'repair_category_id' => $service->repair_category_id,
            'title' => $service->title,
            'service_date' => now()->toDateString(),
            'charge' => 250000,
        ])->assertRedirect();

        $this->assertEqualsWithDelta(250000.0, (float) $service->fresh()->charge, 0.01);
    }

    public function test_edit_page_shows_read_only_estimate_card_when_estimate_exists(): void
    {
        $service = $this->makeService();
        $estimate = $this->makeApprovedEstimate($service);

        $this->get(route('services.edit', $service))
            ->assertOk()
            ->assertSee('Estimasi Biaya')
            ->assertSee($estimate->estimate_number)
            ->assertSee('Buka Estimasi')
            // No editable charge input for estimate-backed services.
            ->assertDontSee('name="charge"', false);
    }

    public function test_edit_page_shows_legacy_charge_for_service_without_estimate(): void
    {
        $service = $this->makeService();

        $this->get(route('services.edit', $service))
            ->assertOk()
            ->assertSee('Legacy')
            ->assertSee('name="charge"', false);
    }

    public function test_edit_page_labels_duration_clearly(): void
    {
        $service = $this->makeService();

        $this->get(route('services.edit', $service))
            ->assertOk()
            ->assertSee('Estimasi Durasi Pengerjaan')
            ->assertSee('Perkiraan lama pengerjaan, bukan estimasi biaya.')
            ->assertDontSee('Estimasi (jam)');
    }
}
