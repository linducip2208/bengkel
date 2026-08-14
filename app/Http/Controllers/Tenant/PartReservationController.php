<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\PartReservation;
use App\Models\Product;
use App\Models\Service;
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
        $available = $this->availableStock($product);

        if ($available < $quantity) {
            return back()->withErrors([
                'quantity' => "Stok tersedia \"{$product->name}\" tidak cukup: tersedia {$available}, diminta {$quantity}.",
            ])->withInput();
        }

        PartReservation::create([
            'service_id' => $service->id,
            'product_id' => $product->id,
            'quantity' => $quantity,
            'reserved_by' => auth()->id(),
            'status' => 'reserved',
            'notes' => $validated['notes'] ?? null,
        ]);

        return back()->with('success', "Parts \"{$product->name}\" berhasil direservasi.");
    }

    public function release(PartReservation $reservation)
    {
        if ($reservation->status !== 'reserved') {
            return back()->withErrors(['error' => 'Reservasi sudah tidak aktif.']);
        }

        $reservation->update(['status' => 'released']);

        return back()->with('success', 'Reservasi parts dilepas.');
    }

    public function consume(PartReservation $reservation)
    {
        if ($reservation->status !== 'reserved') {
            return back()->withErrors(['error' => 'Reservasi sudah tidak aktif.']);
        }

        DB::transaction(function () use ($reservation) {
            app(ProductService::class)->useInService(
                $reservation->product,
                (int) $reservation->quantity,
                $reservation->service_id
            );

            $reservation->update(['status' => 'consumed']);
        });

        return back()->with('success', 'Parts dipakai dan stok dikurangi.');
    }

    private function availableStock(Product $product): int
    {
        return max(0, (int) $product->current_stock - (int) $product->reservedQuantity());
    }
}
