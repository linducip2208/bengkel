<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\EmailLog;
use App\Models\FuelType;
use App\Models\NotificationQueue;
use App\Models\RepairCategory;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleBrand;
use App\Models\VehicleType;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Smoke test perintah marketing & notifikasi terjadwal (sebelumnya nol test).
 * Menangkap bug nyata: birthday:send query kolom `birthday` yang tidak ada
 * di skema + fungsi MONTH()/DAY() MySQL-only (gagal di sqlite & prod).
 */
class MarketingCommandsTest extends TestCase
{
    use RefreshDatabase;

    private function makeService(Customer $customer, array $overrides = []): Service
    {
        $type = VehicleType::create(['vehicle_type' => 'MPV '.uniqid(), 'slug' => uniqid()]);
        $brand = VehicleBrand::create(['vehicle_type_id' => $type->id, 'vehicle_brand' => 'Toyota '.uniqid()]);
        $fuel = FuelType::create(['fuel_type' => 'Pertamax', 'slug' => uniqid()]);
        $vehicle = Vehicle::create([
            'customer_id' => $customer->id,
            'vehicle_type_id' => $type->id,
            'vehicle_brand_id' => $brand->id,
            'fuel_type_id' => $fuel->id,
            'number_plate' => 'B '.uniqid(),
            'model_name' => 'Avanza',
        ]);
        $category = RepairCategory::create(['repair_category_name' => 'Tune Up', 'slug' => uniqid(), 'is_active' => true]);
        $tech = User::factory()->create(['is_active' => true]);

        return Service::create(array_merge([
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'repair_category_id' => $category->id,
            'title' => 'Servis Rutin',
            'service_date' => now(),
            'done_status' => 0,
            'workflow_status' => 0,
            'job_no' => 'JOB-'.uniqid(),
            'created_by' => $tech->id,
            'assign_to' => $tech->id,
        ], $overrides));
    }

    public function test_birthday_send_dry_lists_today_birthdays(): void
    {
        Customer::create(['name' => 'Ultah Hari Ini', 'phone' => '0816000001', 'birth_date' => now()->toDateString()]);
        Customer::create(['name' => 'Ultah Besok', 'phone' => '0816000002', 'birth_date' => now()->addDay()->toDateString()]);

        $this->artisan('birthday:send --dry')
            ->assertSuccessful()
            ->expectsOutputToContain('Ultah Hari Ini')
            ->doesntExpectOutputToContain('Ultah Besok');

        // Mode dry: tidak ada email/log terkirim.
        $this->assertSame(0, EmailLog::count());
    }

    public function test_birthday_send_empty(): void
    {
        $this->artisan('birthday:send --dry')
            ->assertSuccessful()
            ->expectsOutputToContain('Tidak ada customer ulang tahun hari ini.');
    }

    public function test_birthday_vouchers_created_for_today(): void
    {
        $customer = Customer::create(['name' => 'Dapat Voucher', 'phone' => '0816000003', 'birth_date' => now()->toDateString()]);
        Customer::create(['name' => 'Tanpa Voucher', 'phone' => '0816000004', 'birth_date' => now()->addDays(5)->toDateString()]);

        $this->artisan('loyalty:birthday-vouchers')->assertSuccessful();

        $voucher = Voucher::where('code', 'like', 'BDY-'.$customer->id.'-%')->first();
        $this->assertNotNull($voucher);
        $this->assertSame('percent', $voucher->type);
        $this->assertTrue($voucher->is_active);
        $this->assertSame(0, Voucher::where('description', 'like', '%Tanpa Voucher%')->count());
    }

    public function test_reactivation_targets_dormant_only(): void
    {
        $dormant = Customer::create(['name' => 'Dorman', 'phone' => '0816000005']);
        $this->makeService($dormant, ['service_date' => now()->subMonths(7)]);

        $active = Customer::create(['name' => 'Aktif', 'phone' => '0816000006']);
        $this->makeService($active, ['service_date' => now()->subMonth()]);

        $this->artisan('marketing:reactivation')->assertSuccessful();

        $this->assertSame(1, Voucher::where('code', 'like', 'WAKE-'.$dormant->id.'-%')->count());
        $this->assertSame(0, Voucher::where('code', 'like', 'WAKE-'.$active->id.'-%')->count());
    }

    public function test_notification_queue_email_and_unknown_channel(): void
    {
        Mail::fake();

        NotificationQueue::create([
            'channel' => 'email', 'recipient' => 'uji@bengkel.test',
            'message' => 'Halo', 'status' => 'pending',
        ]);
        NotificationQueue::create([
            'channel' => 'pigeon', 'recipient' => 'x',
            'message' => 'Halo', 'status' => 'pending',
        ]);

        $this->artisan('notifications:process')->assertSuccessful();

        // Mail::fake() menelan pengiriman asli; status DB adalah buktinya.
        $this->assertSame('sent', NotificationQueue::where('recipient', 'uji@bengkel.test')->first()->status);
        $this->assertSame('failed', NotificationQueue::where('channel', 'pigeon')->first()->status);
    }

    public function test_service_reminders_for_tomorrow(): void
    {
        $customer = Customer::create(['name' => 'Besok Servis', 'phone' => '0816000007']);
        $service = $this->makeService($customer, ['service_date' => now()->addDay()]);

        $this->artisan('services:send-reminders')
            ->assertSuccessful()
            ->expectsOutputToContain('Besok Servis');

        $this->assertDatabaseHas('activity_logs', [
            'event' => 'service_reminder',
            'subject_type' => Service::class,
            'subject_id' => $service->id,
        ]);
    }
}
