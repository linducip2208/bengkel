<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class BranchScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (!session()->has('current_branch_id')) {
            return;
        }

        $branchId = session('current_branch_id');
        if (!$branchId) {
            return;
        }

        $builder->where(function (Builder $q) use ($model, $branchId) {
            $q->whereNull($model->getTable() . '.branch_id')
              ->orWhere($model->getTable() . '.branch_id', $branchId);
        });
    }
}
