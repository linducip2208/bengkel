<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'skill', 'level', 'notes'])]
class TechnicianSkill extends Model
{
    public const SKILLS = ['Engine', 'AC', 'Electrical', 'Body', 'Transmission', 'Brake'];

    public const LEVELS = ['basic', 'intermediate', 'expert'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
