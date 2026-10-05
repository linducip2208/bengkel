<?php

namespace App\Traits;

use App\Models\Branch;
use App\Models\User;
use App\Scopes\BranchScope;
use App\Services\BranchContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

trait HasBranchScope
{
    protected static function bootHasBranchScope(): void
    {
        static::addGlobalScope(new BranchScope);

        static::creating(function ($model) {
            $user = Auth::user();

            // System/console seeding (no user): keep existing behaviour.
            if (! $user instanceof User) {
                if (empty($model->branch_id) && session()->has('current_branch_id') && session('current_branch_id')) {
                    $model->branch_id = (int) session('current_branch_id');
                }

                return;
            }

            $access = $user->branchAccess();

            // Explicit branch_id supplied: must be authorized (fail-closed).
            if (! empty($model->branch_id)) {
                if (! $access->canAccess((int) $model->branch_id)) {
                    throw new AuthorizationException('Cabang tidak dapat diakses.');
                }

                return;
            }

            // Unrestricted admins keep existing intended global behaviour:
            // explicit session context wins, otherwise NULL stays NULL.
            if ($access->isUnrestricted()) {
                if (session()->has('current_branch_id') && session('current_branch_id')) {
                    $model->branch_id = (int) session('current_branch_id');
                }

                return;
            }

            // Restricted user: resolve authorized branch, never guess NULL.
            $branchId = app(BranchContext::class)->resolveCurrentBranchId();

            if ($branchId && $access->canAccess($branchId)) {
                $model->branch_id = $branchId;

                return;
            }

            // Zero-branch users, or multi-branch users without an explicit
            // authorized context: FAIL CLOSED instead of branch_id = NULL.
            throw new AuthorizationException('Cabang tidak dapat ditentukan. Pilih cabang yang Anda akses.');
        });

        static::updating(function ($model) {
            $user = Auth::user();
            if (! $user instanceof User) {
                return;
            }

            if (! $model->isDirty('branch_id')) {
                return;
            }

            // Moving a row to another branch (or to NULL/global) requires
            // access to the destination branch. NULL is unrestricted-only.
            if (! $user->branchAccess()->canAccess($model->branch_id ? (int) $model->branch_id : null)) {
                throw new AuthorizationException('Perubahan cabang tidak diizinkan.');
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
