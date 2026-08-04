<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['state_id', 'name'])]
class City extends Model
{
    use HasFactory, SoftDeletes;

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }
}
