<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerRequest;
use App\Models\Customer;
use App\Services\CustomerService;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function __construct(protected CustomerService $service) {}

    public function index(Request $request)
    {
        $customers = $this->service->list($request->only(['search', 'per_page']));
        return view('customers.index', compact('customers'));
    }

    public function create()
    {
        return view('customers.create');
    }

    public function store(CustomerRequest $request)
    {
        $this->service->create($request->validated());
        return redirect()->route('customers.index')->with('success', 'Pelanggan berhasil ditambahkan.');
    }

    public function show($id)
    {
        $customer = Customer::with(['loyaltyTransactions', 'warrantyClaims'])->findOrFail($id);
        $stats = $this->service->getWithStats($customer);
        $vehicles = $customer->vehicles()->with(['vehicleType', 'vehicleBrand'])->latest()->get();
        $services = $customer->services()->with(['repairCategory'])->latest()->get();
        $invoices = $customer->invoices()->latest()->get();
        $loyaltyTransactions = $customer->loyaltyTransactions()->latest()->get();
        $warrantyClaims = $customer->warrantyClaims()->latest()->get();
        return view('customers.show', compact('customer', 'stats', 'vehicles', 'services', 'invoices', 'loyaltyTransactions', 'warrantyClaims'));
    }

    public function edit($id)
    {
        $customer = Customer::findOrFail($id);
        return view('customers.edit', compact('customer'));
    }

    public function update(CustomerRequest $request, $id)
    {
        $customer = Customer::findOrFail($id);
        $this->service->update($customer, $request->validated());
        return redirect()->route('customers.index')->with('success', 'Pelanggan berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $customer = Customer::findOrFail($id);
        $this->service->delete($customer);
        return redirect()->route('customers.index')->with('success', 'Pelanggan berhasil dihapus.');
    }

    public function import(Request $request)
    {
        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt'],
        ]);

        $file = $request->file('csv_file');
        $handle = fopen($file->getRealPath(), 'r');
        $headers = fgetcsv($handle);
        $rows = [];
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) === count($headers)) {
                $rows[] = array_combine($headers, $row);
            }
        }
        fclose($handle);

        $imported = $this->service->importFromCsv($rows);
        return redirect()->route('customers.index')->with('success', "{$imported} pelanggan berhasil diimport.");
    }
}
