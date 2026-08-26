<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\PartReservation;
use App\Models\Product;
use App\Models\Service;
use App\Models\StockRecord;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PartReservationController extends Controller
{
    public function store(Request $request, Service $service)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:255',
        ]);

        $product = Product::findOrFail($validated['product_id']);
        $quantity = (int) $validated['quantity'];

        try {
            DB::transaction(function () use ($product, $quantity, $service, $validated) {
                // Lock the stock row and recompute availability inside the
                // transaction so two concurrent reserves cannot both claim
                // the same free units.
                $record = StockRecord::withoutGlobalScopes()
                    ->where('product_id', $product->id)
                    ->lockForUpdate()
                    ->first();
                $onHand = (int) ($record->quantity ?? 0);

                $reserved = (int) PartReservation::where('product_id', $product->id)
                    ->where('status', 'reserved')
                    ->lockForUpdate()
                    ->sum('quantity');

                $available = max(0, $onHand - $reserved);
                if ($available < $quantity) {
                    throw new \RuntimeException(
                        "Stok tersedia \"{$product->name}\" tidak cukup: tersedia {$available}, diminta {$quantity}."
                    );
                }

                PartReservation::create([
                    'service_id' => $service->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'reserved_by' => auth()->id(),
                    'status' => 'reserved',
                    'notes' => $validated['notes'] ?? null,
                ]);
            });
        } catch (\RuntimeException $e) {
            return back()->withErrors(['quantity' => $e->getMessage()])->withInput();
        }

        return back()->with('success', "Parts \"{$product->name}\" berhasil direservasi.");
    }

    public function release(PartReservation $reservation)
    {
        // Atomic conditional update — only one caller can flip an active
        // reservation to released.
        $updated = PartReservation::whereKey($reservation->id)
            ->where('status', 'reserved')
            ->update(['status' => 'released']);

        if (! $updated) {
            return back()->withErrors(['error' => 'Reservasi sudah tidak aktif.']);
        }

        return back()->with('success', 'Reservasi parts dilepas.');
    }

    public function consume(PartReservation $reservation)
    {
        DB::transaction(function () use ($reservation) {
            // Lock the reservation row inside the tx so a concurrent second
            // consume aborts before touching stock.
            $locked = PartReservation::query()
                ->whereKey($reservation->id)
                ->where('status', 'reserved')
                ->lockForUpdate()
                ->first();

            if (! $locked) {
                throw new \RuntimeException('Reservasi sudah tidak aktif.');
            }

            app(ProductService::class)->useInService(
                $locked->product()->withoutGlobalScopes()->first(),
                (int) $locked->quantity,
                $locked->service_id
            );

            $locked->update(['status' => 'consumed']);
        });

        return back()->with('success', 'Parts dipakai dan stok dikurangi.');
    }
}
