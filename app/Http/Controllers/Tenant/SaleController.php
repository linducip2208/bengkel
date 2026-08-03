<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\SaleRequest;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\Vehicle;
use App\Services\SaleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SaleController extends Controller
{
    public function __construct(
        protected SaleService $saleService
    ) {}

    public function index(Request $request): View
    {
        $sales = Sale::query()
            ->with(['customer', 'vehicle'])
            ->when($request->search, fn($q) => $q->whereHas('customer', fn($c) => $c->where('name', 'like', "%{$request->search}%")))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('sales.index', compact('sales'));
    }

    public function create(): View
    {
        $customers = Customer::orderBy('name')->get();
        $vehicles = Vehicle::with(['vehicleType', 'vehicleBrand'])->orderBy('number_plate')->get();

        return view('sales.create', compact('customers', 'vehicles'));
    }

    public function store(SaleRequest $request): RedirectResponse
    {
        $sale = $this->saleService->create($request->validated());

        return redirect()->route('sales.show', $sale)
            ->with('success', 'Penjualan kendaraan berhasil dicatat.');
    }

    public function show(Sale $sale): View
    {
        $sale->load(['customer', 'vehicle.vehicleType', 'vehicle.vehicleBrand', 'invoices.paymentRecords']);

        return view('sales.show', compact('sale'));
    }

    public function edit(Sale $sale): View
    {
        $customers = Customer::orderBy('name')->get();
        $vehicles = Vehicle::with(['vehicleType', 'vehicleBrand'])->orderBy('number_plate')->get();

        return view('sales.edit', compact('sale', 'customers', 'vehicles'));
    }

    public function update(SaleRequest $request, Sale $sale): RedirectResponse
    {
        $sale->update($request->validated());

        return redirect()->route('sales.show', $sale)
            ->with('success', 'Penjualan berhasil diperbarui.');
    }

    public function destroy(Sale $sale): RedirectResponse
    {
        $sale->delete();

        return redirect()->route('sales.index')
            ->with('success', 'Penjualan berhasil dihapus.');
    }
}
