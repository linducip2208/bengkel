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
            'dp_amount' => 'decimal:2',
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

    /**
     * Uang muka yang sudah diterima di awal (kolom invoices.dp_amount).
     * Mengurangi sisa tagihan bersama paid_amount dari payment_records.
     */
    public function dpValue(): float
    {
        return max(0, (float) ($this->dp_amount ?? 0));
    }

    /** Total yang sudah tertutup = pembayaran tercatat + DP. */
    public function collectedValue(): float
    {
        return round((float) ($this->paid_amount ?? 0) + $this->dpValue(), 2);
    }

    /** Sisa tagihan setelah dikurangi pembayaran tercatat dan DP. */
    public function remainingValue(): float
    {
        return max(round((float) ($this->grand_total ?? 0) - $this->collectedValue(), 2), 0);
    }

    public static function resolvePaymentStatus(float $collected, float $grandTotal): int
    {
        if ($collected >= $grandTotal - 0.009 && $grandTotal > 0) {
            return 2;
        }
        if ($collected > 0.009) {
            return 1;
        }

        return 0;
    }

    public static function resolveDpStatus(float $dpAmount, float $grandTotal): string
    {
        if ($dpAmount <= 0.009) {
            return 'none';
        }
        if ($dpAmount >= $grandTotal - 0.009 && $grandTotal > 0) {
            return 'full_paid';
        }

        return 'dp_paid';
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
