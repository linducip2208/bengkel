<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\WarrantyClaim;
use Illuminate\Support\Carbon;

/**
 * Warranty claim business rules shared by the web and API layers.
 *
 * Enforces a proper state machine so a claim cannot skip/regress arbitrarily,
 * and validates on creation that the claimed item is actually eligible for a
 * warranty claim (has a warranty expiration and the claim is filed within it).
 */
class WarrantyClaimService
{
    /** Allowed forward transitions; terminal states accept no further moves. */
    public const TRANSITIONS = [
        'submitted' => ['approved', 'rejected'],
        'approved' => ['resolved'],
        'rejected' => ['resolved'],
        'resolved' => [],
    ];

    public static function allowedFrom(string $current): array
    {
        return self::TRANSITIONS[$current] ?? [];
    }

    public function create(array $data): WarrantyClaim
    {
        $item = InvoiceItem::with('invoice')->findOrFail($data['invoice_item_id']);
        $claimDate = Carbon::parse($data['claim_date'] ?? now());

        $this->assertEligible($item, $claimDate);
        /** @var Invoice|null $invoice */
        $invoice = $item->invoice;

        return WarrantyClaim::create($data + [
            'customer_id' => $invoice?->customer_id,
            'status' => 'submitted',
        ]);
    }

    public function transition(WarrantyClaim $claim, string $to, ?string $resolution = null): WarrantyClaim
    {
        $allowed = self::allowedFrom((string) $claim->status);

        if (! in_array($to, $allowed, true)) {
            throw new \RuntimeException(
                "Transisi status tidak valid ({$claim->status} → {$to})."
            );
        }

        $claim->update([
            'status' => $to,
            'resolution' => $resolution !== null ? $resolution : $claim->resolution,
        ]);

        ActivityLog::record('warranty.transition', $claim, "Status klaim {$claim->id}: {$claim->status} → {$to}");

        return $claim->fresh();
    }

    private function assertEligible(InvoiceItem $item, Carbon $claimDate): void
    {
        /** @var Product|null $product */
        $product = $item->product;
        /** @var Invoice|null $invoice */
        $invoice = $item->invoice;

        // No warranty at all → not claimable.
        $hasWarranty = $item->warranty_expiry !== null
            || ($product && ! empty($product->warranty));

        if (! $hasWarranty) {
            throw new \RuntimeException('Item ini tidak memiliki garansi.');
        }

        $expiry = $item->warranty_expiry;
        if (! $expiry && $product) {
            // Fall back to the product-level warranty period counted from the
            // item's sold date (or the invoice date).
            $reference = $item->sold_date ?? $invoice?->invoice_date;
            $expiry = $product->getWarrantyExpiryDate(
                $reference ? Carbon::parse($reference)->toDateString() : null
            );
        }

        if ($expiry && $claimDate->gt(Carbon::parse($expiry))) {
            throw new \RuntimeException('Klaim diajukan setelah masa garansi berakhir.');
        }
    }
}
