<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\EmailLog;
use App\Models\Invoice;
use App\Models\Service;
use App\Models\ServiceEstimate;
use App\Models\ServiceEstimateItem;
use App\Models\ServiceFinding;
use App\Models\ServiceWorkPackage;
use App\Services\EstimateService;
use App\Services\SettingsService;
use App\Services\WorkshopFlowService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class EstimateController extends Controller
{
    public function __construct(protected EstimateService $estimates) {}

    // ------------------------------------------------------------------
    // Index — central estimate management/monitoring page
    // ------------------------------------------------------------------

    public function index(Request $request)
    {
        abort_unless((bool) auth()->user()?->can('estimates.view'), 403, 'Tidak punya izin melihat estimasi.');

        $filters = [
            'search' => trim((string) $request->input('search', '')),
            'status' => (string) $request->input('status', ''),
            'date_from' => (string) $request->input('date_from', ''),
            'date_to' => (string) $request->input('date_to', ''),
            'branch_id' => (string) $request->input('branch_id', ''),
            'valid_until' => (string) $request->input('valid_until', ''),
            'version' => (string) $request->input('version', ''),
        ];

        $estimates = ServiceEstimate::query()
            ->with(['service:id,job_no', 'customer:id,name,phone', 'vehicle:id,number_plate,model_name,vehicle_brand_id', 'vehicle.vehicleBrand:id,vehicle_brand', 'invoice:id,invoice_number,service_estimate_id'])
            ->when($filters['search'] !== '', function ($q) use ($filters) {
                $term = "%{$filters['search']}%";
                $q->where(function ($q) use ($term) {
                    $q->where('estimate_number', 'like', $term)
                        ->orWhereHas('service', fn ($s) => $s->where('job_no', 'like', $term))
                        ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', $term)->orWhere('phone', 'like', $term))
                        ->orWhereHas('vehicle', fn ($v) => $v->where('number_plate', 'like', $term));
                });
            })
            ->when($filters['status'] !== '' && $filters['status'] !== 'all', fn ($q) => $q->where('status', $filters['status']))
            ->when($filters['date_from'] !== '', fn ($q) => $q->whereDate('estimate_date', '>=', $filters['date_from']))
            ->when($filters['date_to'] !== '', fn ($q) => $q->whereDate('estimate_date', '<=', $filters['date_to']))
            ->when($filters['branch_id'] !== '', fn ($q) => $q->where('branch_id', $filters['branch_id']))
            ->when($filters['valid_until'] !== '', fn ($q) => $q->whereDate('valid_until', $filters['valid_until']))
            ->when($filters['version'] !== '', fn ($q) => $q->where('version', (int) $filters['version']))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        // Status counts respect the same filters except status itself.
        $statusCounts = ServiceEstimate::query()
            ->when($filters['search'] !== '', function ($q) use ($filters) {
                $term = "%{$filters['search']}%";
                $q->where(function ($q) use ($term) {
                    $q->where('estimate_number', 'like', $term)
                        ->orWhereHas('service', fn ($s) => $s->where('job_no', 'like', $term))
                        ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', $term)->orWhere('phone', 'like', $term))
                        ->orWhereHas('vehicle', fn ($v) => $v->where('number_plate', 'like', $term));
                });
            })
            ->when($filters['date_from'] !== '', fn ($q) => $q->whereDate('estimate_date', '>=', $filters['date_from']))
            ->when($filters['date_to'] !== '', fn ($q) => $q->whereDate('estimate_date', '<=', $filters['date_to']))
            ->when($filters['branch_id'] !== '', fn ($q) => $q->where('branch_id', $filters['branch_id']))
            ->when($filters['valid_until'] !== '', fn ($q) => $q->whereDate('valid_until', $filters['valid_until']))
            ->when($filters['version'] !== '', fn ($q) => $q->where('version', (int) $filters['version']))
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $counts = [
            'all' => $statusCounts->sum(),
            ServiceEstimate::STATUS_DRAFT => $statusCounts->get(ServiceEstimate::STATUS_DRAFT, 0),
            ServiceEstimate::STATUS_WAITING_APPROVAL => $statusCounts->get(ServiceEstimate::STATUS_WAITING_APPROVAL, 0),
            ServiceEstimate::STATUS_APPROVED => $statusCounts->get(ServiceEstimate::STATUS_APPROVED, 0),
            ServiceEstimate::STATUS_REJECTED => $statusCounts->get(ServiceEstimate::STATUS_REJECTED, 0),
            ServiceEstimate::STATUS_EXPIRED => $statusCounts->get(ServiceEstimate::STATUS_EXPIRED, 0),
            ServiceEstimate::STATUS_CONVERTED => $statusCounts->get(ServiceEstimate::STATUS_CONVERTED, 0),
        ];

        $branches = Branch::query()->orderBy('name')->get(['id', 'name']);

        return view('estimates.index', compact('estimates', 'counts', 'branches', 'filters'));
    }

    // ------------------------------------------------------------------
    // Dedicated estimate builder page (choose WO → findings → packages)
    // ------------------------------------------------------------------

    /** Builder page: without service → WO search; with ?service_id → form. */
    public function create(Request $request)
    {
        abort_unless((bool) auth()->user()?->can('estimates.create'), 403, 'Tidak punya izin membuat estimasi.');

        $service = null;
        $packages = collect();
        $findings = collect();
        $continuingDraft = null;
        $lockedEstimate = null;

        if ((int) $request->input('service_id', 0) > 0) {
            /** @var Service|null $service */
            $service = Service::query()->find((int) $request->input('service_id'));

            // Branch-scoped out (or deleted) → stay inside the Estimate
            // workflow, back at the search step.
            if ($service === null) {
                return redirect()
                    ->route('estimates.create')
                    ->with('error', 'Service tidak ditemukan pada cabang Anda.');
            }

            $service->load(['customer', 'vehicle.vehicleBrand', 'vehicle.vehicleType', 'repairCategory']);

            // Existing non-draft estimate → show state + actions, no builder.
            $lockedEstimate = $service->estimates()
                ->whereIn('status', [
                    ServiceEstimate::STATUS_SENT,
                    ServiceEstimate::STATUS_WAITING_APPROVAL,
                    ServiceEstimate::STATUS_APPROVED,
                    ServiceEstimate::STATUS_PARTIALLY_APPROVED,
                    ServiceEstimate::STATUS_CONVERTED,
                ])
                ->orderByDesc('version')
                ->first();

            $continuingDraft = $service->estimates()
                ->where('status', ServiceEstimate::STATUS_DRAFT)
                ->orderByDesc('version')
                ->first();

            if ($lockedEstimate !== null) {
                return view('estimates.create', [
                    'service' => $service,
                    'packages' => $packages,
                    'findings' => $findings,
                    'continuingDraft' => null,
                    'lockedEstimate' => $lockedEstimate,
                ]);
            }

            $findings = $service->findings()
                ->whereIn('status', [ServiceFinding::STATUS_OPEN, ServiceFinding::STATUS_WORK_PROPOSED])
                ->orderByDesc('id')
                ->get();

            $packages = $service->workPackages()
                ->whereIn('status', [ServiceWorkPackage::STATUS_DRAFT, ServiceWorkPackage::STATUS_PROPOSED])
                ->with(['items', 'finding'])
                ->orderByDesc('id')
                ->get();
        }

        /** @var view-string $view */
        $view = 'estimates.create';

        return view($view, compact('service', 'packages', 'findings', 'continuingDraft', 'lockedEstimate'));
    }

    /**
     * Searchable Service/Work Order dropdown source (Select2-compatible).
     * Shows all OPEN services — never silently hides earlier workflow states.
     */
    public function searchServices(Request $request)
    {
        abort_unless((bool) auth()->user()?->can('estimates.create'), 403, 'Tidak punya izin membuat estimasi.');

        $q = trim((string) $request->input('q', ''));
        $filter = (string) $request->input('filter', 'none');
        $page = max(1, (int) $request->input('page', 1));
        $perPage = 20;

        // Eligible: Booked..Ready (0-8). Cancelled / completed / closed are
        // excluded — a new estimate for those belongs to a new Service.
        $query = Service::query()
            ->with(['customer:id,name,phone', 'vehicle:id,number_plate,model_name'])
            ->whereNull('cancelled_at')
            ->where('workflow_status', '<=', 8)
            ->when(match ($filter) {
                'draft' => fn ($qq) => $qq->whereHas('estimates', fn ($e) => $e->where('status', ServiceEstimate::STATUS_DRAFT)),
                'waiting' => fn ($qq) => $qq->whereHas('estimates', fn ($e) => $e->whereIn('status', [ServiceEstimate::STATUS_SENT, ServiceEstimate::STATUS_WAITING_APPROVAL])),
                'all' => fn ($qq) => $qq,
                // Default "Belum Ada Estimasi" — prevents valid services from
                // appearing to disappear just because they have a draft.
                default => fn ($qq) => $qq->whereDoesntHave('estimates', fn ($e) => $e->whereIn('status', [
                    ServiceEstimate::STATUS_DRAFT,
                    ServiceEstimate::STATUS_SENT,
                    ServiceEstimate::STATUS_WAITING_APPROVAL,
                    ServiceEstimate::STATUS_APPROVED,
                    ServiceEstimate::STATUS_PARTIALLY_APPROVED,
                ])),
            })
            ->when($q !== '', fn ($qq) => $qq->where(function ($w) use ($q) {
                $w->where('job_no', 'like', "%{$q}%")
                    ->orWhere('title', 'like', "%{$q}%")
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$q}%")->orWhere('phone', 'like', "%{$q}%"))
                    ->orWhereHas('vehicle', fn ($v) => $v->where('number_plate', 'like', "%{$q}%")->orWhere('model_name', 'like', "%{$q}%"));
            }));

        $total = (clone $query)->count();
        $services = $query->orderByDesc('id')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get(['id', 'job_no', 'title', 'customer_id', 'vehicle_id', 'workflow_status']);

        return response()->json([
            'results' => $services->map(fn (Service $s) => $this->serviceCard($s)),
            'pagination' => ['more' => $page * $perPage < $total],
            'total' => $total,
        ]);
    }

    /**
     * Operational summary for one service (modal preview): checklist
     * progress, finding counts, work packages, latest estimate + the
     * correct primary action. Read-only.
     */
    public function servicePreview(Service $service, WorkshopFlowService $flow)
    {
        abort_unless((bool) auth()->user()?->can('estimates.create'), 403, 'Tidak punya izin membuat estimasi.');

        $service->load(['customer:id,name,phone', 'vehicle.vehicleBrand:id,vehicle_brand', 'vehicle:id,number_plate,model_name']);

        $progress = $flow->checklistProgress($service);
        $openFindings = $service->findings()
            ->whereIn('status', [ServiceFinding::STATUS_OPEN, ServiceFinding::STATUS_WORK_PROPOSED])
            ->get();
        $criticalCount = $openFindings->where('severity', ServiceFinding::SEVERITY_CRITICAL)->count();
        $workPackages = $service->workPackages()
            ->whereIn('status', [ServiceWorkPackage::STATUS_DRAFT, ServiceWorkPackage::STATUS_PROPOSED])
            ->count();

        $latest = $service->estimates()
            ->whereIn('status', [
                ServiceEstimate::STATUS_DRAFT,
                ServiceEstimate::STATUS_SENT,
                ServiceEstimate::STATUS_WAITING_APPROVAL,
                ServiceEstimate::STATUS_APPROVED,
                ServiceEstimate::STATUS_PARTIALLY_APPROVED,
                ServiceEstimate::STATUS_CONVERTED,
            ])
            ->orderByDesc('version')
            ->first();

        $primary = match ($latest?->status) {
            ServiceEstimate::STATUS_DRAFT => ['label' => 'Lanjutkan Draft', 'url' => route('estimates.create', ['service_id' => $service->id])],
            ServiceEstimate::STATUS_SENT, ServiceEstimate::STATUS_WAITING_APPROVAL,
            ServiceEstimate::STATUS_APPROVED, ServiceEstimate::STATUS_PARTIALLY_APPROVED => ['label' => 'Lihat Estimasi', 'url' => route('estimates.create', ['service_id' => $service->id])],
            ServiceEstimate::STATUS_CONVERTED => ['label' => 'Lihat Invoice', 'url' => route('invoices.show', $latest->invoice)],
            default => ['label' => 'Buat Estimasi', 'url' => route('estimates.create', ['service_id' => $service->id])],
        };

        return response()->json([
            'service' => [
                'job_no' => $service->job_no,
                'customer' => $service->customer?->name,
                'phone' => $service->customer?->phone,
                'vehicle' => trim(($service->vehicle?->vehicleBrand?->vehicle_brand ?? '').' '.($service->vehicle?->model_name ?? '')) ?: '-',
                'plate' => $service->vehicle?->number_plate,
                'workflow_label' => Service::WORKFLOW_LABELS[$service->workflow_status] ?? (string) $service->workflow_status,
            ],
            'checklist' => [
                'checked_count' => $progress['checked_count'],
                'total_points' => $progress['total_points'],
            ],
            'findings' => [
                'open' => $openFindings->count(),
                'critical' => $criticalCount,
            ],
            'work_packages' => $workPackages,
            'latest_estimate' => $latest !== null ? [
                'number' => $latest->estimate_number,
                'status' => $latest->status,
                'status_label' => $latest->statusLabel(),
            ] : null,
            'primary' => $primary,
        ]);
    }

    /** Estimate-state summary card for one service (search + create page). */
    protected function serviceCard(Service $s): array
    {
        $active = $s->estimates()->whereIn('status', [
            ServiceEstimate::STATUS_DRAFT,
            ServiceEstimate::STATUS_SENT,
            ServiceEstimate::STATUS_WAITING_APPROVAL,
            ServiceEstimate::STATUS_APPROVED,
            ServiceEstimate::STATUS_PARTIALLY_APPROVED,
            ServiceEstimate::STATUS_CONVERTED,
        ])->orderByDesc('version')->first();

        $action = 'create';
        $viewInvoiceUrl = null;
        if ($active !== null) {
            $action = match ($active->status) {
                ServiceEstimate::STATUS_DRAFT => 'continue_draft',
                ServiceEstimate::STATUS_SENT, ServiceEstimate::STATUS_WAITING_APPROVAL,
                ServiceEstimate::STATUS_CONVERTED => 'view',
                ServiceEstimate::STATUS_APPROVED, ServiceEstimate::STATUS_PARTIALLY_APPROVED => 'revise',
                default => 'view',
            };
            if ($action === 'view' && $active->status === ServiceEstimate::STATUS_CONVERTED) {
                $invoice = $active->invoice;
                $viewInvoiceUrl = $invoice !== null ? route('invoices.show', $invoice) : null;
            }
        }

        return [
            'id' => $s->id,
            'text' => $s->job_no.' — '.($s->customer?->name ?? '-'),
            'job_no' => $s->job_no,
            'title' => $s->title,
            'customer' => $s->customer?->name,
            'phone' => $s->customer?->phone,
            'plate' => $s->vehicle?->number_plate,
            'model' => $s->vehicle?->model_name,
            'workflow_label' => Service::WORKFLOW_LABELS[$s->workflow_status] ?? (string) $s->workflow_status,
            'needs_inspection' => (int) $s->workflow_status < 2,
            'has_active_estimate' => $active !== null,
            'action' => $action,
            'action_label' => match ($action) {
                'continue_draft' => 'Lanjutkan Draft',
                'view' => $viewInvoiceUrl !== null ? 'Lihat Invoice' : 'Lihat Estimasi',
                'revise' => 'Buat Revisi',
                default => 'Buat Estimasi',
            },
            'estimate' => $active !== null ? [
                'id' => $active->id,
                'number' => $active->estimate_number,
                'version' => $active->version,
                'status' => $active->status,
                'status_label' => $active->statusLabel(),
            ] : null,
            'url' => $viewInvoiceUrl ?? route('estimates.create', ['service_id' => $s->id]),
            'url_view_invoice' => $viewInvoiceUrl,
        ];
    }

    // ------------------------------------------------------------------
    // Create / update draft (idempotent â€” duplicate submits never duplicate)
    // ------------------------------------------------------------------

    public function store(Request $request, Service $service): RedirectResponse
    {
        abort_unless((bool) auth()->user()?->can('estimates.create'), 403, 'Tidak punya izin membuat estimasi.');

        $data = $this->validateHeader($request);
        $items = $this->normalizeItems($request);
        $packages = array_map('intval', (array) $request->input('packages', []));

        if (count($items) === 0 && count($packages) === 0) {
            return back()->with('error', 'Estimasi harus memiliki minimal satu item atau work package.');
        }

        $estimate = $this->estimates->createDraft($service, $data, $items, $packages);

        // Central Buat Estimasi flow: keep the user inside the Estimate
        // workflow — /estimates with quick actions, never the generic
        // /services index.
        if ($request->input('redirect_to') === 'estimates') {
            return redirect()
                ->route('estimates.index')
                ->with('success', "Estimasi {$estimate->estimate_number} berhasil dibuat.")
                ->with('created_estimate_id', $estimate->id);
        }

        return redirect()
            ->to(route('services.show', $service->id).'#tab-estimate')
            ->with('success', "Estimasi {$estimate->estimate_number} v{$estimate->version} tersimpan (draft).");
    }

    /** Add one or more editable work plans to the service's existing draft. */
    public function addWorkPackagesFromFindings(Request $request, Service $service): RedirectResponse
    {
        abort_unless((bool) auth()->user()?->can('estimates.create'), 403, 'Tidak punya izin membuat estimasi.');

        $ids = collect((array) $request->input('packages', $request->input('package_id')))
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
        $packages = ServiceWorkPackage::query()
            ->where('service_id', $service->id)
            ->whereIn('id', $ids)
            ->whereIn('status', [ServiceWorkPackage::STATUS_DRAFT, ServiceWorkPackage::STATUS_PROPOSED])
            ->with('items')
            ->get();

        if ($packages->isEmpty()) {
            return back()->with('error', 'Belum ada Rencana Pekerjaan yang siap dimasukkan ke Estimasi.');
        }

        /** @var ServiceEstimate|null $draft */
        $draft = $service->estimates()->where('status', ServiceEstimate::STATUS_DRAFT)->latest('id')->first();
        $flow = app(WorkshopFlowService::class);
        if ($draft === null) {
            $draft = $this->estimates->createDraft($service, [], [], [$packages->first()->id]);
            $packages = $packages->slice(1);
        }
        foreach ($packages as $package) {
            $flow->addWorkPackageToEstimate($draft, $package);
        }

        return redirect()->to(route('services.show', $service).'#tab-estimate')
            ->with('success', 'Rencana Pekerjaan dimasukkan ke Estimasi Draft.');
    }

    public function update(Request $request, ServiceEstimate $estimate): RedirectResponse
    {
        abort_unless((bool) auth()->user()?->can('estimates.update'), 403, 'Tidak punya izin mengubah estimasi.');

        // An issued/approved estimate is an immutable commercial document.
        if (! $estimate->isEditable()) {
            $note = $request->input('internal_notes');
            if (is_string($note) && $note !== '') {
                $estimate->forceFill(['internal_notes' => $note])->save();
                ActivityLog::record('estimate.updated', $estimate, "Catatan internal estimasi {$estimate->estimate_number} diperbarui (non-komersial)");
            }

            return back()->with('error', 'Estimasi sudah dikirim/disetujui â€” gunakan tombol "Buat Revisi" untuk mengubah isi komersial.');
        }

        $data = $this->validateHeader($request);
        $items = $this->normalizeItems($request);
        $packages = array_map('intval', (array) $request->input('packages', []));

        if (count($items) === 0 && count($packages) === 0) {
            return back()->with('error', 'Estimasi harus memiliki minimal satu item atau work package.');
        }

        $this->estimates->updateDraft($estimate, $data, $items, $packages);

        return redirect()
            ->to(route('services.show', $estimate->service_id).'#tab-estimate')
            ->with('success', "Estimasi {$estimate->estimate_number} diperbarui.");
    }

    // ------------------------------------------------------------------
    // Send (WhatsApp / Email) â€” stamps snapshot + waiting approval
    // ------------------------------------------------------------------

    public function sendWA(ServiceEstimate $estimate): RedirectResponse
    {
        abort_unless((bool) auth()->user()?->can('estimates.send'), 403, 'Tidak punya izin mengirim estimasi.');

        $estimate = $this->estimates->markSent($estimate, 'whatsapp');
        $customer = $estimate->snapshotCustomer();
        $phone = preg_replace('/[^0-9]/', '', (string) ($customer['phone'] ?? ''));

        if ($phone === '') {
            return back()->with('error', 'Nomor WA pelanggan tidak tersedia.');
        }
        if (substr($phone, 0, 1) === '0') {
            $phone = '62'.substr($phone, 1);
        }

        $publicUrl = route('public.estimate.show', $estimate->getOrCreatePublicToken());

        $message = "Halo {$customer['name']},\n\n"
            ."Berikut estimasi servis kendaraan Anda.\n\n"
            ."No Estimasi:\n{$estimate->estimate_number} (v{$estimate->version})\n\n"
            ."No Service:\n{$estimate->snapshotService()['number']}\n\n"
            ."Kendaraan:\n{$estimate->snapshotVehicle()['number_plate']}\n\n"
            .'Total Estimasi:'."\n"
            .'Rp '.number_format((float) $estimate->grand_total, 0, ',', '.')."\n\n"
            .'Berlaku sampai:'."\n"
            .$estimate->valid_until?->format('d M Y')."\n\n"
            ."Lihat & Setujui:\n{$publicUrl}\n\n"
            .'Terima kasih.';

        return redirect()->away('https://wa.me/'.$phone.'?text='.urlencode($message));
    }

    public function sendEmail(ServiceEstimate $estimate): RedirectResponse
    {
        abort_unless((bool) auth()->user()?->can('estimates.send'), 403, 'Tidak punya izin mengirim estimasi.');

        $estimate = $this->estimates->markSent($estimate, 'email');
        $email = $estimate->snapshotCustomer()['email'] ?? null;

        if (empty($email)) {
            return back()->with('error', 'Pelanggan tidak punya alamat email.');
        }

        try {
            $settings = app(SettingsService::class)->getCompanyInfo();
            $pdfBinary = $this->buildPdf($estimate)->output();
            $appName = config('app.name', 'Bengkel');
            $subject = "Estimasi Servis {$estimate->estimate_number} dari {$appName}";
            $body = view('estimates.email-body', ['estimate' => $estimate, 'appName' => $appName])->render();
            $fromAddress = app(SettingsService::class)->get('mail_from_address', config('mail.from.address'));
            $fromName = app(SettingsService::class)->get('mail_from_name', config('mail.from.name'));

            Mail::send([], [], function ($message) use ($email, $subject, $body, $fromAddress, $fromName, $pdfBinary, $estimate) {
                $message->to($email)
                    ->subject($subject)
                    ->from($fromAddress, $fromName)
                    ->html($body)
                    ->attachData($pdfBinary, "estimasi-{$estimate->estimate_number}.pdf", ['mime' => 'application/pdf']);
            });

            EmailLog::create([
                'to' => $email,
                'subject' => $subject,
                'body' => "[Estimasi {$estimate->estimate_number}] dikirim dengan attachment PDF",
                'status' => 'sent',
            ]);

            return back()->with('success', "Estimasi dikirim ke {$email}.");
        } catch (\Throwable $e) {
            EmailLog::create([
                'to' => $email,
                'subject' => "Estimasi {$estimate->estimate_number}",
                'body' => null,
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            return back()->with('error', 'Gagal mengirim email: '.$e->getMessage());
        }
    }

    // ------------------------------------------------------------------
    // PDF / print
    // ------------------------------------------------------------------

    public function pdf(ServiceEstimate $estimate)
    {
        abort_unless((bool) auth()->user()?->can('estimates.view'), 403, 'Tidak punya izin melihat estimasi.');

        return $this->buildPdf($estimate)->download("estimasi-{$estimate->estimate_number}.pdf");
    }

    public function preview(ServiceEstimate $estimate)
    {
        abort_unless((bool) auth()->user()?->can('estimates.view'), 403, 'Tidak punya izin melihat estimasi.');

        return $this->buildPdf($estimate)->stream("estimasi-{$estimate->estimate_number}.pdf");
    }

    public function print(ServiceEstimate $estimate)
    {
        abort_unless((bool) auth()->user()?->can('estimates.view'), 403, 'Tidak punya izin melihat estimasi.');

        $estimate->load(['items.product', 'groups.items', 'groups.workPackage.items', 'groups.finding.observationPointResult']);

        return view('estimates.print', ['estimate' => $estimate]);
    }

    // ------------------------------------------------------------------
    // Revision
    // ------------------------------------------------------------------

    public function revise(Request $request, ServiceEstimate $estimate): RedirectResponse
    {
        abort_unless((bool) auth()->user()?->can('estimates.revise'), 403, 'Tidak punya izin membuat revisi estimasi.');

        $data = $this->validateHeader($request);
        $items = $this->normalizeItems($request);
        $items = count($items) > 0 ? $items : $estimate->items->map(fn (ServiceEstimateItem $item) => [
            'item_type' => $item->item_type,
            'product_id' => $item->product_id,
            'description' => $item->description,
            'quantity' => (float) $item->quantity,
            'unit_price' => (float) $item->unit_price,
            'discount' => (float) $item->discount,
            'discount_type' => $item->discount_type,
            'tax_rate' => $item->tax_rate,
        ])->all();

        $revision = $this->estimates->revise($estimate, $data, $items, $request->input('revision_reason'));

        return redirect()
            ->to(route('services.show', $revision->service_id).'#tab-estimate')
            ->with('success', "Revisi dibuat: {$revision->estimate_number} v{$revision->version} (draft).");
    }

    // ------------------------------------------------------------------
    // Manager override approval
    // ------------------------------------------------------------------

    public function overrideApprove(Request $request, ServiceEstimate $estimate): RedirectResponse
    {
        $this->authorize('estimates.override');

        $request->validate(['reason' => 'required|string|min:5|max:255']);

        $this->estimates->approve($estimate, 'manager_override', $request->input('reason'), override: true);

        return back()->with('success', 'Estimasi disetujui via override manager.');
    }

    // ------------------------------------------------------------------
    // Convert to invoice
    // ------------------------------------------------------------------

    public function convertToInvoice(ServiceEstimate $estimate): RedirectResponse
    {
        $this->authorize('estimates.convert_invoice');

        try {
            $invoice = DB::transaction(fn () => $this->estimates->convertToInvoice($estimate));
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('invoices.show', $invoice)
            ->with('success', "Invoice {$invoice->invoice_number} dibuat dari estimasi {$estimate->estimate_number}.");
    }

    // ------------------------------------------------------------------
    // Validation helpers
    // ------------------------------------------------------------------

    protected function validateHeader(Request $request): array
    {
        return $request->validate([
            'estimate_date' => 'nullable|date',
            'valid_until' => 'nullable|date|after_or_equal:estimate_date',
            'discount' => 'nullable|numeric|min:0',
            'discount_type' => 'nullable|in:fixed,percent',
            'notes' => 'nullable|string|max:2000',
            'terms' => 'nullable|string|max:4000',
            'internal_notes' => 'nullable|string|max:2000',
            'revision_reason' => 'nullable|string|max:255',
        ]);
    }

    /**
     * Normalize submitted item rows: drop empty rows, coerce numerics.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function normalizeItems(Request $request): array
    {
        $validated = $request->validate([
            'items' => 'nullable|array',
            'items.*.item_type' => 'nullable|in:part,labor,other',
            'items.*.product_id' => 'nullable|integer|exists:products,id',
            'items.*.description' => 'nullable|string|max:500',
            'items.*.quantity' => 'nullable|numeric|min:0',
            'items.*.unit_price' => 'nullable|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
            'items.*.discount_type' => 'nullable|in:fixed,percent',
            'items.*.tax_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        $items = [];
        foreach ($validated['items'] ?? [] as $row) {
            $description = trim((string) ($row['description'] ?? ''));
            $hasProduct = ! empty($row['product_id']);
            if ($description === '' && ! $hasProduct) {
                continue;
            }

            $items[] = [
                'item_type' => $row['item_type'] ?? ($hasProduct ? ServiceEstimateItem::TYPE_PART : ServiceEstimateItem::TYPE_LABOR),
                'product_id' => $hasProduct ? (int) $row['product_id'] : null,
                'description' => $description !== '' ? $description : 'Item',
                'quantity' => (float) ($row['quantity'] ?? 1),
                'unit_price' => (float) ($row['unit_price'] ?? 0),
                'discount' => (float) ($row['discount'] ?? 0),
                'discount_type' => $row['discount_type'] ?? 'fixed',
                'tax_rate' => isset($row['tax_rate']) && $row['tax_rate'] !== '' ? (float) $row['tax_rate'] : null,
            ];
        }

        return $items;
    }

    protected function buildPdf(ServiceEstimate $estimate): \Barryvdh\DomPDF\PDF
    {
        $estimate->loadMissing(['items.product', 'groups.items', 'groups.workPackage.items', 'groups.finding.observationPointResult']);

        $pdf = Pdf::loadView('estimates.pdf', [
            'estimate' => $estimate,
            'company' => $estimate->snapshotCompany(),
            'customer' => $estimate->snapshotCustomer(),
            'vehicle' => $estimate->snapshotVehicle(),
            'service' => $estimate->snapshotService(),
        ])->setPaper('a4');

        // Page X / Y footer (only if the DomPDF canvas supports it).
        try {
            $canvas = $pdf->getCanvas();
            if (method_exists($canvas, 'getFontMetrics')) {
                $fontMetrics = $canvas->getFontMetrics();
                $font = $fontMetrics->getFont('helvetica', 'normal');
                $canvas->page_text(515, 812, 'Hal {PAGE_NUM} dari {PAGE_COUNT}', $font, 8, [0.45, 0.45, 0.45]);
            }
        } catch (\Throwable) {
            // Page numbers are cosmetic â€” never block document generation.
        }

        return $pdf;
    }
}
