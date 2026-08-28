<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SellReturn;
use App\Services\AutoJournalService;
use App\Services\DocumentNumberService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SellReturnController extends Controller
{
    public function index(Request $request)
    {
        $returns = SellReturn::query()
            ->with(['customer', 'items'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $q->where('return_number', 'like', "%{$request->search}%")
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$request->search}%"));
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('sell-returns.index', compact('returns'));
    }

    public function create()
    {
        $sales = Sale::with('customer')->orderByDesc('id')->limit(100)->get();
        $invoices = Invoice::with('customer')->orderByDesc('id')->limit(100)->get();
        $customers = Customer::orderBy('name')->get();
        // Preview only — the real number is consumed atomically at store().
        $returnNumber = DocumentNumberService::peek(DocumentNumberService::SELL_RETURNS, 'RET', 'Ymd', 4);

        return view('sell-returns.create', compact('sales', 'invoices', 'customers', 'returnNumber'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateData($request);

        usort($validated['items'], fn ($a, $b) => $a['product_id'] <=> $b['product_id']);

        try {
            $sellReturn = DB::transaction(function () use ($validated) {
                $items = $validated['items'];
                unset($validated['items']);

                $customerId = $validated['customer_id'];

                if (! empty($validated['sale_id'])) {
                    $customerId = Sale::find($validated['sale_id'])?->customer_id;
                } elseif (! empty($validated['invoice_id'])) {
                    $customerId = Invoice::find($validated['invoice_id'])?->customer_id;
                }

                if (! $customerId) {
                    throw new \RuntimeException('Customer wajib diisi atau pilih penjualan/invoice terkait.');
                }

                $refundAmount = $this->sumTotal($items);

                $sellReturn = SellReturn::create(array_merge($validated, [
                    'return_number' => DocumentNumberService::generate(DocumentNumberService::SELL_RETURNS, 'RET', 'Ymd', 4),
                    'customer_id' => $customerId,
                    'refund_amount' => $refundAmount,
                    'status' => 'completed',
                    'created_by' => auth()->id(),
                ]));

                $costAmount = 0.0;

                foreach ($items as $item) {
                    $sellReturn->items()->create([
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'unit_price' => round((float) $item['unit_price'], 2),
                        'total_price' => round((float) $item['quantity'] * (float) $item['unit_price'], 2),
                    ]);

                    $this->addStock($item['product_id'], $item['quantity'], $sellReturn);

                    $product = Product::withoutGlobalScopes()->find($item['product_id']);
                    $costAmount += (float) ($product?->cost_price ?? 0) * (float) $item['quantity'];
                }

                // Reverse revenue + COGS of the original sale.
                app(AutoJournalService::class)->journalSellReturn($sellReturn, $refundAmount, round($costAmount, 2));

                return $sellReturn;
            });
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        ActivityLog::record('sell-return.create', $sellReturn, "Retur penjualan {$sellReturn->return_number} dibuat");

        return redirect()->route('sell-returns.show', $sellReturn)
            ->with('success', 'Retur penjualan '.$sellReturn->return_number.' berhasil dibuat. Stok telah dikembalikan.');
    }

    public function show(SellReturn $sellReturn)
    {
        $sellReturn->load(['sale', 'invoice', 'customer', 'creator', 'items.product']);

        return view('sell-returns.show', compact('sellReturn'));
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'sale_id' => 'nullable|exists:sales,id',
            'invoice_id' => 'nullable|exists:invoices,id',
            'customer_id' => 'nullable|exists:customers,id',
            'return_date' => 'required|date',
            'reason' => 'required|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);
    }

    private function sumTotal(array $items): float
    {
        return round((float) collect($items)->sum(function ($item) {
            return (float) $item['quantity'] * (float) $item['unit_price'];
        }), 2);
    }

    private function addStock(int $productId, $quantity, SellReturn $sellReturn): void
    {
        $quantity = round((float) $quantity, 2);
        if ($quantity <= 0) {
            return;
        }

        StockService::increment(
            $productId,
            $quantity,
            'sell_return',
            'Retur penjualan #'.$sellReturn->return_number,
            SellReturn::class,
            $sellReturn->id,
        );
    }
}
