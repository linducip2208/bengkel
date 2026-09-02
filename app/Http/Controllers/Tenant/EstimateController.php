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
use App\Services\EstimateService;
use App\Services\SettingsService;
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

        // Chooser for "+ Buat Estimasi" — never creates an estimate directly.
        $services = Service::query()
            ->with(['customer:id,name', 'vehicle:id,number_plate,model_name'])
            ->whereDoesntHave('estimates', fn ($q) => $q->whereIn('status', [
                ServiceEstimate::STATUS_DRAFT,
                ServiceEstimate::STATUS_SENT,
                ServiceEstimate::STATUS_WAITING_APPROVAL,
                ServiceEstimate::STATUS_APPROVED,
            ]))
            ->whereIn('workflow_status', [2, 3])
            ->orderByDesc('id')
            ->limit(50)
            ->get(['id', 'job_no', 'title', 'customer_id', 'vehicle_id']);

        return view('estimates.index', compact('estimates', 'counts', 'branches', 'services', 'filters'));
    }

    // ------------------------------------------------------------------
    // Create / update draft (idempotent â€” duplicate submits never duplicate)
    // ------------------------------------------------------------------

    public function store(Request $request, Service $service): RedirectResponse
    {
        abort_unless((bool) auth()->user()?->can('estimates.create'), 403, 'Tidak punya izin membuat estimasi.');

        $data = $this->validateHeader($request);
        $items = $this->normalizeItems($request);

        if (count($items) === 0) {
            return back()->with('error', 'Estimasi harus memiliki minimal satu item.');
        }

        $estimate = $this->estimates->createDraft($service, $data, $items);

        return redirect()
            ->to(route('services.show', $service->id).'#tab-estimate')
            ->with('success', "Estimasi {$estimate->estimate_number} v{$estimate->version} tersimpan (draft).");
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

        if (count($items) === 0) {
            return back()->with('error', 'Estimasi harus memiliki minimal satu item.');
        }

        $this->estimates->updateDraft($estimate, $data, $items);

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

        $estimate->load('items.product');

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
        $estimate->loadMissing('items.product');

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
