<?php

namespace Tests\Feature;

use App\Models\PartReservation;
use App\Services\EstimateService;
use App\Services\WorkshopFlowService;

/**
 * H. Rejected work package → no stock reservation.
 * Approved parts become reservations; estimate alone never touches stock.
 */
class ApprovedPartsReservationTest extends WorkshopFlowTestCase
{
    protected function makeEstimateWithPartPackage(): array
    {
        $service = $this->makeService();
        $flow = app(WorkshopFlowService::class);
        $product = $this->makeProduct();

        $package = $flow->saveWorkPackage($service, [
            'title' => 'GANTI KAMPAS REM DEPAN',
        ], [
            ['item_type' => 'labor', 'description' => 'Jasa', 'quantity' => 1, 'unit_price' => 75000],
            ['item_type' => 'part', 'description' => 'Kampas Rem Depan', 'quantity' => 2, 'unit_price' => 180000, 'product_id' => $product->id],
        ]);

        $estimate = app(EstimateService::class)->createDraft($service, [], [], [$package->id]);
        app(EstimateService::class)->markSent($estimate, 'test');
        $estimate = $estimate->fresh();
        $group = $estimate->groups()->firstOrFail();

        return [$estimate, $group, $package];
    }

    public function test_approval_creates_reservation_for_part_items(): void
    {
        [$estimate, $group] = $this->makeEstimateWithPartPackage();

        app(WorkshopFlowService::class)->submitGroupDecisions($estimate, [
            ['group_id' => $group->id, 'decision' => 'approved'],
        ], 'public_link');

        $reservations = PartReservation::where('service_id', $estimate->service_id)->get();
        $this->assertSame(1, $reservations->count());
        $this->assertSame('reserved', $reservations->first()->status);
        $this->assertEqualsWithDelta(2.0, (float) $reservations->first()->quantity, 0.001);
    }

    public function test_repeated_approval_does_not_duplicate_reservations(): void
    {
        [$estimate, $group] = $this->makeEstimateWithPartPackage();
        $flow = app(WorkshopFlowService::class);

        $flow->submitGroupDecisions($estimate, [
            ['group_id' => $group->id, 'decision' => 'approved'],
        ], 'public_link');
        $flow->reservePartsForApprovedGroups($estimate->fresh());
        $flow->reservePartsForApprovedGroups($estimate->fresh());

        $this->assertSame(1, PartReservation::where('service_id', $estimate->service_id)->count());
    }

    public function test_rejected_package_reserves_nothing(): void
    {
        [$estimate, $group] = $this->makeEstimateWithPartPackage();

        app(WorkshopFlowService::class)->submitGroupDecisions($estimate, [
            ['group_id' => $group->id, 'decision' => 'rejected'],
        ], 'public_link');

        // Critical assertion H: rejected → zero reservations.
        $this->assertSame(0, PartReservation::count());
    }

    public function test_estimate_draft_reserves_nothing_and_stock_untouched(): void
    {
        [$estimate] = $this->makeEstimateWithPartPackage();

        // Estimate is draft — no reservation and no stock mutation yet.
        $this->assertSame(0, PartReservation::count());
    }

    public function test_partial_approval_reserves_approved_parts_only(): void
    {
        $service = $this->makeService();
        $flow = app(WorkshopFlowService::class);
        $productA = $this->makeProduct('PART A');
        $productB = $this->makeProduct('PART B');

        $packageA = $flow->saveWorkPackage($service, [
            'title' => 'WORK WITH PARTS',
        ], [
            ['item_type' => 'part', 'description' => 'Part A', 'quantity' => 1, 'unit_price' => 50000, 'product_id' => $productA->id],
        ]);
        $packageB = $flow->saveWorkPackage($service, [
            'title' => 'WORK REJECTED',
        ], [
            ['item_type' => 'part', 'description' => 'Part B', 'quantity' => 3, 'unit_price' => 50000, 'product_id' => $productB->id],
        ]);

        $estimate = app(EstimateService::class)->createDraft($service, [], [], [$packageA->id, $packageB->id]);
        app(EstimateService::class)->markSent($estimate, 'test');
        $estimate = $estimate->fresh();

        $groupA = $estimate->groups()->where('service_work_package_id', $packageA->id)->firstOrFail();
        $groupB = $estimate->groups()->where('service_work_package_id', $packageB->id)->firstOrFail();

        $flow->submitGroupDecisions($estimate, [
            ['group_id' => $groupA->id, 'decision' => 'approved'],
            ['group_id' => $groupB->id, 'decision' => 'rejected'],
        ], 'public_link');

        $reservations = PartReservation::where('service_id', $service->id)->get();
        $this->assertSame(1, $reservations->count());
        $this->assertSame($productA->id, $reservations->first()->product_id);
    }

    public function test_reservations_carry_package_tag_for_release(): void
    {
        [$estimate, $group, $package] = $this->makeEstimateWithPartPackage();

        app(WorkshopFlowService::class)->submitGroupDecisions($estimate, [
            ['group_id' => $group->id, 'decision' => 'approved'],
        ], 'public_link');

        $reservation = PartReservation::where('service_id', $estimate->service_id)->firstOrFail();
        $this->assertStringContainsString('WP#'.$package->id, $reservation->notes);
    }
}
