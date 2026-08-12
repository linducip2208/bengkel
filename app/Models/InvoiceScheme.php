<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['name', 'prefix', 'format', 'start_number', 'next_number', 'branch_id', 'is_default', 'is_active'])]
class InvoiceScheme extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'start_number' => 'integer',
            'next_number' => 'integer',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
