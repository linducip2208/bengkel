<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\InvoiceRequest;
use App\Http\Requests\PaymentRequest;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\PaymentMethod;
use App\Models\Service;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

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
            ->when($request->status && isset($statusMap[$request->status]), fn($q) => $q->where('payment_status', $statusMap[$request->status]))
            ->when($request->invoice_type, fn($q) => $q->where('invoice_type', $request->invoice_type))
            ->when($request->date_from, fn($q) => $q->whereDate('invoice_date', '>=', $request->date_from))
            ->when($request->date_to, fn($q) => $q->whereDate('invoice_date', '<=', $request->date_to))
            ->when($request->search, fn($q) => $q->where(function ($q) use ($request) {
                $q->whereHas('customer', fn($c) => $c->where('name', 'like', "%{$request->search}%"))
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
        $vehicles = \App\Models\Vehicle::with('customer')->orderBy('number_plate')->get();
        $selectedService = null;

        if ($request->service_id) {
            $selectedService = Service::with('customer', 'vehicle')->find($request->service_id);
        }

        return view('invoices.create', compact('customers', 'paymentMethods', 'vehicles', 'selectedService'));
    }

    public function store(InvoiceRequest $request): RedirectResponse
    {
        $invoice = $this->invoiceService->create($request->validated());

        return redirect()->route('invoices.show', $invoice)
            ->with('success', 'Invoice ' . $invoice->invoice_number . ' berhasil dibuat.');
    }

    public function show(Invoice $invoice): View
    {
        $invoice->load(['items', 'customer', 'vehicle', 'service.vehicle', 'service.jobcardDetail', 'sale.vehicle', 'paymentRecords.paymentMethod', 'paymentMethod']);
        $totalPaid = $invoice->paymentRecords->sum('amount');
        $remaining = max($invoice->grand_total - $totalPaid, 0);
        $settings = app(\App\Services\SettingsService::class)->getCompanyInfo();

        return view('invoices.show', compact('invoice', 'totalPaid', 'remaining', 'settings'));
    }

    public function edit(Invoice $invoice): View
    {
        abort_if((int) $invoice->payment_status >= 2, 403, 'Invoice yang sudah lunas tidak dapat diedit.');

        $invoice->load('items');
        $customers = Customer::orderBy('name')->get();
        $paymentMethods = PaymentMethod::where('is_active', true)->get();
        $vehicles = \App\Models\Vehicle::with('customer')->orderBy('number_plate')->get();

        return view('invoices.edit', compact('invoice', 'customers', 'paymentMethods', 'vehicles'));
    }

    public function update(InvoiceRequest $request, Invoice $invoice): RedirectResponse
    {
        abort_if((int) $invoice->payment_status >= 2, 403);

        $this->invoiceService->update($invoice, $request->validated());

        return redirect()->route('invoices.index')
            ->with('success', 'Invoice ' . $invoice->invoice_number . ' berhasil diperbarui.');
    }

    public function destroy(Invoice $invoice): RedirectResponse
    {
        abort_if($invoice->paymentRecords()->exists(), 403, 'Invoice yang sudah memiliki pembayaran tidak dapat dihapus.');

        $invoice->load('items');
        $this->invoiceService->deleteWithStockRestore($invoice);

        return redirect()->route('invoices.index')
            ->with('success', 'Invoice berhasil dihapus.');
    }

    private function resolveTemplateView(?string $template = null): array
    {
        $template = $template ?: app(\App\Services\SettingsService::class)->get('invoice_template', 'modern');
        $templates = [
            'modern'  => 'invoices.pdf-modern',
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
        $totalPaid = $invoice->paymentRecords->sum('amount');
        $remaining = max($invoice->grand_total - $totalPaid, 0);
        $settings = app(\App\Services\SettingsService::class)->getCompanyInfo();

        $pdf = Pdf::loadView($view, compact('invoice', 'totalPaid', 'remaining', 'settings'));
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
            'modern'  => 'invoices.preview-modern',
            'classic' => 'invoices.preview-classic',
            'minimal' => 'invoices.preview-minimal',
            'thermal' => 'invoices.preview-thermal',
        ];
        $view = $previews[$template] ?? $previews['modern'];

        $invoice->load(['items', 'customer', 'service.vehicle', 'service.jobcardDetail', 'sale.vehicle', 'paymentRecords.paymentMethod']);
        $totalPaid = $invoice->paymentRecords->sum('amount');
        $remaining = max($invoice->grand_total - $totalPaid, 0);
        $settings = app(\App\Services\SettingsService::class)->getCompanyInfo();

        return view($view, compact('invoice', 'totalPaid', 'remaining', 'settings', 'template'));
    }

    public function sendEmail(Invoice $invoice): RedirectResponse
    {
        $invoice->load(['items', 'customer', 'vehicle', 'service.vehicle', 'service.jobcardDetail', 'sale.vehicle', 'paymentRecords.paymentMethod']);

        $email = $invoice->customer?->email;
        if (!$email) {
            return redirect()->route('invoices.show', $invoice)
                ->with('error', 'Customer tidak punya alamat email.');
        }

        $totalPaid = $invoice->paymentRecords->sum('amount');
        $remaining = max($invoice->grand_total - $totalPaid, 0);

        try {
            $settings = app(\App\Services\SettingsService::class)->getCompanyInfo();
            [$view, $paperSize, $template] = $this->resolveTemplateView();
            $pdf = Pdf::loadView($view, compact('invoice', 'totalPaid', 'remaining', 'settings'));
            if (!is_array($paperSize)) {
                $pdf->setPaper($paperSize);
            }
            $pdfBinary = $pdf->output();

            $appName = config('app.name', 'Bengkel');
            $subject = "Invoice {$invoice->invoice_number} dari {$appName}";
            $body = view('invoices.email-body', [
                'invoice' => $invoice,
                'totalPaid' => $totalPaid,
                'remaining' => $remaining,
                'appName' => $appName,
            ])->render();

            $settings = app(\App\Services\SettingsService::class);
            $fromAddress = $settings->get('mail_from_address', config('mail.from.address'));
            $fromName = $settings->get('mail_from_name', config('mail.from.name'));

            \Illuminate\Support\Facades\Mail::send([], [], function ($message) use ($email, $subject, $body, $fromAddress, $fromName, $pdfBinary, $invoice) {
                $message->to($email)
                    ->subject($subject)
                    ->from($fromAddress, $fromName)
                    ->html($body)
                    ->attachData($pdfBinary, "invoice-{$invoice->invoice_number}.pdf", [
                        'mime' => 'application/pdf',
                    ]);
            });

            \App\Models\EmailLog::create([
                'to' => $email,
                'subject' => $subject,
                'body' => "[Invoice {$invoice->invoice_number}] dikirim dengan attachment PDF",
                'status' => 'sent',
            ]);

            return redirect()->route('invoices.show', $invoice)
                ->with('success', "Invoice berhasil dikirim ke {$email}.");
        } catch (\Throwable $e) {
            \App\Models\EmailLog::create([
                'to' => $email,
                'subject' => "Invoice {$invoice->invoice_number}",
                'body' => null,
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            return redirect()->route('invoices.show', $invoice)
                ->with('error', 'Gagal mengirim email: ' . $e->getMessage());
        }
    }

    public function sendWA(Invoice $invoice)
    {
        $phone = $invoice->customer?->phone;
        if (!$phone) {
            return redirect()->back()->with('error', 'Nomor WA pelanggan tidak tersedia.');
        }

        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (substr($phone, 0, 1) === '0') {
            $phone = '62' . substr($phone, 1);
        }

        $text = urlencode("Halo {$invoice->customer->name}, berikut invoice Anda:\n*{$invoice->invoice_number}*\nTotal: Rp " . number_format($invoice->grand_total, 0, ',', '.') . "\n\nTerima kasih.");
        $url = "https://wa.me/{$phone}?text={$text}";

        return redirect()->away($url);
    }
}
