<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\LoyaltyTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LoyaltyController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::query()
            ->select('customers.*')
            ->withCount('invoices')
            ->orderBy('loyalty_points', 'desc');
        if ($request->filled('tier')) $query->where('membership_tier', $request->tier);
        if ($request->filled('search')) $query->where('name', 'like', "%{$request->search}%");
        $customers = $query->paginate(20)->withQueryString();

        $summary = [
            'total_points' => Customer::sum('loyalty_points'),
            'top_customers' => Customer::orderBy('loyalty_points', 'desc')->limit(5)->get(['id', 'name', 'loyalty_points', 'membership_tier']),
        ];

        return view('loyalty.index', compact('customers', 'summary'));
    }

    public function show(Customer $customer)
    {
        $transactions = LoyaltyTransaction::where('customer_id', $customer->id)->with('creator')->latest()->paginate(30);
        return view('loyalty.show', compact('customer', 'transactions'));
    }

    public function adjust(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'points' => 'required|integer',
            'description' => 'required|string|max:255',
        ]);

        DB::transaction(function () use ($validated, $customer) {
            LoyaltyTransaction::create([
                'customer_id' => $customer->id,
                'points' => $validated['points'],
                'type' => 'adjust',
                'description' => $validated['description'],
                'created_by' => auth()->id(),
            ]);
            $customer->increment('loyalty_points', $validated['points']);
            $this->updateTier($customer);
        });

        return back()->with('success', 'Poin disesuaikan.');
    }

    public static function earnFromInvoice(\App\Models\Invoice $invoice, int $pointsPer1000Rupiah = 1): void
    {
        if (!$invoice->customer_id) return;
        $points = (int) floor($invoice->grand_total / 1000) * $pointsPer1000Rupiah;
        if ($points <= 0) return;

        DB::transaction(function () use ($invoice, $points) {
            LoyaltyTransaction::create([
                'customer_id' => $invoice->customer_id,
                'reference_type' => \App\Models\Invoice::class,
                'reference_id' => $invoice->id,
                'points' => $points,
                'type' => 'earn',
                'description' => "Earn dari invoice {$invoice->invoice_number}",
            ]);
            $customer = Customer::find($invoice->customer_id);
            if ($customer) {
                $customer->increment('loyalty_points', $points);
                self::updateTierStatic($customer);
            }
        });
    }

    private function updateTier(Customer $customer): void
    {
        self::updateTierStatic($customer);
    }

    public static function updateTierStatic(Customer $customer): void
    {
        $pts = $customer->loyalty_points ?? 0;
        $tier = match (true) {
            $pts >= 10000 => 'platinum',
            $pts >= 5000 => 'gold',
            $pts >= 1000 => 'silver',
            default => 'bronze',
        };
        if ($customer->membership_tier !== $tier) {
            $customer->update(['membership_tier' => $tier]);
        }
    }
}
