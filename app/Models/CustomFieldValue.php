<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['custom_field_id', 'entity_type', 'entity_id', 'value'])]
class CustomFieldValue extends Model
{
    use HasFactory;

    public function customField(): BelongsTo
    {
        return $this->belongsTo(CustomField::class);
    }
}
