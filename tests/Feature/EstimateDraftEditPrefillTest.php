<?php

namespace Tests\Feature;

use App\Models\ServiceEstimate;
use App\Services\EstimateService;

class EstimateDraftEditPrefillTest extends EstimateTestCase
{
    public function test_existing_draft_is_prefilled_and_updates_in_place(): void
    {
        $service = $this->makeService();
        $product = $this->makeProduct('KAMPAS REM', 350000);
        $estimate = app(EstimateService::class)->createDraft($service, [
            'discount' => 10000,
            'discount_type' => 'fixed',
            'notes' => 'Catatan draft',
        ], [
            $this->partPayload($product, ['quantity' => 1, 'discount' => 5000]),
            $this->itemPayload(['description' => 'JASA GANTI REM', 'unit_price' => 75000]),
            $this->itemPayload(['item_type' => 'other', 'description' => 'BIAYA EKSTERNAL', 'unit_price' => 25000]),
        ]);

        $response = $this->get(route('estimates.create', ['service_id' => $service->id]));
        $response->assertOk()
            ->assertSee('KAMPAS REM')
            ->assertSee('JASA GANTI REM')
            ->assertSee('BIAYA EKSTERNAL')
            ->assertSee('name="items[0][product_id]"', false)
            ->assertSee('value="'.$product->id.'"', false)
            ->assertSee('value="350000.00"', false)
            ->assertSee('value="5000.00"', false);

        $payload = $this->storePayload([
            $this->partPayload($product, ['quantity' => 2, 'discount' => 0]),
            $this->itemPayload(['description' => 'JASA GANTI REM', 'unit_price' => 80000]),
            $this->itemPayload(['item_type' => 'other', 'description' => 'BIAYA EKSTERNAL', 'unit_price' => 30000]),
        ]);
        $this->put(route('estimates.update', $estimate), $payload)->assertRedirect();

        $fresh = $estimate->fresh(['items']);
        $this->assertSame($estimate->id, $fresh->id);
        $this->assertSame($estimate->estimate_number, $fresh->estimate_number);
        $this->assertSame(1, ServiceEstimate::query()->where('service_id', $service->id)->count());
        $this->assertCount(3, $fresh->items);
        $this->assertSame(2.0, (float) $fresh->items->first()->quantity);
        $this->assertSame(80000.0, (float) $fresh->items->get(1)->unit_price);
    }
}
