<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['ip', 'label', 'is_active'])]
class IpWhitelist extends Model
{
    protected $casts = ['is_active' => 'boolean'];
}
