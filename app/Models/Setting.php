<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
#[Fillable(['key', 'value', 'group'])]
class Setting extends Model
{
    use HasFactory;

    protected $table = 'settings';

    protected function casts(): array
    {
        return [];
    }
}
