<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\GatePass;
use App\Models\Service;
use App\Models\Vehicle;
use App\Services\DocumentNumberService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class GatePassController extends Controller
{
    public function index()
    {
        $gatePasses = GatePass::with(['vehicle.customer', 'service'])
            ->latest()
            ->paginate(20);

        return view('gate-passes.index', compact('gatePasses'));
    }

    public function create()
    {
        $vehicles = Vehicle::with('customer')->orderBy('number_plate')->get();
        $services = Service::with('customer')->whereIn('done_status', [0, 1])->latest()->get();

        return view('gate-passes.create', compact('vehicles', 'services'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'service_id' => 'nullable|exists:services,id',
            'entry_date' => 'required|date',
            'driver_name' => 'nullable|string|max:255',
            'driver_phone' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
        ]);

        $vehicle = Vehicle::find($validated['vehicle_id']);
        $validated['customer_id'] = $vehicle->customer_id;
        $validated['gate_pass_no'] = DocumentNumberService::generate(DocumentNumberService::GATE_PASSES, 'GP', 'Ymd', 4);
        $validated['status'] = 'in';
        $validated['created_by'] = auth()->id();

        GatePass::create($validated);

        return redirect()->route('gate-passes.index')
            ->with('success', 'Gate pass created successfully.');
    }

    public function show(GatePass $gatePass)
    {
        $gatePass->load(['vehicle.customer', 'service']);

        return view('gate-passes.show', compact('gatePass'));
    }

    public function edit(GatePass $gatePass)
    {
        $vehicles = Vehicle::with('customer')->orderBy('number_plate')->get();
        $services = Service::with('customer')->whereIn('done_status', [0, 1])->latest()->get();

        return view('gate-passes.edit', compact('gatePass', 'vehicles', 'services'));
    }

    public function update(Request $request, GatePass $gatePass)
    {
        $validated = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'service_id' => 'nullable|exists:services,id',
            'entry_date' => 'required|date',
            'driver_name' => 'nullable|string|max:255',
            'driver_phone' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
        ]);

        $gatePass->update($validated);

        return redirect()->route('gate-passes.index')->with('success', 'Gate pass updated');
    }

    public function destroy(GatePass $gatePass)
    {
        $gatePass->delete();

        return redirect()->route('gate-passes.index')->with('success', 'Gate pass deleted');
    }

    public function print(GatePass $gatePass)
    {
        $gatePass->load(['vehicle.customer', 'service']);
        $pdf = Pdf::loadView('gate-passes.print', compact('gatePass'));

        return $pdf->download("gate-pass-{$gatePass->gate_pass_no}.pdf");
    }

    public function markExit(GatePass $gatePass)
    {
        $gatePass->update([
            'exit_date' => now(),
            'status' => 'out',
        ]);

        return redirect()->route('gate-passes.index')
            ->with('success', 'Vehicle exit recorded.');
    }
}
