<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['equipment_id', 'maintenance_date', 'performed_by', 'cost', 'description'])]
class EquipmentMaintenanceLog extends Model
{
    protected $casts = ['maintenance_date' => 'date', 'cost' => 'decimal:2'];

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }
}
