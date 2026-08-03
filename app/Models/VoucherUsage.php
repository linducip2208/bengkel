<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['voucher_id', 'invoice_id', 'customer_id', 'discount_applied'])]
class VoucherUsage extends Model
{
    protected $casts = ['discount_applied' => 'decimal:2'];

    public function voucher(): BelongsTo { return $this->belongsTo(Voucher::class); }
    public function invoice(): BelongsTo { return $this->belongsTo(Invoice::class); }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
}
