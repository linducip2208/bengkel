<?php

namespace App\Scopes;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class BranchScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $table = $model->getTable();

        // 1. Explicit branch context (web session) wins, but it must still be
        //    authorized: a restricted user pinned to a branch they cannot
        //    access gets zero rows (fail-closed), never a fallback.
        if (session()->has('current_branch_id')) {
            $branchId = session('current_branch_id');
            if ($branchId) {
                $branchId = (int) $branchId;
                $user = Auth::user();
                if ($user instanceof User && ! $user->branchAccess()->canAccess($branchId)) {
                    $this->applyImpossible($builder);

                    return;
                }
                $builder->where("{$table}.branch_id", $branchId);

                return;
            }

            // Falsy session value: fall through to user-based scoping.
        }

        // 2. Stateless contexts (Sanctum API) + web without pinned branch:
        //    restrict to the authenticated user's branch access.
        $user = Auth::user();

        if (! $user instanceof User) {
            // System/console/guest context (no user): no constraint. HTTP
            // guest access is still rejected by auth middleware; console jobs
            // and public token flows use explicit withoutGlobalScopes().
            return;
        }

        $access = $user->branchAccess();

        if ($access->isUnrestricted()) {
            return;
        }

        if ($access->branchIds !== []) {
            // Restricted: ONLY assigned branches. NULL branch rows are
            // global/legacy and visible to unrestricted users only.
            $builder->whereIn("{$table}.branch_id", $access->branchIds);

            return;
        }

        // Restricted with zero assignments: zero rows (fail-closed).
        $this->applyImpossible($builder);
    }

    private function applyImpossible(Builder $builder): void
    {
        // Database-safe always-false predicate (no invalid SQL).
        $builder->whereRaw('1 = 0');
    }
}
