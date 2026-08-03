<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'invoice_id', 'payment_gateway_id', 'token', 'external_id',
    'amount', 'status', 'payment_url', 'qr_string',
    'gateway_response', 'paid_at', 'expires_at',
])]
class PaymentLink extends Model
{
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'gateway_response' => 'array',
            'paid_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function invoice(): BelongsTo { return $this->belongsTo(Invoice::class); }
    public function gateway(): BelongsTo { return $this->belongsTo(PaymentGateway::class, 'payment_gateway_id'); }

    public function isExpired(): bool { return $this->expires_at && $this->expires_at->isPast(); }
}
