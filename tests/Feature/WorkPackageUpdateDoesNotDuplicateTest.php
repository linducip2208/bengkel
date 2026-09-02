<?php

namespace Tests\Feature;

use App\Models\ServiceFinding;
use App\Models\ServiceWorkPackage;
use App\Services\ObservationService;
use App\Services\WorkshopFlowService;

/**
 * D. Draft Work Package update → same ID (dedicated test file).
 */
class WorkPackageUpdateDoesNotDuplicateTest extends WorkshopFlowTestCase
{
    public function test_repeated_draft_saves_keep_one_row_and_one_id(): void
    {
        $service = $this->makeService();
        $flow = app(WorkshopFlowService::class);

        $package = $flow->saveWorkPackage($service, [
            'title' => 'GANTI KAMPAS REM DEPAN',
            'standard_minutes' => 30,
        ], [
            ['item_type' => 'labor', 'description' => 'Jasa', 'quantity' => 1, 'unit_price' => 75000],
        ]);
        $idBefore = $package->id;

        // Repeated "saves" of the same draft — the idempotency contract.
        for ($i = 0; $i < 3; $i++) {
            $flow->saveWorkPackage($service, [
                'title' => 'GANTI KAMPAS REM DEPAN',
                'standard_minutes' => 30,
                'description' => 'revisi draft ke-'.$i,
            ], [
                ['item_type' => 'labor', 'description' => 'Jasa', 'quantity' => 1, 'unit_price' => 75000],
            ], $package);
        }

        $this->assertSame($idBefore, $package->fresh()->id);
        $this->assertSame(1, ServiceWorkPackage::where('service_id', $service->id)->count());
        $this->assertSame(1, $package->fresh()->items()->count());
    }

    public function test_finding_stays_single_when_package_saved_repeatedly(): void
    {
        [, , $pad] = $this->makeChecklistData();
        $service = $this->makeService();
        $flow = app(WorkshopFlowService::class);

        app(ObservationService::class)->saveCheckResults($service, [
            $pad->id => ['condition_status' => 'critical'],
        ]);
        $finding = ServiceFinding::where('service_id', $service->id)->firstOrFail();

        $package = $flow->saveWorkPackage($service, [
            'title' => 'GANTI KAMPAS',
            'service_finding_id' => $finding->id,
        ], [
            ['item_type' => 'labor', 'description' => 'Jasa', 'quantity' => 1, 'unit_price' => 75000],
        ], );

        // Re-saving the same package must not duplicate findings either.
        $flow->saveWorkPackage($service, [
            'title' => 'GANTI KAMPAS',
            'service_finding_id' => $finding->id,
        ], [
            ['item_type' => 'labor', 'description' => 'Jasa', 'quantity' => 1, 'unit_price' => 75000],
        ], $package);

        $this->assertSame($finding->id, $package->fresh()->service_finding_id);
        $this->assertSame(1, ServiceFinding::count());
        $this->assertSame(1, ServiceWorkPackage::count());
    }

    public function test_store_endpoint_is_idempotent_for_same_title(): void
    {
        [, , $pad] = $this->makeChecklistData();
        $service = $this->makeService();

        app(ObservationService::class)->saveCheckResults($service, [
            $pad->id => ['condition_status' => 'critical'],
        ]);
        $finding = ServiceFinding::where('service_id', $service->id)->firstOrFail();

        $payload = [
            'title' => 'GANTI KAMPAS REM DEPAN',
            'service_finding_id' => $finding->id,
            'standard_minutes' => 30,
            'items' => [
                ['item_type' => 'labor', 'description' => 'Jasa Ganti Kampas Rem', 'quantity' => 1, 'unit_price' => 75000, 'standard_minutes' => 30],
                ['item_type' => 'part', 'description' => 'Kampas Rem Depan', 'quantity' => 1, 'unit_price' => 180000],
            ],
        ];

        // POST store creates one row even when finding already has a package.
        $this->post(route('services.work-packages.store', $service), $payload)->assertRedirect();

        $first = ServiceWorkPackage::where('service_id', $service->id)->firstOrFail();
        $this->post(route('services.work-packages.store', $service), $payload)->assertRedirect();

        $this->assertSame($first->id, ServiceWorkPackage::where('service_id', $service->id)->orderBy('id')->firstOrFail()->id);
        $this->assertSame(1, ServiceWorkPackage::where('service_finding_id', $finding->id)->count(), 'One finding → one package per save.');
    }
}
