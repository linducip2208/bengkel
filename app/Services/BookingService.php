<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\FuelType;
use App\Models\RepairCategory;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleBrand;
use App\Models\VehicleType;
use App\Support\IdentityNormalizer;
use Illuminate\Support\Facades\DB;

/**
 * Booking business logic shared by the web admin flow and the API.
 *
 * convertToService() is atomic + idempotent: the booking row is locked FOR
 * UPDATE and re-checked, so a double-click / concurrent request converts a
 * booking exactly once and the reserved technician (if any) is carried over
 * to the new service.
 */
class BookingService
{
    public function convertToService(Booking $booking): Service
    {
        return DB::transaction(function () use ($booking) {
            $locked = Booking::query()->whereKey($booking->id)->lockForUpdate()->first();

            if (! $locked) {
                throw new \RuntimeException('Booking tidak ditemukan.');
            }
            if (! in_array($locked->status, ['pending', 'confirmed'], true)) {
                throw new \RuntimeException('Hanya booking dengan status pending/confirmed yang bisa dikonversi.');
            }
            if ($locked->service_id) {
                return Service::withoutGlobalScopes()->findOrFail($locked->service_id);
            }

            $customer = $this->resolveCustomer($locked);
            $vehicle = $this->resolveVehicle($locked, $customer);
            $repairCategoryId = $locked->repair_category_id
                ?? RepairCategory::query()->value('id');
            if (! $repairCategoryId) {
                $repairCategoryId = RepairCategory::createWithUniqueSlug([
                    'repair_category_name' => 'Lain-lain',
                    'is_active' => true,
                ])->id;
            }

            $jobNo = app(ServiceService::class)->generateJobNo();
            $service = Service::create([
                'job_no' => $jobNo,
                'customer_id' => $customer->id,
                'vehicle_id' => $vehicle->id,
                'repair_category_id' => $repairCategoryId,
                'service_date' => $locked->booking_at,
                'description' => $locked->complaint,
                'title' => $locked->complaint ?: 'Servis dari booking',
                'done_status' => 0,
                'workflow_status' => 0,
                'created_by' => auth()->id() ?? 1,
                'branch_id' => $locked->branch_id,
            ]);

            // Carry the reserved technician onto the new service.
            $this->carryTechnician($service, $locked->technician_id);

            $locked->update([
                'customer_id' => $customer->id,
                'service_id' => $service->id,
                'status' => 'confirmed',
            ]);

            ActivityLog::record('booking.convert', $locked, "Booking dikonversi ke Service {$jobNo}");

            return $service;
        });
    }

    private function resolveCustomer(Booking $booking): Customer
    {
        $phone = IdentityNormalizer::indonesianPhone($booking->phone);
        $email = IdentityNormalizer::email($booking->email);

        return Customer::withoutGlobalScopes()->firstOrCreate(
            ['phone' => $phone],
            ['name' => $booking->name, 'email' => $email]
        );
    }

    private function resolveVehicle(Booking $booking, Customer $customer): Vehicle
    {
        if ($booking->vehicle_plate) {
            $plate = IdentityNormalizer::vehiclePlate($booking->vehicle_plate);
            $vehicle = Vehicle::withoutGlobalScopes()->where('number_plate', $plate)->first();
            if ($vehicle && $vehicle->customer_id !== $customer->id) {
                throw new \RuntimeException('Nomor polisi sudah terdaftar pada pelanggan lain; periksa identitas booking.');
            }
            if (! $vehicle) {
                $vehicle = Vehicle::withoutGlobalScopes()->create([
                    'customer_id' => $customer->id,
                    'number_plate' => $plate,
                    'model_name' => trim(($booking->vehicle_brand ?: '').' '.($booking->vehicle_model ?: '')) ?: null,
                ]);
            }

            return $vehicle;
        }

        $vehicle = Vehicle::withoutGlobalScopes()->where('customer_id', $customer->id)->first();
        if ($vehicle) {
            return $vehicle;
        }

        $fuelId = FuelType::query()->value('id');
        $typeId = VehicleType::query()->value('id');
        $brand = $typeId ? VehicleBrand::where('vehicle_type_id', $typeId)->value('id') : null;
        if (! $typeId || ! $brand || ! $fuelId) {
            throw new \RuntimeException('Data master kendaraan (tipe/merek/bahan bakar) belum lengkap — lengkapi terlebih dahulu.');
        }

        return Vehicle::create([
            'customer_id' => $customer->id,
            'vehicle_type_id' => $typeId,
            'vehicle_brand_id' => $brand,
            'fuel_type_id' => $fuelId,
            'number_plate' => 'PLAT-BELUM-DISET-'.$booking->id,
            'model_name' => trim(($booking->vehicle_brand ?: '').' '.($booking->vehicle_model ?: '')) ?: 'Belum diisi',
        ]);
    }

    private function carryTechnician(Service $service, ?int $technicianId): void
    {
        if (! $technicianId) {
            return;
        }

        $technician = User::query()->whereKey($technicianId)->first();
        if (! $technician) {
            return;
        }

        $service->technicians()->sync([$technicianId]);
        $service->update(['assign_to' => $technicianId]);
        app(ServiceService::class)->calculateCommissions($service, [$technicianId]);
    }
}
