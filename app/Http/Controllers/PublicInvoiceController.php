<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\SettingsService;

class PublicInvoiceController extends Controller
{
    /** Public — customer views invoice via shareable link (no auth) */
    public function show(string $token)
    {
        $invoice = Invoice::withoutGlobalScopes()
            ->with(['items', 'customer', 'paymentRecords.paymentMethod', 'paymentMethod'])
            ->where('public_token', $token)
            ->firstOrFail();

        $totalPaid = (float) $invoice->paid_amount;
        $remaining = max((float) $invoice->grand_total - $totalPaid, 0);

        $settingsService = app(SettingsService::class);
        $settings = $settingsService->getCompanyInfo();
        $accentColor = $settingsService->get('invoice_accent_color', '#2563eb');
        $font = $settingsService->get('invoice_font', 'Inter');

        return view('public.invoice', compact(
            'invoice',
            'totalPaid',
            'remaining',
            'settings',
            'accentColor',
            'font'
        ));
    }
}
