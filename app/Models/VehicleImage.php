<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

#[Fillable(['vehicle_id', 'image_path', 'caption'])]
class VehicleImage extends Model
{
    use HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        // Physical file cleanup on every delete path (web, API, console).
        static::deleting(function (self $image) {
            if ($image->image_path) {
                Storage::disk('public')->delete($image->image_path);
            }
        });
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
