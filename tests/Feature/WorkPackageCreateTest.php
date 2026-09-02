<?php

namespace Tests\Feature;

use App\Models\ServiceFinding;
use App\Models\ServiceWorkPackage;
use App\Models\ServiceWorkPackageItem;
use App\Services\ObservationService;
use App\Services\WorkshopFlowService;

/**
 * C. Draft Work Package update → same ID.
 */
class WorkPackageCreateTest extends WorkshopFlowTestCase
{
    public function test_create_work_package_from_finding(): void
    {
        [, , $pad] = $this->makeChecklistData();
        $service = $this->makeService();

        app(ObservationService::class)->saveCheckResults($service, [
            $pad->id => ['condition_status' => 'critical', 'comment' => 'Kampas hampir habis', 'measurement_value' => '1.2', 'measurement_unit' => 'mm'],
        ]);
        $finding = ServiceFinding::where('service_id', $service->id)->firstOrFail();

        $package = app(WorkshopFlowService::class)->saveWorkPackage($service, [
            'title' => 'GANTI KAMPAS REM DEPAN',
            'service_finding_id' => $finding->id,
            'standard_minutes' => 30,
            'description' => 'Kampas rem depan tersisa sekitar 1.2 mm',
        ], [
            ['item_type' => 'labor', 'description' => 'Ganti Kampas Rem', 'quantity' => 1, 'unit_price' => 75000, 'standard_minutes' => 30],
            ['item_type' => 'part', 'description' => 'Kampas Rem Depan', 'quantity' => 1, 'unit_price' => 180000],
        ]);

        $package->refresh();
        $this->assertSame(ServiceWorkPackage::STATUS_DRAFT, $package->status);
        $this->assertSame($finding->id, $package->service_finding_id);
        $this->assertSame('critical', $package->severity_snapshot);
        $this->assertSame(30, $package->standard_minutes);
        $this->assertSame(2, $package->items->count());

        // Finding moves to work_proposed.
        $finding->refresh();
        $this->assertSame(ServiceFinding::STATUS_WORK_PROPOSED, $finding->status);
    }

    public function test_manual_work_package_without_finding(): void
    {
        $service = $this->makeService();

        $package = app(WorkshopFlowService::class)->saveWorkPackage($service, [
            'title' => 'GANTI OLI MESIN',
            'standard_minutes' => 15,
        ], [
            ['item_type' => 'labor', 'description' => 'Jasa Ganti Oli', 'quantity' => 1, 'unit_price' => 30000, 'standard_minutes' => 15],
            ['item_type' => 'part', 'description' => 'Oli Shell HX5', 'quantity' => 1, 'unit_price' => 120000],
        ]);

        $this->assertNull($package->service_finding_id);
        $this->assertNull($package->severity_snapshot);
        $this->assertSame(ServiceWorkPackage::STATUS_DRAFT, $package->status);
    }

    public function test_update_endpoint_keeps_primary_key_for_draft(): void
    {
        $service = $this->makeService();
        $package = app(WorkshopFlowService::class)->saveWorkPackage($service, [
            'title' => 'DRAFT AWAL',
        ], [
            ['item_type' => 'labor', 'description' => 'Jasa', 'quantity' => 1, 'unit_price' => 50000],
        ]);
        $idBefore = $package->id;
        $countBefore = ServiceWorkPackage::count();

        $this->put(route('work-packages.update', $package), [
            'title' => 'DRAFT DIPERBARUI',
            'standard_minutes' => 45,
            'items' => [
                ['item_type' => 'labor', 'description' => 'Jasa', 'quantity' => 1, 'unit_price' => 75000, 'standard_minutes' => 45],
            ],
        ])->assertRedirect();

        $package->refresh();
        // Critical assertion C: same primary key.
        $this->assertSame($idBefore, $package->id);
        $this->assertSame($countBefore, ServiceWorkPackage::count());
        $this->assertSame('DRAFT DIPERBARUI', $package->title);
        $this->assertSame(45, $package->standard_minutes);
        $this->assertEqualsWithDelta(75000.0, (float) $package->items->first()->line_total, 0.01);
    }

    public function test_non_draft_package_cannot_be_updated(): void
    {
        $service = $this->makeService();
        $package = app(WorkshopFlowService::class)->saveWorkPackage($service, [
            'title' => 'TERKUNCI',
        ], [
            ['item_type' => 'labor', 'description' => 'Jasa', 'quantity' => 1, 'unit_price' => 50000],
        ]);
        $package->forceFill(['status' => ServiceWorkPackage::STATUS_APPROVED])->save();
        $titleBefore = $package->title;

        $this->put(route('work-packages.update', $package), [
            'title' => 'HACK',
            'items' => [['item_type' => 'labor', 'description' => 'x', 'quantity' => 1, 'unit_price' => 1]],
        ])->assertStatus(422);

        $package->refresh();
        $this->assertSame($titleBefore, $package->title);
    }

    public function test_work_package_items_have_no_discount_field(): void
    {
        $service = $this->makeService();
        $package = app(WorkshopFlowService::class)->saveWorkPackage($service, [
            'title' => 'WP',
        ], [
            ['item_type' => 'part', 'description' => 'Part', 'quantity' => 2, 'unit_price' => 90000],
        ]);

        $item = $package->items->first();
        $this->assertArrayNotHasKey('discount', $item->getAttributes());
        $this->assertInstanceOf(ServiceWorkPackageItem::class, $item);
    }
}
