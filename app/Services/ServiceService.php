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
        $validated = \App\Http\Requests\ServiceRequest::createFrom($request)->validated();

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
                $this->calculateCommissions($service, $technicianIds);
            }

            if ($request->has('products') && is_array($request->products)) {
                $productService = app(ProductService::class);
                foreach ($request->products as $productData) {
                    $product = Product::find($productData['id'] ?? $productData['product_id'] ?? null);
                    if ($product && ($productData['quantity'] ?? 0) > 0) {
                        $productService->useInService($product, (int) $productData['quantity'], $service->id);
                    }
                }
            }

            return $service;
        });

        \App\Models\ActivityLog::record('service.create', $service, "Service {$service->job_no} dibuat");
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
        $validated = \App\Http\Requests\ServiceRequest::createFrom($request)->validated();

        DB::transaction(function () use ($service, $validated) {
            $technicianIds = $validated['assign_to'] ?? [];
            unset($validated['assign_to']);

            $service->update($validated);

            if (!empty($technicianIds)) {
                $service->technicians()->sync($technicianIds);
                $service->update(['assign_to' => $technicianIds[0]]);
                app(static::class)->calculateCommissions($service, $technicianIds);
            } else {
                $service->technicians()->detach();
                $service->update(['assign_to' => null]);
            }
        });

        \App\Models\ActivityLog::record('service.update', $service, "Service {$service->job_no} diperbarui");
        return redirect()
            ->route('services.show', $service)
            ->with('success', 'Servis berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $service = Service::findOrFail($id);
        $jobNo = $service->job_no;
        $service->delete();

        \App\Models\ActivityLog::record('service.delete', null, "Service {$jobNo} dihapus");
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

            $invoiceNumber = app(InvoiceService::class)->generateInvoiceNumber();

            // Calculate product costs used during service
            $productCosts = \App\Models\StockHistory::where('reference_type', \App\Models\Service::class)
                ->where('reference_id', $service->id)
                ->where('type', 'usage')
                ->get();
            $partsTotal = 0;
            $invoiceItems = [];

            if ($productCosts->isNotEmpty()) {
                foreach ($productCosts as $sh) {
                    $product = \App\Models\Product::find($sh->product_id);
                    if ($product) {
                        $qty = abs($sh->quantity_change);
                        $price = $product->price ?? 0;
                        $lineTotal = $qty * $price;
                        $partsTotal += $lineTotal;
                        $invoiceItems[] = [
                            'product_id' => $product->id,
                            'description' => 'Parts: ' . $product->name,
                            'quantity' => $qty,
                            'unit_price' => $price,
                            'total_price' => $lineTotal,
                        ];
                    }
                }
            }

            $totalAmount = ($service->charge ?? 0) + $partsTotal;

            $invoice = Invoice::create([
                'invoice_number' => $invoiceNumber,
                'customer_id' => $service->customer_id,
                'service_id' => $service->id,
                'vehicle_id' => $service->vehicle_id,
                'payment_status' => 0,
                'total_amount' => $totalAmount,
                'grand_total' => $totalAmount,
                'invoice_date' => now(),
                'invoice_type' => 'service',
                'created_by' => auth()->id() ?? 1,
            ]);

            $invoice->items()->create([
                'description' => 'Servis: ' . ($service->repairCategory?->repair_category_name ?? 'Perbaikan'),
                'quantity' => 1,
                'unit_price' => $service->charge,
                'total_price' => $service->charge,
            ]);

            foreach ($invoiceItems as $item) {
                $invoice->items()->create($item);
            }
        });

        $this->notifyCustomer($service, 'service-completed', [
            'service' => $service,
            'workshop_name' => config('app.name'),
        ]);

        \App\Models\ActivityLog::record('service.complete', $service, "Service {$service->job_no} selesai");

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

        $this->notifyCustomer($service, 'service-started', [
            'service' => $service,
            'workshop_name' => config('app.name'),
        ]);

        \App\Models\ActivityLog::record('service.start', $service, "Service {$service->job_no} dimulai");
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
        $customers = Customer::withoutBranchScope()
            ->where(fn($q) => $q->where('name', 'like', '%' . $request->q . '%')
                ->orWhere('phone', 'like', '%' . $request->q . '%'))
            ->limit(20)
            ->get(['id', 'name', 'phone']);

        return response()->json($customers);
    }

    public function vehiclesByCustomer($customer)
    {
        $customer = Customer::findOrFail($customer);
        $vehicles = $customer->vehicles()
            ->withoutBranchScope()
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

    public function calculateCommissions(Service $service, array $technicianIds): void
    {
        $charge = (float) ($service->charge ?? 0);
        if ($charge <= 0 || empty($technicianIds)) return;

        $settings = app(SettingsService::class);
        $defaultPct = (float) ($settings->get('commission_default_pct', 10));
        $share = round($charge * $defaultPct / 100 / count($technicianIds), 2);

        foreach ($technicianIds as $uid) {
            $pivot = \App\Models\ServiceTechnician::where('service_id', $service->id)
                ->where('user_id', $uid)->first();
            if ($pivot && !$pivot->commission_amt) {
                $pivot->update(['commission_pct' => $defaultPct, 'commission_amt' => $share]);
            }
        }
    }

    public function getStats(): array
    {
        return [
            'total_open' => Service::open()->count(),
            'in_progress' => Service::inProgress()->count(),
            'done_today' => Service::done()->today()->count(),
        ];
    }

    private function notifyCustomer(Service $service, string $templateSlug, array $data): void
    {
        $customer = $service->customer;
        if (!$customer || (!$customer->email && !$customer->phone)) return;
        try {
            app(NotificationService::class)->send($templateSlug, $customer, $data);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Notify failed for service {$service->id}: {$e->getMessage()}");
        }
    }
}
