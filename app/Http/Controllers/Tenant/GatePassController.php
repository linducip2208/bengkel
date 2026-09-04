<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\GatePass;
use App\Models\Service;
use App\Models\Vehicle;
use App\Services\DocumentNumberService;
use App\Services\GatePassEligibilityService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class GatePassController extends Controller
{
    public function __construct(protected GatePassEligibilityService $eligibility) {}

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
        $services = $this->eligibility->eligibleServices()->get();

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

        $vehicle = Vehicle::findOrFail($validated['vehicle_id']);
        if (! empty($validated['service_id'])) {
            $service = Service::findOrFail($validated['service_id']);
            $this->eligibility->assertCanCreate($service, (int) $validated['vehicle_id']);
            $validated['vehicle_id'] = $service->vehicle_id;
            $validated['customer_id'] = $service->customer_id;
        } else {
            $validated['customer_id'] = $vehicle->customer_id;
        }
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
        $services = $this->eligibility->eligibleServices()->get();

        return view('gate-passes.edit', compact('gatePass', 'vehicles', 'services'));
    }

    public function update(Request $request, GatePass $gatePass)
    {
        abort_if($gatePass->status === 'out', 409, 'Gate pass yang sudah keluar tidak dapat diubah.');

        $validated = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'service_id' => 'nullable|exists:services,id',
            'entry_date' => 'required|date',
            'driver_name' => 'nullable|string|max:255',
            'driver_phone' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
        ]);

        if ($gatePass->service_id && empty($validated['service_id'])) {
            throw ValidationException::withMessages(['service_id' => 'Gate pass Service tidak dapat dilepas dari Service.']);
        }

        if (! empty($validated['service_id'])) {
            $service = Service::findOrFail($validated['service_id']);
            $this->eligibility->assertCanCreate($service, (int) $validated['vehicle_id']);
            $validated['vehicle_id'] = $service->vehicle_id;
            $validated['customer_id'] = $service->customer_id;
        } else {
            $validated['customer_id'] = Vehicle::findOrFail($validated['vehicle_id'])->customer_id;
        }
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
        if ($gatePass->status === 'out') {
            return redirect()->route('gate-passes.index')->with('info', 'Gate pass sudah diproses keluar.');
        }

        if ($gatePass->service_id) {
            $service = Service::findOrFail($gatePass->service_id);
            try {
                $this->eligibility->assertCanRelease($service);
            } catch (ValidationException $e) {
                return back()->withErrors($e->errors());
            }
        }

        $gatePass->update([
            'exit_date' => now(),
            'status' => 'out',
        ]);

        if ($gatePass->service_id) {
            $service->forceFill([
                'released_at' => $service->released_at ?? now(),
                'workflow_status' => max((int) $service->workflow_status, 11),
            ])->save();
            ActivityLog::record('vehicle.released', $gatePass, "Kendaraan service {$service->job_no} keluar");
            if ($service->completed_at === null) {
                $service->forceFill([
                    'completed_at' => now(),
                    'workflow_status' => 12,
                    'done_status' => 2,
                ])->save();
                ActivityLog::record('service.completed', $service, "Service {$service->job_no} selesai setelah kendaraan dirilis");
            }
        }

        return redirect()->route('gate-passes.index')
            ->with('success', 'Vehicle exit recorded.');
    }
}
