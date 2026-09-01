<?php

namespace App\Models;

use App\Support\IdentityNormalizer;
use App\Traits\HasBranchScope;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string|null $number_plate
 * @property string|null $model_name
 * @property int|null $model_year
 * @property int|null $odometer
 * @property VehicleType|null $vehicleType
 * @property VehicleBrand|null $vehicleBrand
 */
#[Fillable(['customer_id', 'vehicle_type_id', 'vehicle_brand_id', 'fuel_type_id', 'number_plate', 'chassis_number', 'engine_number', 'model_name', 'model_year', 'color', 'odometer', 'branch_id', 'price', 'description'])]
class Vehicle extends Model
{
    use HasBranchScope, HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::saving(function (self $vehicle): void {
            $vehicle->number_plate = IdentityNormalizer::vehiclePlate($vehicle->number_plate);
            $vehicle->chassis_number = IdentityNormalizer::serialNumber($vehicle->chassis_number);
            $vehicle->engine_number = IdentityNormalizer::serialNumber($vehicle->engine_number);
        });
    }

    protected function casts(): array
    {
        return [
            'odometer' => 'integer',
            'price' => 'decimal:2',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function vehicleType(): BelongsTo
    {
        return $this->belongsTo(VehicleType::class);
    }

    public function vehicleBrand(): BelongsTo
    {
        return $this->belongsTo(VehicleBrand::class);
    }

    public function fuelType(): BelongsTo
    {
        return $this->belongsTo(FuelType::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(VehicleImage::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function mediaAttachments(): MorphMany
    {
        return $this->morphMany(MediaAttachment::class, 'attachable');
    }
}
