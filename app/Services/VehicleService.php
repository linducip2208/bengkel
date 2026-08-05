<?php

namespace App\Services;

use App\Models\Vehicle;
use Illuminate\Pagination\LengthAwarePaginator;

class VehicleService extends BaseService
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = Vehicle::with(['customer', 'vehicleType', 'vehicleBrand', 'fuelType']);

        if (!empty($filters['search'])) {
            $term = $filters['search'];
            $query->where(function ($q) use ($term) {
                $q->where('number_plate', 'like', "%{$term}%")
                    ->orWhere('chassis_number', 'like', "%{$term}%")
                    ->orWhere('engine_number', 'like', "%{$term}%")
                    ->orWhereHas('customer', fn($c) => $c->where('name', 'like', "%{$term}%"));
            });
        }

        if (!empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        if (!empty($filters['vehicle_type_id'])) {
            $query->where('vehicle_type_id', $filters['vehicle_type_id']);
        }

        return $query->latest()->paginate($filters['per_page'] ?? 15)->withQueryString();
    }

    public function create(array $data): Vehicle
    {
        return Vehicle::create($data);
    }

    public function update(Vehicle $vehicle, array $data): Vehicle
    {
        $vehicle->update($data);
        return $vehicle;
    }

    public function delete(Vehicle $vehicle): void
    {
        $vehicle->delete();
    }

    public function findByPlate(string $plate): ?Vehicle
    {
        return Vehicle::where('number_plate', $plate)
            ->with(['customer', 'vehicleType', 'vehicleBrand'])
            ->first();
    }

    public function getServiceHistory(Vehicle $vehicle)
    {
        return $vehicle->services()->with(['repairCategory'])->latest()->get();
    }

    public function predictNextService(Vehicle $vehicle): array
    {
        $odometer = $vehicle->odometer ?? 0;
        $suggestedKm = $odometer + 10000;

        return [
            'current_odometer' => $odometer,
            'suggested_next_odometer' => $suggestedKm,
            'suggested_date' => now()->addMonths(6)->format('Y-m-d'),
            'message' => "Servis berikutnya disarankan pada {$suggestedKm} km atau sekitar " . now()->addMonths(6)->translatedFormat('F Y') . '.',
        ];
    }

    public function uploadImage(Vehicle $vehicle, $file, ?string $caption = null): void
    {
        $path = $file->store('vehicle-images', 'public');
        $vehicle->images()->create([
            'image_path' => $path,
            'caption' => $caption,
        ]);
    }
}
