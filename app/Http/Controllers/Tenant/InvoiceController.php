<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\InvoiceRequest;
use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\EmailLog;
use App\Models\Invoice;
use App\Models\PaymentMethod;
use App\Models\Service;
use App\Models\Vehicle;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use App\Services\SettingsService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\HttpException;

class InvoiceController extends Controller
{
    public function __construct(
        protected InvoiceService $invoiceService,
        protected PaymentService $paymentService
    ) {}

    public function index(Request $request): View
    {
        $statusMap = ['unpaid' => 0, 'half_paid' => 1, 'full_paid' => 2];

        $invoices = Invoice::query()
            ->with(['customer', 'vehicle', 'paymentRecords', 'service.vehicle', 'sale.vehicle'])
            ->when($request->status && isset($statusMap[$request->status]), fn ($q) => $q->where('payment_status', $statusMap[$request->status]))
            ->when($request->invoice_type, fn ($q) => $q->where('invoice_type', $request->invoice_type))
            ->when($request->date_from, fn ($q) => $q->whereDate('invoice_date', '>=', $request->date_from))
            ->when($request->date_to, fn ($q) => $q->whereDate('invoice_date', '<=', $request->date_to))
            ->when($request->search, fn ($q) => $q->where(function ($q) use ($request) {
                $q->whereHas('customer', fn ($c) => $c->where('name', 'like', "%{$request->search}%"))
                    ->orWhere('invoice_number', 'like', "%{$request->search}%");
            }))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('invoices.index', compact('invoices'));
    }

    public function create(Request $request): View
    {
        $customers = Customer::orderBy('name')->get();
        $paymentMethods = PaymentMethod::where('is_active', true)->get();
        $vehicles = Vehicle::with('customer')->orderBy('number_plate')->get();
        $selectedService = null;

        if ($request->service_id) {
            $selectedService = Service::with('customer', 'vehicle')->find($request->service_id);
        }

        return view('invoices.create', compact('customers', 'paymentMethods', 'vehicles', 'selectedService'));
    }

    public function store(InvoiceRequest $request): RedirectResponse
    {
        $this->authorize('invoices.manage');

        try {
            $invoice = $this->invoiceService->create($request->validated());
        } catch (\Throwable $e) {
            if ($e instanceof ValidationException) {
                throw $e;
            }
            report($e);

            return back()->withInput()->with('error', $this->invoiceSaveError($e));
        }

        ActivityLog::record('invoice.create', $invoice, "Invoice {$invoice->invoice_number} dibuat");

        return redirect()->route('invoices.show', $invoice)
            ->with('success', 'Invoice '.$invoice->invoice_number.' berhasil dibuat.');
    }

    public function show(Invoice $invoice): View
    {
        $invoice->load(['items', 'customer', 'vehicle', 'service.vehicle', 'service.jobcardDetail', 'sale.vehicle', 'paymentRecords.paymentMethod', 'paymentMethod']);
        $totalPaid = (float) $invoice->paid_amount;
        $dpAmount = max(0, (float) ($invoice->dp_amount ?? 0));
        $remaining = max($invoice->grand_total - $totalPaid - $dpAmount, 0);
        $settings = app(SettingsService::class)->getCompanyInfo();

        return view('invoices.show', compact('invoice', 'totalPaid', 'dpAmount', 'remaining', 'settings'));
    }

    public function edit(Invoice $invoice): View
    {
        abort_if((int) $invoice->payment_status >= 2, 403, 'Invoice yang sudah lunas tidak dapat diedit.');

        $invoice->load('items');
        $customers = Customer::orderBy('name')->get();
        $paymentMethods = PaymentMethod::where('is_active', true)->get();
        $vehicles = Vehicle::with('customer')->orderBy('number_plate')->get();

        return view('invoices.edit', compact('invoice', 'customers', 'paymentMethods', 'vehicles'));
    }

    public function update(InvoiceRequest $request, Invoice $invoice): RedirectResponse
    {
        abort_if((int) $invoice->payment_status >= 2, 403);
        $this->authorize('invoices.manage');

        try {
            $this->invoiceService->update($invoice, $request->validated());
        } catch (\Throwable $e) {
            if ($e instanceof ValidationException) {
                throw $e;
            }
            report($e);

            return back()->withInput()->with('error', $this->invoiceSaveError($e));
        }

        return redirect()->route('invoices.index')
            ->with('success', 'Invoice '.$invoice->invoice_number.' berhasil diperbarui.');
    }

    /**
     * Convert save failures into a safe, user-facing message. The exception
     * is still reported server-side for diagnostics, but its internals are
     * never exposed in the browser.
     */
    private function invoiceSaveError(\Throwable $e): string
    {
        if ($e instanceof ValidationException) {
            return 'Invoice gagal disimpan. Periksa kembali data dan diskon yang dimasukkan.';
        }

        return 'Invoice gagal disimpan. Pastikan customer, item, harga, dan diskon sudah diisi dengan benar.';
    }

    public function destroy(Invoice $invoice): RedirectResponse
    {
        abort_if($invoice->isVoided(), 403, 'Invoice yang sudah di-void tidak dapat dihapus (riwayat dipertahankan).');
        abort_if($invoice->paymentRecords()->exists() || $invoice->collectedValue() > 0.009, 403, 'Invoice yang sudah memiliki pembayaran/DP tidak dapat dihapus.');
        $this->authorize('invoices.delete');

        $invoice->load('items');
        $this->invoiceService->deleteWithStockRestore($invoice);

        return redirect()->route('invoices.index')
            ->with('success', 'Invoice berhasil dihapus.');
    }

    /**
     * Void invoice ber-payment (D2 opsi A): tidak menghapus fisik,
     * mengembalikan stok + me-reverse jurnal + mencatat refund.
     */
    public function void(Request $request, Invoice $invoice): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            $result = $this->invoiceService->voidInvoice($invoice, $validated['reason'], auth()->id());
        } catch (HttpException $e) {
            return back()->with('error', $e->getMessage());
        }

        $refunded = number_format($result['refunded'], 0, ',', '.');

        return redirect()->route('invoices.show', $invoice)
            ->with('success', "Invoice berhasil di-void (refund Rp {$refunded}).");
    }

    /**
     * Stream the customer-uploaded payment proof from private storage.
     * Falls back to the legacy public-disk path for historical uploads.
     */
    public function paymentProof(Invoice $invoice)
    {
        abort_unless($invoice->payment_proof, 404, 'Bukti pembayaran belum diunggah.');

        if (Storage::disk('local')->exists($invoice->payment_proof)) {
            return Storage::disk('local')->response($invoice->payment_proof);
        }

        abort_unless(Storage::disk('public')->exists($invoice->payment_proof), 404, 'Berkas tidak ditemukan.');

        return Storage::disk('public')->response($invoice->payment_proof);
    }

    private function resolveTemplateView(?string $template = null): array
    {
        $template = $template ?: app(SettingsService::class)->get('invoice_template', 'modern');
        $templates = [
            'modern' => 'invoices.pdf-modern',
            'classic' => 'invoices.pdf-classic',
            'minimal' => 'invoices.pdf-minimal',
            'thermal' => 'invoices.pdf-thermal',
        ];
        $view = $templates[$template] ?? $templates['modern'];
        $paperSize = $template === 'thermal' ? [0, 0, 226.77, 841.89] : 'a4';

        return [$view, $paperSize, $template];
    }

    public function pdf(Invoice $invoice, Request $request)
    {
        [$view, $paperSize, $template] = $this->resolveTemplateView($request->get('template'));

        $invoice->load(['items', 'customer', 'service.vehicle', 'service.jobcardDetail', 'sale.vehicle', 'paymentRecords.paymentMethod']);
        $totalPaid = (float) $invoice->paid_amount;
        $dpAmount = max(0, (float) ($invoice->dp_amount ?? 0));
        $remaining = max($invoice->grand_total - $totalPaid - $dpAmount, 0);
        $settings = app(SettingsService::class)->getCompanyInfo();

        $pdf = Pdf::loadView($view, compact('invoice', 'totalPaid', 'dpAmount', 'remaining', 'settings'));
        if (is_array($paperSize)) {
            $pdf->setPaper($paperSize);
        } else {
            $pdf->setPaper($paperSize);
        }

        return $pdf->download("invoice-{$invoice->invoice_number}.pdf");
    }

    public function preview(Invoice $invoice, Request $request): View
    {
        $template = $request->get('template', 'modern');
        $previews = [
            'modern' => 'invoices.preview-modern',
            'classic' => 'invoices.preview-classic',
            'minimal' => 'invoices.preview-minimal',
            'thermal' => 'invoices.preview-thermal',
        ];
        $view = $previews[$template] ?? $previews['modern'];

        $invoice->load(['items', 'customer', 'service.vehicle', 'service.jobcardDetail', 'sale.vehicle', 'paymentRecords.paymentMethod']);
        $totalPaid = (float) $invoice->paid_amount;
        $dpAmount = max(0, (float) ($invoice->dp_amount ?? 0));
        $remaining = max($invoice->grand_total - $totalPaid - $dpAmount, 0);
        $settings = app(SettingsService::class)->getCompanyInfo();

        return view($view, compact('invoice', 'totalPaid', 'dpAmount', 'remaining', 'settings', 'template'));
    }

    public function sendEmail(Invoice $invoice): RedirectResponse
    {
        $invoice->load(['items', 'customer', 'vehicle', 'service.vehicle', 'service.jobcardDetail', 'sale.vehicle', 'paymentRecords.paymentMethod']);

        $email = $invoice->customer?->email;
        if (! $email) {
            return redirect()->route('invoices.show', $invoice)
                ->with('error', 'Customer tidak punya alamat email.');
        }

        $totalPaid = (float) $invoice->paid_amount;
        $dpAmount = max(0, (float) ($invoice->dp_amount ?? 0));
        $remaining = max($invoice->grand_total - $totalPaid - $dpAmount, 0);

        try {
            $settings = app(SettingsService::class)->getCompanyInfo();
            [$view, $paperSize, $template] = $this->resolveTemplateView();
            $pdf = Pdf::loadView($view, compact('invoice', 'totalPaid', 'dpAmount', 'remaining', 'settings'));
            if (! is_array($paperSize)) {
                $pdf->setPaper($paperSize);
            }
            $pdfBinary = $pdf->output();

            $appName = config('app.name', 'Bengkel');
            $subject = "Invoice {$invoice->invoice_number} dari {$appName}";
            $body = view('invoices.email-body', [
                'invoice' => $invoice,
                'totalPaid' => $totalPaid,
                'dpAmount' => $dpAmount,
                'remaining' => $remaining,
                'appName' => $appName,
            ])->render();

            $settings = app(SettingsService::class);
            $fromAddress = $settings->get('mail_from_address', config('mail.from.address'));
            $fromName = $settings->get('mail_from_name', config('mail.from.name'));

            Mail::send([], [], function ($message) use ($email, $subject, $body, $fromAddress, $fromName, $pdfBinary, $invoice) {
                $message->to($email)
                    ->subject($subject)
                    ->from($fromAddress, $fromName)
                    ->html($body)
                    ->attachData($pdfBinary, "invoice-{$invoice->invoice_number}.pdf", [
                        'mime' => 'application/pdf',
                    ]);
            });

            EmailLog::create([
                'to' => $email,
                'subject' => $subject,
                'body' => "[Invoice {$invoice->invoice_number}] dikirim dengan attachment PDF",
                'status' => 'sent',
            ]);

            return redirect()->route('invoices.show', $invoice)
                ->with('success', "Invoice berhasil dikirim ke {$email}.");
        } catch (\Throwable $e) {
            EmailLog::create([
                'to' => $email,
                'subject' => "Invoice {$invoice->invoice_number}",
                'body' => null,
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            return redirect()->route('invoices.show', $invoice)
                ->with('error', 'Gagal mengirim email: '.$e->getMessage());
        }
    }

    public function sendWA(Invoice $invoice)
    {
        $phone = $invoice->customer?->phone;
        if (! $phone) {
            return redirect()->back()->with('error', 'Nomor WA pelanggan tidak tersedia.');
        }

        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (substr($phone, 0, 1) === '0') {
            $phone = '62'.substr($phone, 1);
        }

        $dpAmount = max(0, (float) ($invoice->dp_amount ?? 0));
        $remaining = max((float) $invoice->grand_total - (float) $invoice->paid_amount - $dpAmount, 0);
        $lines = [
            "Halo {$invoice->customer->name}, berikut invoice Anda:",
            "*{$invoice->invoice_number}*",
            'Total: Rp '.number_format($invoice->grand_total, 0, ',', '.'),
        ];
        if ($dpAmount > 0) {
            $lines[] = 'DP: Rp '.number_format($dpAmount, 0, ',', '.');
        }
        $lines[] = 'Sisa: Rp '.number_format($remaining, 0, ',', '.');
        $lines[] = '';
        $lines[] = 'Terima kasih.';
        $text = urlencode(implode("\n", $lines));
        $url = "https://wa.me/{$phone}?text={$text}";

        return redirect()->away($url);
    }

    public function share(Request $request, Invoice $invoice)
    {
        $token = $invoice->getOrCreatePublicToken();
        $url = url('/invoice/'.$token);

        if ($request->expectsJson()) {
            return response()->json([
                'url' => $url,
                'token' => $token,
            ]);
        }

        return redirect()->route('invoices.show', $invoice)
            ->with('success', 'Link invoice publik: '.$url);
    }
}
