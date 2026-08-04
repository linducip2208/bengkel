<?php

namespace App\Services;

use App\Http\Requests\ServiceRequest;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\RepairCategory;
use App\Models\Service;
use App\Models\User;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ServiceService extends BaseService
{
    public function index(Request $request)
    {
        $services = Service::with(['customer', 'vehicle', 'repairCategory', 'technicians'])
            ->when($request->filled('status') && $request->status !== 'all', function ($q) use ($request) {
                $q->where('done_status', $request->status);
            })
            ->when($request->filled('date_from'), function ($q) use ($request) {
                $q->whereDate('service_date', '>=', $request->date_from);
            })
            ->when($request->filled('date_to'), function ($q) use ($request) {
                $q->whereDate('service_date', '<=', $request->date_to);
            })
            ->when($request->filled('customer_search'), function ($q) use ($request) {
                $q->whereHas('customer', fn($c) => $c->where('name', 'like', '%' . $request->customer_search . '%'));
            })
            ->when($request->filled('technician'), function ($q) use ($request) {
                $q->whereHas('technicians', fn($t) => $t->where('users.id', $request->technician));
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $stats = $this->getStats();
        $technicians = User::role('mekanik')->get();

        return view('services.index', compact('services', 'stats', 'technicians'));
    }

    public function create()
    {
        $repairCategories = RepairCategory::orderBy('repair_category_name')->get();
        $technicians = User::role('mekanik')->get();

        return view('services.create', compact('repairCategories', 'technicians'));
    }

    public function store(Request $request)
    {
        $validated = app(ServiceRequest::class)->validated();

        // Conflict detection: check technician availability
        $warnings = [];
        $techIds = $validated['assign_to'] ?? [];
        if (!empty($techIds) && !empty($validated['service_date'])) {
            $conflicts = \App\Models\ServiceTechnician::whereIn('user_id', $techIds)
                ->whereHas('service', fn($q) => $q->whereDate('service_date', \Carbon\Carbon::parse($validated['service_date'])->toDateString())->where('done_status', '<', 2))
                ->with('user')->get();
            if ($conflicts->isNotEmpty()) {
                $names = $conflicts->pluck('user.name')->unique()->implode(', ');
                $warnings[] = "Teknisi {$names} sudah punya service di tanggal yang sama.";
            }
        }

        $service = DB::transaction(function () use ($validated, $request) {
            $validated['job_no'] = $this->generateJobNo();
            $validated['done_status'] = $validated['done_status'] ?? 1;
            $validated['created_by'] = auth()->id();
            if (($validated['done_status'] ?? 0) >= 1) {
                $validated['started_at'] = $validated['started_at'] ?? now();
            }

            $technicianIds = $validated['assign_to'] ?? [];
            unset($validated['assign_to']);

            $service = Service::create($validated);

            if (!empty($technicianIds)) {
                $service->technicians()->sync($technicianIds);
                $service->update(['assign_to' => $technicianIds[0]]);
            }

            if ($request->has('products') && is_array($request->products)) {
                $productService = app(ProductService::class);
                foreach ($request->products as $productData) {
                    $product = Product::find($productData['id'] ?? $productData['product_id'] ?? null);
                    if ($product && ($productData['quantity'] ?? 0) > 0) {
                        $productService->useInService($product, (int) $productData['quantity']);
                    }
                }
            }

            return $service;
        });

        return redirect()
            ->route('services.show', $service)
            ->with('success', 'Servis berhasil dibuat.')
            ->with('warnings', $warnings);
    }

    public function show($id)
    {
        $service = Service::with([
            'customer', 'vehicle.vehicleType', 'vehicle.vehicleBrand',
            'repairCategory', 'technicians', 'jobcardDetail',
            'serviceObservationPoints.observationPoint.observationType',
            'images', 'checkoutResults.checkoutCategory', 'invoice',
        ])->findOrFail($id);

        $nextService = $service->jobcardDetail
            ? app(JobcardService::class)->calculateNextService($service)
            : null;

        return view('services.show', compact('service', 'nextService'));
    }

    public function edit($id)
    {
        $service = Service::with(['technicians'])->findOrFail($id);
        $repairCategories = RepairCategory::orderBy('repair_category_name')->get();
        $technicians = User::role('mekanik')->get();
        $selectedCustomer = $service->customer;
        $selectedVehicle = $service->vehicle;

        return view('services.edit', compact('service', 'repairCategories', 'technicians', 'selectedCustomer', 'selectedVehicle'));
    }

    public function update(Request $request, $id)
    {
        $service = Service::findOrFail($id);
        $validated = app(ServiceRequest::class)->validated();

        DB::transaction(function () use ($service, $validated) {
            $technicianIds = $validated['assign_to'] ?? [];
            unset($validated['assign_to']);

            $service->update($validated);

            if (!empty($technicianIds)) {
                $service->technicians()->sync($technicianIds);
                $service->update(['assign_to' => $technicianIds[0]]);
            } else {
                $service->technicians()->detach();
                $service->update(['assign_to' => null]);
            }
        });

        return redirect()
            ->route('services.show', $service)
            ->with('success', 'Servis berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $service = Service::findOrFail($id);
        $service->delete();

        return redirect()
            ->route('services.index')
            ->with('success', 'Servis berhasil dihapus.');
    }

    public function complete(Request $request, $id)
    {
        $service = Service::findOrFail($id);

        DB::transaction(function () use ($service) {
            $service->update([
                'done_status' => 2,
                'workflow_status' => 5,
                'completed_at' => now(),
            ]);

            if ($service->jobcardDetail) {
                $service->jobcardDetail->update([
                    'out_date' => now(),
                    'done_status' => 2,
                ]);
            }

            if ($service->jobcardDetail?->odometer_out) {
                $service->vehicle->update(['odometer' => $service->jobcardDetail->odometer_out]);
            }

            Invoice::create([
                'invoice_number' => 'INV-' . date('Ym') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT),
                'customer_id' => $service->customer_id,
                'service_id' => $service->id,
                'payment_status' => 0,
                'total_amount' => $service->charge,
                'grand_total' => $service->charge,
                'invoice_date' => now(),
                'invoice_type' => 'service',
                'created_by' => auth()->id() ?? 1,
            ]);
        });

        return redirect()
            ->route('services.show', $service)
            ->with('success', 'Servis selesai.');
    }

    public function startService($id)
    {
        $service = Service::findOrFail($id);
        $service->update([
            'done_status' => 1,
            'workflow_status' => 1,
            'started_at' => $service->started_at ?? now(),
            'checked_in_at' => $service->checked_in_at ?? now(),
        ]);

        return back()->with('success', 'Servis dimulai. Timer berjalan.');
    }

    public function advanceWorkflow($id)
    {
        $service = Service::findOrFail($id);
        $nextStatus = ($service->workflow_status ?? 0) + 1;
        if ($nextStatus > 5) $nextStatus = 5;

        $data = ['workflow_status' => $nextStatus];
        if ($nextStatus >= 5) {
            $data['done_status'] = 2;
            $data['completed_at'] = now();
        } elseif ($nextStatus >= 2) {
            $data['done_status'] = 1;
            $data['started_at'] = $data['started_at'] ?? now();
        }
        if ($nextStatus >= 3) $data['qc_passed_at'] = now();

        $service->update($data);

        $labels = [0=>'Pending',1=>'Checked In',2=>'In Progress',3=>'QC',4=>'Ready',5=>'Delivered'];
        return back()->with('success', 'Status: ' . $labels[$nextStatus]);
    }

    public function uploadImage(Request $request, $id)
    {
        $service = Service::findOrFail($id);

        $request->validate([
            'image' => 'required|image|max:5120',
            'type' => 'required|in:before,after,progress',
            'caption' => 'nullable|string|max:255',
        ]);

        $path = $request->file('image')->store('service-images/' . $service->id, 'public');

        $service->images()->create([
            'image_path' => $path,
            'type' => $request->type,
            'caption' => $request->caption,
        ]);

        return back()->with('success', 'Foto berhasil diupload.');
    }

    public function searchCustomers(Request $request)
    {
        $customers = Customer::where('name', 'like', '%' . $request->q . '%')
            ->orWhere('phone', 'like', '%' . $request->q . '%')
            ->limit(20)
            ->get(['id', 'name', 'phone']);

        return response()->json($customers);
    }

    public function vehiclesByCustomer($customer)
    {
        $customer = Customer::findOrFail($customer);
        $vehicles = $customer->vehicles()
            ->with('vehicleBrand:id,vehicle_brand', 'vehicleType:id,vehicle_type')
            ->get();

        return response()->json($vehicles);
    }

    public function generateJobNo(): string
    {
        $prefix = 'BP-' . now()->format('Ymd') . '-';
        $latest = Service::withTrashed()
            ->where('job_no', 'like', $prefix . '%')
            ->orderBy('job_no', 'desc')
            ->first();

        if ($latest) {
            $lastNumber = (int) substr($latest->job_no, -3);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return $prefix . str_pad((string) $nextNumber, 3, '0', STR_PAD_LEFT);
    }

    public function getStats(): array
    {
        return [
            'total_open' => Service::open()->count(),
            'in_progress' => Service::inProgress()->count(),
            'done_today' => Service::done()->today()->count(),
        ];
    }
}
