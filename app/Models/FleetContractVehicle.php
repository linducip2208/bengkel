<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['fleet_contract_id', 'vehicle_id'])]
class FleetContractVehicle extends Model
{
    protected $table = 'fleet_contract_vehicles';

    public function fleetContract(): BelongsTo
    {
        return $this->belongsTo(FleetContract::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
