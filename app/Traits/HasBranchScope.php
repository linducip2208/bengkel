<?php

namespace App\Traits;

use App\Models\Branch;
use App\Scopes\BranchScope;
use App\Services\BranchContext;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

trait HasBranchScope
{
    protected static function bootHasBranchScope(): void
    {
        static::addGlobalScope(new BranchScope);

        static::creating(function ($model) {
            if (! empty($model->branch_id)) {
                return;
            }

            // 1. Explicit session context (web tenant UI).
            if (session()->has('current_branch_id')) {
                $branchId = session('current_branch_id');
                if ($branchId) {
                    $model->branch_id = $branchId;
                }

                return;
            }

            // 2. Stateless fallback (Sanctum API): scope to the user's branch
            //    when unambiguously resolvable, so transactional rows are not
            //    silently written as branch_id = NULL (globally visible).
            if (Auth::check()) {
                $branchId = app(BranchContext::class)->resolveCurrentBranchId();
                if ($branchId) {
                    $model->branch_id = $branchId;
                }
            }
        });
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function scopeWithoutBranchScope($query)
    {
        return $query->withoutGlobalScope(BranchScope::class);
    }
}
