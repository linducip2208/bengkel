<?php

namespace Tests\Feature;

use App\Http\Controllers\BookingController;
use App\Http\Middleware\RequirePair;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\FuelType;
use App\Models\Income;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\ProductUnit;
use App\Models\Reminder;
use App\Models\Service;
use App\Models\StockRecord;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleBrand;
use App\Models\VehicleType;
use App\Services\PaymentService;
use App\Services\StockService;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\RedirectResponse;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * One connected flow:
 * Booking → Job Card → parts consumption → completion → Invoice →
 * partial payment → full payment → journals → next-service reminder.
 */
class EndToEndWorkshopFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            RequirePair::class,
            PreventRequestForgery::class,
        ]);

        Role::findOrCreate('service_advisor', 'web');
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('service_advisor');
        $this->actingAs($user);
    }

    public function test_full_customer_vehicle_booking_service_invoice_payment_chain(): void
    {
        // ------------------------------------------------------ Customer & Vehicle
        $customer = Customer::create(['name' => 'E2E Pelanggan', 'phone' => '081234567890']);

        $vtype = VehicleType::create(['vehicle_type' => 'Hatchback', 'slug' => uniqid()]);
        $vbrand = VehicleBrand::create(['vehicle_type_id' => $vtype->id, 'vehicle_brand' => 'Honda '.uniqid()]);
        $fuel = FuelType::create(['fuel_type' => 'Pertamax', 'slug' => uniqid()]);
        $vehicle = Vehicle::create([
            'customer_id' => $customer->id,
            'vehicle_type_id' => $vtype->id,
            'vehicle_brand_id' => $vbrand->id,
            'fuel_type_id' => $fuel->id,
            'number_plate' => 'D '.uniqid(),
            'model_name' => 'Brio',
            'odometer' => 20000,
        ]);

        // ------------------------------------------------------ Booking → Service
        $booking = Booking::create([
            'customer_id' => $customer->id,
            'name' => $customer->name,
            'phone' => '081234567890',
            'vehicle_plate' => $vehicle->number_plate,
            'complaint' => 'Rem berdecit',
            'booking_at' => now(),
            'status' => 'confirmed',
        ]);

        $controller = app(BookingController::class);
        $response = $controller->convertToService($booking);
        $this->assertInstanceOf(RedirectResponse::class, $response);

        $service = Service::find($booking->fresh()->service_id);
        $this->assertNotNull($service, 'Booking must convert to a job card.');
        $this->assertEquals($customer->id, $service->customer_id, 'No re-entry of customer data.');
        $this->assertEquals($vehicle->id, $service->vehicle_id, 'No re-entry of vehicle data.');
        $this->assertStringContainsString('Rem berdecit', (string) $service->description);
        $this->assertMatchesRegularExpression('/^BP-\d{8}-\d{3,4}$/', $service->job_no);

        // ------------------------------------------------------ Parts consumption
        $product = Product::create([
            'product_no' => 'P-'.uniqid(),
            'code' => 'C-'.uniqid(),
            'name' => 'Kampas Rem',
            'product_type_id' => ProductType::create(['type' => 'T'.uniqid(), 'slug' => uniqid(), 'is_active' => true])->id,
            'unit_id' => ProductUnit::create(['name' => 'N'.uniqid(), 'abbreviation' => uniqid(), 'is_active' => true])->id,
            'price' => 120000,
            'cost_price' => 85000,
        ]);
        StockRecord::create(['product_id' => $product->id, 'quantity' => 10]);

        StockService::decrement($product->id, 1, 'usage', 'Digunakan dalam servis', Service::class, $service->id);
        $service->update(['charge' => 100000]);

        // ------------------------------------------------------ Completion → Invoice
        $completeResponse = $this->post("/services/{$service->id}/complete");
        $completeResponse->assertRedirect();

        $invoice = Invoice::withoutGlobalScopes()->where('service_id', $service->id)->first();
        $this->assertNotNull($invoice);

        // charge 100.000 + part 120.000 = 220.000
        $this->assertEquals(220000.0, (float) $invoice->grand_total);

        // Stock consumed exactly once
        $this->assertEquals(9, (int) StockRecord::withoutGlobalScopes()->where('product_id', $product->id)->value('quantity'));

        // Accrual journal: Dr AR 220.000 / Cr Revenue(s) 220.000
        $arEntry = JournalEntry::where('reference_type', Invoice::class)
            ->where('reference_id', $invoice->id)
            ->where('entry_type', 'ar_invoice')
            ->first();
        $this->assertNotNull($arEntry);
        $this->assertEqualsWithDelta(220000.0, (float) $arEntry->lines()->sum('debit'), 0.005);
        $this->assertEqualsWithDelta(220000.0, (float) $arEntry->lines()->sum('credit'), 0.005);

        // COGS posted at cost: 1 × 85.000
        $cogsEntry = JournalEntry::where('reference_type', Invoice::class)
            ->where('reference_id', $invoice->id)
            ->where('entry_type', 'cogs')
            ->first();
        $this->assertNotNull($cogsEntry);
        $this->assertEqualsWithDelta(85000.0, (float) $cogsEntry->lines()->sum('debit'), 0.005);

        // ------------------------------------------------------ Payments
        $method = PaymentMethod::create(['payment' => 'Cash E2E', 'slug' => uniqid(), 'is_active' => true]);

        app(PaymentService::class)->process($invoice->fresh(), [
            'amount' => 50000,
            'payment_method_id' => $method->id,
            'payment_date' => now()->toDateTimeString(),
        ]);

        $invoice = $invoice->fresh();
        $this->assertEquals(1, $invoice->payment_status, 'Partial payment status.');

        app(PaymentService::class)->process($invoice->fresh(), [
            'amount' => 170000,
            'payment_method_id' => $method->id,
            'payment_date' => now()->toDateTimeString(),
        ]);

        $invoice = $invoice->fresh();
        $this->assertEquals(2, $invoice->payment_status, 'Settled after full payment.');
        $this->assertEquals(220000.0, (float) $invoice->paid_amount);

        // Income booked once for the settled invoice
        $this->assertEquals(
            1,
            Income::where('invoice_number', $invoice->invoice_number)->count()
        );

        // Every journal in the whole flow balances
        $entries = JournalEntry::with('lines')->get();
        foreach ($entries as $entry) {
            $this->assertEqualsWithDelta(
                round((float) $entry->lines->sum('debit'), 2),
                round((float) $entry->lines->sum('credit'), 2),
                0.005,
                "Unbalanced journal {$entry->entry_number}"
            );
        }

        // ------------------------------------------------------ Next service reminder
        $this->assertTrue(
            Reminder::where('service_id', $service->id)->exists(),
            'Completion must schedule the next-service reminder.'
        );

        // ------------------------------------------------------ Warranty fields on sold part
        $item = $invoice->items()->withoutGlobalScopes()->where('product_id', $product->id)->first();
        $this->assertNotNull($item, 'Consumed part must appear as invoice line.');

        // ------------------------------------------------------ Vehicle history hub shows the chain
        $this->assertEquals(1, $customer->services()->count());
        $this->assertEquals(1, $customer->vehicles()->whereKey($vehicle->id)->count());
    }
}
