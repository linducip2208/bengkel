<?php

namespace App\Models;

use App\Traits\HasBranchScope;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['branch_id', 'name', 'specialty', 'phone', 'email', 'address', 'is_active', 'notes'])]
class Subcontractor extends Model
{
    use HasBranchScope, SoftDeletes;
    protected $casts = ['is_active' => 'boolean'];
    public function jobs(): HasMany { return $this->hasMany(SubcontractorJob::class); }
}
