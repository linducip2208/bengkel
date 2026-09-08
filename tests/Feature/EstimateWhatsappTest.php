<?php

namespace Tests\Feature;

use App\Services\EstimateService;

class EstimateWhatsappTest extends EstimateTestCase
{
    public function test_whatsapp_redirect_contains_customer_estimate_total_and_public_link_only(): void
    {
        $service = $this->makeService();
        $service->customer->update(['phone' => '081234567890']);
        $estimate = app(EstimateService::class)->createDraft($service, [], [
            $this->itemPayload(['description' => 'JASA GANTI REM', 'unit_price' => 75000]),
        ]);

        $response = $this->post(route('estimates.send-wa', $estimate));
        $response->assertRedirect();
        $location = (string) $response->headers->get('Location');

        $this->assertStringStartsWith('https://wa.me/6281234567890?text=', $location);
        $this->assertStringContainsString(urlencode($service->customer->name), $location);
        $this->assertStringContainsString(urlencode($estimate->estimate_number), $location);
        $this->assertStringContainsString(urlencode('Rp 75.000'), $location);
        $this->assertStringContainsString(urlencode(route('public.estimate.show', $estimate->fresh()->public_token)), $location);
        $this->assertStringNotContainsString('cost_price', $location);
        $this->assertStringNotContainsString('Sisa Pembayaran', $location);
    }
}
