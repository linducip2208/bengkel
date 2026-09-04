<?php

namespace App\Services;

use App\Http\Requests\ServiceRequest;
use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\PartReservation;
use App\Models\Product;
use App\Models\Reminder;
use App\Models\RepairCategory;
use App\Models\Service;
use App\Models\ServiceEstimate;
use App\Models\ServiceTechnician;
use App\Models\StockHistory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ServiceService extends BaseService
{
    // Valid forward workflow transitions. Terminal/cancelled states reject everything.
    public const WORKFLOW_TERMINAL = 12;

    public function index(Request $request)
    {
        $services = Service::with(['customer', 'vehicle', 'repairCategory', 'technicians'])
            ->when($request->filled('status') && $request->status !== 'all', function ($q) use ($request) {
                if ($request->status === 'active') {
                    $q->where('workflow_status', '<', 12);
                } else {
                    $q->where('workflow_status', $request->status);
                }
            })
            ->when($request->filled('date_from'), function ($q) use ($request) {
                $q->whereDate('service_date', '>=', $request->date_from);
            })
            ->when($request->filled('date_to'), function ($q) use ($request) {
                $q->whereDate('service_date', '<=', $request->date_to);
            })
            ->when($request->filled('customer_search'), function ($q) use ($request) {
                $q->whereHas('customer', fn ($c) => $c->where('name', 'like', '%'.$request->customer_search.'%'));
            })
            ->when($request->filled('technician'), function ($q) use ($request) {
                $q->whereHas('technicians', fn ($t) => $t->where('users.id', $request->technician));
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $stats = $this->getStats();
        $technicians = User::role('mekanik')->get();

        $progressService = app(WorkshopProgressService::class);
        $progressByService = $services->getCollection()->mapWithKeys(fn (Service $service) => [
            $service->id => $progressService->calculate($service),
        ]);

        return view('services.index', compact('services', 'stats', 'technicians', 'progressByService'));
    }

    public function create()
    {
        $repairCategories = RepairCategory::orderBy('repair_category_name')->get();
        $technicians = User::role('mekanik')->get();
        $serviceAdvisors = User::workshopStaffQuery()->orderBy('name')->get();

        return view('services.create', compact('repairCategories', 'technicians', 'serviceAdvisors'));
    }

    public function store(ServiceRequest $request)
    {
        $validated = $request->validated();

        // Conflict detection: check technician availability
        $warnings = [];
        $techIds = $validated['assign_to'] ?? [];
        if (! empty($techIds) && ! empty($validated['service_date'])) {
            $conflicts = ServiceTechnician::whereIn('user_id', $techIds)
                ->whereHas('service', fn ($q) => $q->whereDate('service_date', Carbon::parse($validated['service_date'])->toDateString())->where('done_status', '<', 2))
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
            $validated['service_advisor_id'] = $validated['service_advisor_id'] ?? null;
            if (($validated['done_status'] ?? 0) >= 1) {
                $validated['started_at'] = $validated['started_at'] ?? now();
            }

            $technicianIds = $validated['assign_to'] ?? [];
            unset($validated['assign_to']);

            $service = Service::create($validated);

            $repeat = $service->detectRepeatJob();
            if ($repeat) {
                $service->update(['repeat_of' => $repeat->id]);
            }

            if (! empty($technicianIds)) {
                $service->technicians()->sync($technicianIds);
                $service->update(['assign_to' => $technicianIds[0]]);
                $this->calculateCommissions($service, $technicianIds);
            }

            if ($request->has('products') && is_array($request->products)) {
                $productService = app(ProductService::class);
                foreach ($request->products as $productData) {
                    $product = Product::find($productData['id'] ?? $productData['product_id'] ?? null);
                    if ($product && ($productData['quantity'] ?? 0) > 0) {
                        $productService->useInService($product, (float) $productData['quantity'], $service->id);
                    }
                }
            }

            return $service;
        });

        ActivityLog::record('service.create', $service, "Service {$service->job_no} dibuat");

        return redirect()
            ->route('services.show', $service)
            ->with('success', 'Servis berhasil dibuat.')
            ->with('warnings', $warnings);
    }

    public function show($id)
    {
        $service = Service::with([
            'customer', 'booking', 'vehicle.vehicleType', 'vehicle.vehicleBrand',
            'repairCategory', 'technicians', 'jobcardDetail',
            'serviceObservationPoints.observationPoint.observationType',
            'images', 'checkoutResults.checkoutCategory', 'invoice',
            'serviceAdvisor', 'serviceTechnicians.user',
            'activityLogs.user',
            'findings' => fn ($q) => $q->orderByDesc('id'),
            'workPackages' => fn ($q) => $q->with(['items.product', 'task.timeEntries', 'qcChecks', 'finding'])->orderByDesc('id'),
            'estimates' => fn ($q) => $q->with(['items.product', 'groups.items', 'groups.workPackage.items', 'groups.finding.observationPointResult'])->orderByDesc('version'),
        ])->findOrFail($id);

        $nextService = $service->jobcardDetail
            ? app(JobcardService::class)->calculateNextService($service)
            : null;

        $partsUsed = StockHistory::with('product')
            ->where('reference_type', Service::class)
            ->where('reference_id', $service->id)
            ->where('type', 'usage')
            ->get()
            ->map(function ($sh) use ($service) {
                $product = $sh->product;
                $serviceDate = $service->service_date?->toDateString();

                return [
                    'product_id' => $sh->product_id,
                    'product_name' => $product?->name ?? 'Unknown',
                    'sku' => $product?->product_no ?? '-',
                    'warranty' => $product?->warranty ?? null,
                    'qty' => abs($sh->quantity_change),
                    'is_under_warranty' => $product?->isUnderWarranty($serviceDate) ?? false,
                    'warranty_expiry' => $product?->getWarrantyExpiryDate($serviceDate),
                ];
            });

        $reservations = $service->reservations()
            ->with('product', 'reserver')
            ->latest()
            ->get();

        $products = Product::with(['productType', 'unit', 'stockRecord'])
            ->orderBy('name')
            ->get();

        $reservedMap = PartReservation::whereIn('product_id', $products->pluck('id'))
            ->where('status', 'reserved')
            ->groupBy('product_id')
            ->selectRaw('product_id, SUM(quantity) as total')
            ->pluck('total', 'product_id');

        $invoice = Invoice::query()->where('service_id', $service->id)->first();
        $paidAmount = $invoice ? (float) $invoice->paymentRecords()->sum('amount') : 0.0;

        $estimateSummary = app(EstimateService::class)->reconciliation($service);
        // Source of truth before invoicing = active approved estimate.
        $estimatedAmount = $estimateSummary['approved_estimate'] > 0
            ? $estimateSummary['approved_estimate']
            : (float) ($service->charge ?? 0);

        $financialSummary = [
            'estimated' => $estimatedAmount,
            'invoiced' => (float) ($invoice ? $invoice->grand_total : 0),
            'paid' => $paidAmount,
            'outstanding' => max(0, (float) ($invoice ? $invoice->grand_total : 0) - $paidAmount),
        ];

        $progress = app(WorkshopProgressService::class)->calculate($service);

        return view('services.show', compact('service', 'nextService', 'partsUsed', 'reservations', 'products', 'reservedMap', 'financialSummary', 'estimateSummary', 'progress'));
    }

    public function edit($id)
    {
        $service = Service::with(['technicians'])->findOrFail($id);
        $repairCategories = RepairCategory::orderBy('repair_category_name')->get();
        $technicians = User::role('mekanik')->get();
        $serviceAdvisors = User::workshopStaffQuery()->orderBy('name')->get();
        $selectedCustomer = $service->customer;
        $selectedVehicle = $service->vehicle;

        return view('services.edit', compact('service', 'repairCategories', 'technicians', 'serviceAdvisors', 'selectedCustomer', 'selectedVehicle'));
    }

    public function update(ServiceRequest $request, $id)
    {
        $service = Service::findOrFail($id);
        $validated = $request->validated();

        DB::transaction(function () use ($service, $validated) {
            $technicianIds = $validated['assign_to'] ?? [];
            unset($validated['assign_to']);
            $validated['service_advisor_id'] = $validated['service_advisor_id'] ?? null;

            // Commercial source of truth is the ServiceEstimate — service edit
            // must never overwrite it once an estimate exists. `charge` becomes
            // a derived/compatibility value (approved amount / invoice amount).
            if ($service->estimates()->exists() && array_key_exists('charge', $validated)) {
                unset($validated['charge']);
            }

            $service->update($validated);

            if (! empty($technicianIds)) {
                $service->technicians()->sync($technicianIds);
                $service->update(['assign_to' => $technicianIds[0]]);
                app(static::class)->calculateCommissions($service, $technicianIds);
            } else {
                $service->technicians()->detach();
                $service->update(['assign_to' => null]);
            }
        });

        ActivityLog::record('service.update', $service, "Service {$service->job_no} diperbarui");

        return redirect()
            ->route('services.show', $service)
            ->with('success', 'Servis berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $service = Service::findOrFail($id);
        $jobNo = $service->job_no;

        if ($service->invoice) {
            return redirect()
                ->route('services.show', $service)
                ->with('error', 'Servis sudah memiliki invoice — batalkan/void invoice terlebih dahulu sebelum menghapus servis.');
        }

        DB::transaction(function () use ($service) {
            // Return consumed parts to inventory (reversal keeps audit history).
            $usages = StockHistory::where('reference_type', Service::class)
                ->where('reference_id', $service->id)
                ->where('type', 'usage')
                ->get();

            foreach ($usages as $usage) {
                StockService::increment(
                    $usage->product_id,
                    abs((float) $usage->quantity_change),
                    'usage_restore',
                    "Pembatalan servis {$service->job_no}",
                    Service::class,
                    $service->id,
                );
            }

            $service->reservations()->where('status', 'reserved')->update(['status' => 'released']);
            $service->delete();
        });

        ActivityLog::record('service.delete', null, "Service {$jobNo} dihapus");

        return redirect()
            ->route('services.index')
            ->with('success', 'Servis berhasil dihapus.');
    }

    public function complete(Request $request, $id)
    {
        $service = Service::findOrFail($id);

        try {
            $result = $this->executeComplete($service);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        if (! empty($result['already'])) {
            return redirect()
                ->route('services.show', $service)
                ->with('info', 'Servis sudah selesai sebelumnya — tidak ada invoice baru.');
        }

        // Reload stamps written inside the transaction (survey_token etc).
        $service = $service->fresh();

        if ($service->customer) {
            $this->notifyCustomer($service, 'service-completed', [
                'service' => $service,
                'workshop_name' => config('app.name'),
                'survey_link' => $service->survey_token ? route('survey.show', $service->survey_token) : null,
            ]);
        }

        // Auto-generate next service reminder
        $this->createNextServiceReminder($service);

        ActivityLog::record('service.complete', $service, "Service {$service->job_no} selesai");

        return redirect()
            ->route('services.show', $service)
            ->with('success', 'Servis selesai.');
    }

    /**
     * Execute the full service-completion business flow atomically and
     * idempotently. Used by both the web flow and the API endpoint.
     *
     * Guarantees when called more than once:
     *  - stock is never decremented twice (parts were already consumed at
     *    use-time; completion only records them on the invoice),
     *  - only ONE invoice is ever created per service,
     *  - the accounting journal is idempotent (see AutoJournalService),
     *  - completed_at/jobcard stamps are only set the first time.
     *
     * @return array{invoice: Invoice|null, already: bool}
     *
     * @throws \RuntimeException when the service is cancelled or already final.
     */
    public function executeComplete(Service $service): array
    {
        if (app(WorkshopInvoiceGuard::class)->isModernWorkshopService($service)) {
            throw new \RuntimeException('Service modern harus mengikuti pekerjaan, QC, invoice, pembayaran, dan Gate Pass sebelum selesai.');
        }

        return DB::transaction(function () use ($service) {
            // Lock the row: a concurrent double-click re-reads state and
            // short-circuits instead of creating a second invoice.
            $locked = Service::query()->whereKey($service->id)->lockForUpdate()->first();

            if (! $locked) {
                throw new \RuntimeException('Service tidak ditemukan.');
            }

            if ($locked->cancelled_at) {
                throw new \RuntimeException('Servis ini sudah dibatalkan dan tidak bisa diselesaikan.');
            }

            if ($locked->done_status >= 2 || $locked->workflow_status >= 12) {
                $existing = Invoice::where('service_id', $locked->id)->first();

                return ['already' => true, 'invoice' => $existing];
            }

            $locked->update([
                'done_status' => 2,
                'workflow_status' => 12,
                'completed_at' => now(),
                'invoiced_at' => now(),
                'survey_token' => $locked->survey_token ?? Str::random(32),
            ]);

            if ($locked->jobcardDetail) {
                $locked->jobcardDetail->update([
                    'out_date' => now(),
                    'done_status' => 2,
                ]);
            }

            if ($locked->jobcardDetail?->odometer_out && $locked->vehicle) {
                $locked->vehicle->update(['odometer' => $locked->jobcardDetail->odometer_out]);
            }

            $invoiceNumber = app(InvoiceService::class)->generateInvoiceNumber();

            // Calculate product costs used during service
            $productCosts = StockHistory::where('reference_type', Service::class)
                ->where('reference_id', $locked->id)
                ->where('type', 'usage')
                ->get();
            $partsTotal = 0;
            $invoiceItems = [];

            if ($productCosts->isNotEmpty()) {
                $products = Product::whereIn('id', $productCosts->pluck('product_id')->unique())
                    ->get()
                    ->keyBy('id');

                foreach ($productCosts as $sh) {
                    $product = $products[$sh->product_id] ?? null;
                    if ($product) {
                        $qty = abs((float) $sh->quantity_change);
                        $price = (float) ($product->price ?? 0);
                        $lineTotal = round($qty * $price, 2);
                        $partsTotal += $lineTotal;
                        $invoiceItems[] = [
                            'product_id' => $product->id,
                            'description' => 'Parts: '.$product->name,
                            'quantity' => $qty,
                            'unit_price' => $price,
                            'total_price' => $lineTotal,
                        ];
                    }
                }
            }

            $totalAmount = round(($locked->charge ?? 0) + $partsTotal, 2);

            $invoice = Invoice::create([
                'invoice_number' => $invoiceNumber,
                'customer_id' => $locked->customer_id,
                'service_id' => $locked->id,
                'vehicle_id' => $locked->vehicle_id,
                'payment_status' => 0,
                'total_amount' => $totalAmount,
                'grand_total' => $totalAmount,
                'invoice_date' => now(),
                'invoice_type' => 'service',
                'created_by' => auth()->id(),
                'branch_id' => $locked->branch_id,
            ]);

            $locked->update(['actual_cost' => $invoice->grand_total]);

            if ((float) $locked->charge > 0) {
                $invoice->items()->create([
                    'description' => 'Servis: '.($locked->repairCategory?->repair_category_name ?? 'Perbaikan'),
                    'quantity' => 1,
                    'unit_price' => $locked->charge,
                    'total_price' => round((float) $locked->charge, 2),
                ]);
            }

            foreach ($invoiceItems as $item) {
                $invoice->items()->create($item);
            }

            // Accrual accounting: AR + revenue split + COGS for parts.
            try {
                app(AutoJournalService::class)->journalInvoiceIssued($invoice);
            } catch (\Throwable $e) {
                Log::error("Service completion auto-journal: {$e->getMessage()}");

                // Journal failure must roll back the whole completion.
                throw $e;
            }

            foreach ($productCosts as $sh) {
                StockHistory::create([
                    'product_id' => $sh->product_id,
                    'quantity_change' => 0,
                    'previous_stock' => $sh->new_stock,
                    'new_stock' => $sh->new_stock,
                    'type' => 'invoice',
                    'reason' => 'Tercatat di Invoice #'.$invoiceNumber,
                    'reference_type' => Invoice::class,
                    'reference_id' => $invoice->id,
                    'user_id' => auth()->id() ?? 1,
                ]);
            }

            return ['already' => false, 'invoice' => $invoice];
        });
    }

    public function startService($id)
    {
        $service = Service::findOrFail($id);

        if ($service->workflow_status >= 12 || $service->cancelled_at) {
            return back()->with('error', 'Servis sudah selesai/dibatalkan — tidak bisa dimulai ulang.');
        }
        if (! in_array((int) $service->workflow_status, [0, 1], true)) {
            return back()->with('error', 'Servis sudah melewati tahap check-in (status: '.$service->status_label.').');
        }

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

        ActivityLog::record('service.start', $service, "Service {$service->job_no} dimulai");

        return back()->with('success', 'Servis dimulai. Timer berjalan.');
    }

    public function advanceWorkflow($id)
    {
        $service = Service::findOrFail($id);
        $result = DB::transaction(function () use ($service) {
            $locked = Service::query()->whereKey($service->id)->lockForUpdate()->firstOrFail();

            if ($locked->cancelled_at) {
                return ['error' => 'Servis sudah dibatalkan.'];
            }
            $nextStatus = (int) $locked->workflow_status + 1;
            if (! $locked->canTransitionTo($nextStatus)) {
                return ['error' => 'Transisi status tidak valid dari '.$locked->status_label.'.'];
            }

            // Workflow guards tied to the Estimate lifecycle.
            $estimateService = app(EstimateService::class);
            $activeEstimate = $estimateService->latestActiveEstimate($locked);

            if ($nextStatus === 3 && $activeEstimate === null) {
                return ['error' => 'Transisi ke Waiting Approval memerlukan Estimasi. Buat estimasi pada tab Estimasi terlebih dahulu.'];
            }

            if ($nextStatus === 4 && $activeEstimate !== null && $activeEstimate->status !== ServiceEstimate::STATUS_APPROVED) {
                return ['error' => 'Estimasi '.$activeEstimate->estimate_number.' belum disetujui pelanggan ('
                        .$activeEstimate->statusLabel().'). Tunggu persetujuan customer atau gunakan override manager dengan alasan.'];
            }

            $data = ['workflow_status' => $nextStatus];

            switch ($nextStatus) {
                case 1: $data['checked_in_at'] = $service->checked_in_at ?? now();
                    break;
                case 2: $data['inspected_at'] = now();
                    break;
                case 4:
                    $data['approved_at'] = now();
                    $data['is_approved'] = true;
                    break;
                case 5: $data['started_at'] = $locked->started_at ?? now();
                    break;
                case 7:
                    $data['qc_passed_at'] = now();
                    // QC passed implies work finished.
                    $data['done_status'] = max((int) $locked->done_status, 2);
                    break;
                case 9: $data['invoiced_at'] = now();
                    break;
                case 10: $data['paid_at'] = now();
                    break;
                case 11: $data['released_at'] = now();
                    break;
                case 12: $data['completed_at'] = now();
                    break;
            }

            $locked->update($data);
            ActivityLog::record('service.workflow_transition', $locked, 'Status berubah ke '.Service::WORKFLOW_LABELS[$nextStatus], [
                'from' => (int) $service->workflow_status,
                'to' => $nextStatus,
            ]);

            return ['status' => $nextStatus];
        });

        if (isset($result['error'])) {
            return back()->with('error', $result['error']);
        }

        return back()->with('success', 'Status: '.Service::WORKFLOW_LABELS[$result['status']]);
    }

    public function uploadImage(Request $request, $id)
    {
        $service = Service::findOrFail($id);

        $request->validate([
            'image' => 'required|image|max:5120',
            'type' => 'required|in:before,after,progress',
            'caption' => 'nullable|string|max:255',
        ]);

        $path = $request->file('image')->store('service-images/'.$service->id, 'public');

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
            ->where(fn ($q) => $q->where('name', 'like', '%'.$request->q.'%')
                ->orWhere('phone', 'like', '%'.$request->q.'%'))
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
        return DocumentNumberService::generate(DocumentNumberService::SERVICES, 'BP', 'Ymd', 3);
    }

    public function calculateCommissions(Service $service, array $technicianIds): void
    {
        $charge = (float) ($service->charge ?? 0);
        if ($charge <= 0 || empty($technicianIds)) {
            return;
        }

        $settings = app(SettingsService::class);
        $defaultPct = (float) ($settings->get('commission_default_pct', 10));
        $share = round($charge * $defaultPct / 100 / count($technicianIds), 2);

        foreach ($technicianIds as $uid) {
            $pivot = ServiceTechnician::where('service_id', $service->id)
                ->where('user_id', $uid)->first();
            if ($pivot && ! $pivot->commission_amt) {
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

    private function createNextServiceReminder(Service $service): void
    {
        try {
            $jobcardDetail = $service->jobcardDetail;
            $vehicle = $service->vehicle;

            $description = 'Estimated next service based on time interval';
            $remindAt = now()->addDays(90);

            if ($jobcardDetail && $jobcardDetail->odometer_out) {
                $nextOdometer = $jobcardDetail->odometer_out + 5000;
                $description = "Estimated next service at {$nextOdometer} km based on current odometer reading ({$jobcardDetail->odometer_out} km)";
            }

            Reminder::create([
                'customer_id' => $service->customer_id,
                'vehicle_id' => $service->vehicle_id,
                'service_id' => $service->id,
                'title' => 'Next Service Reminder for '.($vehicle?->number_plate ?? 'Vehicle #'.$service->vehicle_id),
                'description' => $description,
                'reminder_date' => $remindAt,
                'reminder_type' => 'service',
                'is_active' => true,
                'branch_id' => $service->branch_id,
                'created_by' => auth()->id(),
            ]);
        } catch (\Throwable $e) {
            Log::warning("Next service reminder failed for service {$service->id}: {$e->getMessage()}");
        }
    }

    private function notifyCustomer(Service $service, string $templateSlug, array $data): void
    {
        $customer = $service->customer;
        if (! $customer || (! $customer->email && ! $customer->phone)) {
            return;
        }
        try {
            app(NotificationService::class)->send($templateSlug, $customer, $data);
        } catch (\Throwable $e) {
            Log::warning("Notify failed for service {$service->id}: {$e->getMessage()}");
        }
    }
}
