<?php

namespace App\Models;

use App\Traits\HasBranchScope;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable(['invoice_number', 'customer_id', 'service_id', 'service_estimate_id', 'sale_id', 'vehicle_id', 'payment_method_id', 'payment_status', 'total_amount', 'discount', 'discount_type', 'discount_percent', 'tax_amount', 'grand_total', 'paid_amount', 'amount_received', 'dp_amount', 'dp_status', 'invoice_date', 'due_date', 'invoice_type', 'created_by', 'notes', 'payment_proof', 'public_token', 'idempotency_key', 'branch_id', 'pos_session_id'])]
class Invoice extends Model
{
    use HasBranchScope, HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'due_date' => 'date',
            'total_amount' => 'decimal:2',
            'discount' => 'decimal:2',
            'discount_percent' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'amount_received' => 'decimal:2',
        ];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function paymentRecords(): HasMany
    {
        return $this->hasMany(PaymentRecord::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function posSession(): BelongsTo
    {
        return $this->belongsTo(PosSession::class);
    }

    public function voucherUsages(): HasMany
    {
        return $this->hasMany(VoucherUsage::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function getStatusAttribute(): string
    {
        return match ((int) $this->payment_status) {
            2 => 'full_paid',
            1 => 'half_paid',
            default => 'unpaid',
        };
    }

    public function getSubtotalAttribute(): float
    {
        return (float) ($this->total_amount ?? 0);
    }

    public function getOrCreatePublicToken(): string
    {
        if (! empty($this->public_token)) {
            return $this->public_token;
        }

        $this->public_token = $this->generateUniquePublicToken();
        $this->save();

        return $this->public_token;
    }

    protected function generateUniquePublicToken(): string
    {
        do {
            $token = Str::random(32);
        } while (static::withoutGlobalScopes()->where('public_token', $token)->exists());

        return $token;
    }
}
