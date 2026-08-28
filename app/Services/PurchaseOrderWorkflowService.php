<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use Illuminate\Support\Facades\DB;

class PurchaseOrderWorkflowService
{
    public function transition(PurchaseOrder $purchaseOrder, string $action): PurchaseOrder
    {
        $target = match ($action) {
            'submit' => 'submitted',
            'approve' => 'approved',
            'close' => 'closed',
            default => null,
        };
        $allowedFrom = match ($action) {
            'submit' => ['draft'],
            'approve' => ['submitted'],
            'close' => ['received'],
            default => [],
        };

        return DB::transaction(function () use ($purchaseOrder, $target, $allowedFrom) {
            $locked = PurchaseOrder::query()->whereKey($purchaseOrder->id)->lockForUpdate()->firstOrFail();
            if (! $target || ! in_array($locked->status, $allowedFrom, true)) {
                throw new \RuntimeException('Transisi status PO tidak valid.');
            }
            $locked->update(['status' => $target]);

            return $locked->fresh();
        });
    }
}
