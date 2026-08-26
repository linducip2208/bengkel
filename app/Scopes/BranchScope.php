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
        // 1. Explicit branch context (web session) wins.
        if (session()->has('current_branch_id')) {
            $branchId = session('current_branch_id');
            if ($branchId) {
                $this->limitTo($builder, $model, collect([$branchId]));
            }

            return;
        }

        // 2. Fallback for stateless contexts (Sanctum API): restrict to the
        //    authenticated user's assigned branches. Users without any
        //    assignment keep legacy global visibility.
        $user = Auth::user();

        if ($user instanceof User) {
            $accessible = $user->accessibleBranchIds();

            if ($accessible->isNotEmpty()) {
                $this->limitTo($builder, $model, $accessible);
            }
        }
    }

    private function limitTo(Builder $builder, Model $model, $branchIds): void
    {
        $table = $model->getTable();

        $builder->where(function (Builder $q) use ($table, $branchIds) {
            $q->whereNull("{$table}.branch_id")
                ->orWhereIn("{$table}.branch_id", $branchIds);
        });
    }
}
