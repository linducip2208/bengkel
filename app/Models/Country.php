<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'name', 'phone_code'])]
class Country extends Model
{
    use HasFactory;

    public function states(): HasMany
    {
        return $this->hasMany(State::class);
    }
}
