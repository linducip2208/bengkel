<?php

namespace App\Models;

use App\Traits\HasBranchScope;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'service_id', 'customer_id', 'branch_id', 'reviewer_name',
    'rating', 'comment', 'is_published', 'admin_reply',
])]
class Review extends Model
{
    use HasBranchScope;

    protected function casts(): array
    {
        return ['rating' => 'integer', 'is_published' => 'boolean'];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
