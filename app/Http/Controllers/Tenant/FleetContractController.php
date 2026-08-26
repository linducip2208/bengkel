<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\FleetContract;
use App\Models\Vehicle;
use Illuminate\Http\Request;

class FleetContractController extends Controller
{
    public function index(Request $request)
    {
        $query = FleetContract::with(['customer', 'vehicles.vehicle']);
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$s}%"));
            });
        }
        $contracts = $query->latest()->paginate(20)->withQueryString();

        return view('fleet-contracts.index', compact('contracts'));
    }

    public function create()
    {
        $customers = Customer::orderBy('name')->get();
        $vehicles = Vehicle::with('customer')->orderBy('number_plate')->get();

        return view('fleet-contracts.create', compact('customers', 'vehicles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'name' => 'required|string|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'service_interval_days' => 'nullable|integer|min:1',
            'service_interval_km' => 'nullable|integer|min:0',
            'notes' => 'nullable|string|max:5000',
            'is_active' => 'boolean',
            'vehicle_ids' => 'nullable|array',
            'vehicle_ids.*' => 'exists:vehicles,id',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $contract = FleetContract::create($validated);

        if (! empty($validated['vehicle_ids'])) {
            foreach ($validated['vehicle_ids'] as $vehicleId) {
                $contract->vehicles()->create(['vehicle_id' => $vehicleId]);
            }
        }

        ActivityLog::record('fleet-contract.create', $contract, "Kontrak fleet {$contract->name} dibuat");

        return redirect()->route('fleet-contracts.show', $contract)->with('success', 'Kontrak fleet berhasil dibuat.');
    }

    public function show(FleetContract $fleetContract)
    {
        $fleetContract->load(['customer', 'vehicles.vehicle.customer']);

        $intervalDays = $fleetContract->service_interval_days ?? 90;
        $vehiclesStatus = $fleetContract->vehicles->map(function ($cv) use ($intervalDays) {
            $vehicle = $cv->vehicle;
            $lastService = $vehicle?->services()->latest('service_date')->first();
            $lastDate = $lastService?->service_date;
            $dueDate = $lastDate ? $lastDate->copy()->addDays($intervalDays) : null;
            $daysOverdue = $dueDate ? now()->diffInDays($dueDate, false) : null;

            return [
                'vehicle' => $vehicle,
                'last_service_date' => $lastDate,
                'due_date' => $dueDate,
                'days_overdue' => $daysOverdue,
                'is_due' => $dueDate ? now()->greaterThanOrEqualTo($dueDate) : false,
            ];
        });

        return view('fleet-contracts.show', compact('fleetContract', 'vehiclesStatus'));
    }

    public function edit(FleetContract $fleetContract)
    {
        $fleetContract->load('vehicles');
        $customers = Customer::orderBy('name')->get();
        $vehicles = Vehicle::with('customer')->orderBy('number_plate')->get();
        $selectedVehicleIds = $fleetContract->vehicles->pluck('vehicle_id')->toArray();

        return view('fleet-contracts.edit', compact('fleetContract', 'customers', 'vehicles', 'selectedVehicleIds'));
    }

    public function update(Request $request, FleetContract $fleetContract)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'name' => 'required|string|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'service_interval_days' => 'nullable|integer|min:1',
            'service_interval_km' => 'nullable|integer|min:0',
            'notes' => 'nullable|string|max:5000',
            'is_active' => 'boolean',
            'vehicle_ids' => 'nullable|array',
            'vehicle_ids.*' => 'exists:vehicles,id',
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $vehicleIds = $validated['vehicle_ids'] ?? [];
        unset($validated['vehicle_ids']);

        $fleetContract->update($validated);

        $fleetContract->vehicles()->delete();
        foreach ($vehicleIds as $vehicleId) {
            $fleetContract->vehicles()->create(['vehicle_id' => $vehicleId]);
        }

        ActivityLog::record('fleet-contract.update', $fleetContract, "Kontrak fleet {$fleetContract->name} diperbarui");

        return redirect()->route('fleet-contracts.show', $fleetContract)->with('success', 'Kontrak fleet diperbarui.');
    }

    public function destroy(FleetContract $fleetContract)
    {
        ActivityLog::record('fleet-contract.delete', $fleetContract, "Kontrak fleet {$fleetContract->name} dihapus");
        $fleetContract->delete();

        return redirect()->route('fleet-contracts.index')->with('success', 'Kontrak fleet dihapus.');
    }

    public function dueVehicles()
    {
        $contracts = FleetContract::with(['customer', 'vehicles.vehicle.customer'])
            ->where('is_active', true)
            ->get();

        $due = collect();
        foreach ($contracts as $contract) {
            $intervalDays = $contract->service_interval_days ?? 90;
            foreach ($contract->vehicles as $cv) {
                $vehicle = $cv->vehicle;
                if (! $vehicle) {
                    continue;
                }
                $lastService = $vehicle->services()->latest('service_date')->first();
                $lastDate = $lastService?->service_date;
                $dueDate = $lastDate
                    ? $lastDate->copy()->addDays($intervalDays)
                    : ($contract->start_date ? $contract->start_date->copy()->addDays($intervalDays) : null);

                $due->push([
                    'contract' => $contract,
                    'vehicle' => $vehicle,
                    'last_service_date' => $lastDate,
                    'due_date' => $dueDate,
                    'days_overdue' => $dueDate ? now()->diffInDays($dueDate, false) : null,
                    'is_due' => $dueDate ? now()->greaterThanOrEqualTo($dueDate) : false,
                ]);
            }
        }

        $due = $due->filter(fn ($d) => $d['is_due'])->sortBy('due_date')->values();

        return view('fleet-contracts.due', compact('due'));
    }
}
