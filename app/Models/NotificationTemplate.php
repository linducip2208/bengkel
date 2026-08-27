<?php

namespace App\Models;

use App\Models\Concerns\HasRaceSafeUniqueSlug;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'slug', 'channel', 'subject', 'body', 'is_active'])]
class NotificationTemplate extends Model
{
    use HasFactory, HasRaceSafeUniqueSlug, SoftDeletes;

    protected const UNIQUE_SLUG_SOURCE = 'name';

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
