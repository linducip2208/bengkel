<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['subcontractor_id', 'service_id', 'description', 'cost', 'status', 'assigned_date', 'completed_date'])]
class SubcontractorJob extends Model
{
    protected $casts = ['cost' => 'decimal:2', 'assigned_date' => 'date', 'completed_date' => 'date'];
    public function subcontractor(): BelongsTo { return $this->belongsTo(Subcontractor::class); }
    public function service(): BelongsTo { return $this->belongsTo(Service::class); }
}
