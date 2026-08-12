<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['pos_session_id', 'denomination', 'count', 'subtotal'])]
class CashDenomination extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'denomination' => 'integer',
            'count' => 'integer',
            'subtotal' => 'decimal:2',
        ];
    }

    public function posSession(): BelongsTo
    {
        return $this->belongsTo(PosSession::class);
    }
}
