<?php

namespace App\Traits;

use App\Scopes\BranchScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait HasBranchScope
{
    protected static function bootHasBranchScope(): void
    {
        static::addGlobalScope(new BranchScope);

        static::creating(function ($model) {
            if (empty($model->branch_id) && session()->has('current_branch_id')) {
                $branchId = session('current_branch_id');
                if ($branchId) {
                    $model->branch_id = $branchId;
                }
            }
        });
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Branch::class);
    }

    public function scopeWithoutBranchScope($query)
    {
        return $query->withoutGlobalScope(BranchScope::class);
    }
}
