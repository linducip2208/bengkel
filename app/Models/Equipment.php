<?php

namespace App\Models;

use App\Traits\HasBranchScope;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['branch_id', 'name', 'code', 'category', 'purchase_date', 'purchase_price', 'status', 'next_maintenance_date', 'maintenance_interval_days', 'notes'])]
class Equipment extends Model
{
    use HasBranchScope, SoftDeletes;
    protected $table = 'equipment';
    protected $casts = ['purchase_date' => 'date', 'next_maintenance_date' => 'date', 'purchase_price' => 'decimal:2'];
    public function maintenanceLogs(): HasMany { return $this->hasMany(EquipmentMaintenanceLog::class); }

    public function getIsDueMaintenanceAttribute(): bool
    {
        return $this->next_maintenance_date && $this->next_maintenance_date->isPast();
    }
}
