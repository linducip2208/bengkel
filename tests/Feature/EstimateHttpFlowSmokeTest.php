<?php

namespace Tests\Feature;

use App\Http\Middleware\RequirePair;
use App\Models\Customer;
use App\Models\FuelType;
use App\Models\Invoice;
use App\Models\RepairCategory;
use App\Models\Service;
use App\Models\ServiceEstimate;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleBrand;
use App\Models\VehicleType;
use App\Services\EstimateService;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * End-to-end smoke: estimate builder HTTP flow, PDF rendering, public page,
 * approval, conversion. Deleted-friendly: pure integration check.
 */
class EstimateHttpFlowSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([RequirePair::class, PreventRequestForgery::class]);

        Role::findOrCreate('service_advisor', 'web');
        foreach (['estimates.view', 'estimates.create', 'estimates.update', 'estimates.send', 'estimates.revise', 'estimates.convert_invoice'] as $perm) {
            Permission::findOrCreate($perm, 'web');
        }
        $user = User::factory()->create(['is_active' => true]);
        $user->givePermissionTo(['estimates.view', 'estimates.create', 'estimates.update', 'estimates.send', 'estimates.revise', 'estimates.convert_invoice']);
        $this->actingAs($user);
    }

    public function test_full_estimate_flow_over_http_with_multipage_pdf(): void
    {
        $estimateService = app(EstimateService::class);

        // Build a service via the estimate test-case fixtures inline.
        $customer = Customer::create(['name' => 'UAT Customer', 'phone' => '081234567890', 'email' => 'uat@example.test', 'address' => 'Jl. Uji No. 1']);
        $type = VehicleType::create(['vehicle_type' => 'MPV', 'slug' => uniqid()]);
        $brand = VehicleBrand::create(['vehicle_type_id' => $type->id, 'vehicle_brand' => 'Toyota']);
        $fuel = FuelType::create(['fuel_type' => 'Bensin', 'slug' => uniqid()]);
        $vehicle = Vehicle::create([
            'customer_id' => $customer->id, 'vehicle_type_id' => $type->id, 'vehicle_brand_id' => $brand->id,
            'fuel_type_id' => $fuel->id, 'number_plate' => 'B 1234 UAT', 'model_name' => 'Kijang', 'model_year' => 2021, 'odometer' => 42000,
        ]);
        $category = RepairCategory::create(['repair_category_name' => 'Tune Up', 'slug' => uniqid(), 'is_active' => true]);
        $service = Service::create([
            'customer_id' => $customer->id, 'vehicle_id' => $vehicle->id, 'repair_category_id' => $category->id,
            'job_no' => 'BP-UAT-1', 'title' => 'Overhaul kopling', 'service_date' => now(),
            'workflow_status' => 2, 'created_by' => auth()->id(),
        ]);

        // 1. Create estimate (30+ mixed items) over HTTP.
        $items = [];
        for ($i = 1; $i <= 25; $i++) {
            $items[] = ['item_type' => 'part', 'product_id' => null, 'description' => 'SPAREPART '.$i, 'quantity' => 2, 'unit_price' => 50000 + $i * 100, 'discount' => 0, 'discount_type' => 'fixed', 'tax_rate' => null];
        }
        for ($i = 1; $i <= 10; $i++) {
            $items[] = ['item_type' => 'labor', 'product_id' => null, 'description' => 'JASA O/H KOPLING '.$i, 'quantity' => 1, 'unit_price' => 200000, 'discount' => 10, 'discount_type' => 'percent', 'tax_rate' => 11];
        }

        $response = $this->post("/services/{$service->id}/estimates", [
            'estimate_date' => now()->toDateString(),
            'valid_until' => now()->addDays(7)->toDateString(),
            'notes' => 'Keluhan: kopling berat',
            'items' => $items,
        ]);

        $response->assertRedirect();
        /** @var ServiceEstimate $estimate */
        $estimate = ServiceEstimate::query()->where('service_id', $service->id)->firstOrFail();
        $this->assertCount(35, $estimate->items()->get());

        // 2. Service detail page renders the Estimasi tab.
        $this->get('/services/'.$service->id)->assertOk();

        // 3. Send + PDF render.
        $estimateService->markSent($estimate, 'test');
        $estimate = $estimate->fresh();
        $this->assertNotNull($estimate->snapshot);
        $this->assertNotNull($estimate->public_token);

        $pdf = $this->get('/estimates/'.$estimate->id.'/pdf');
        $pdf->assertOk();
        $this->assertSame('%PDF', substr((string) $pdf->getContent(), 0, 4));

        $this->get('/estimates/'.$estimate->id.'/preview')->assertOk();
        $this->get('/estimates/'.$estimate->id.'/print')->assertOk();

        // 4. Public page + public PDF.
        $public = $this->get('/estimate/'.$estimate->public_token);
        $public->assertOk();
        $public->assertSee($estimate->estimate_number);
        $public->assertSee('JASA O/H KOPLING 10');
        $this->assertSame('%PDF', substr((string) $this->get('/estimate/'.$estimate->public_token.'/pdf')->getContent(), 0, 4));

        // 5. Customer approves.
        $this->post('/estimate/'.$estimate->public_token.'/approve')->assertRedirect();
        $this->assertSame(ServiceEstimate::STATUS_APPROVED, $estimate->fresh()->status);

        // 6. Convert to invoice via HTTP (idempotent).
        $this->post('/estimates/'.$estimate->id.'/convert-invoice')->assertRedirect();
        $this->post('/estimates/'.$estimate->id.'/convert-invoice')->assertRedirect();
        $this->assertSame(1, Invoice::withoutGlobalScopes()->where('service_estimate_id', $estimate->id)->count());

        $invoice = Invoice::withoutGlobalScopes()->where('service_estimate_id', $estimate->id)->firstOrFail();
        $this->get('/invoices/'.$invoice->id.'/pdf')->assertOk();
    }
}
