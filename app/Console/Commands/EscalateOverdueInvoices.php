<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Services\SettingsService;
use Illuminate\Console\Command;

class EscalateOverdueInvoices extends Command
{
    protected $signature = 'invoices:escalate-overdue';
    protected $description = 'Escalate overdue unpaid/half-paid invoices & send WA reminder';

    public function handle(): int
    {
        $settings = app(SettingsService::class);

        $overdue = Invoice::with(['customer', 'paymentRecords'])
            ->where('payment_status', '!=', 2)
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', now()->toDateString())
            ->get();

        if ($overdue->isEmpty()) {
            $this->info('No overdue invoices found.');
            return self::SUCCESS;
        }

        $sent = 0;
        $phone = preg_replace('/[^0-9]/', '', $settings->get('company_phone', ''));
        if ($phone && substr($phone, 0, 1) === '0') {
            $phone = '62' . substr($phone, 1);
        }

        foreach ($overdue as $invoice) {
            $totalPaid = $invoice->paymentRecords->sum('amount');
            $remaining = max($invoice->grand_total - $totalPaid, 0);

            $this->line("Overdue: {$invoice->invoice_number} — {$invoice->customer->name} — Sisa: {$remaining}");

            // Send WA reminder to customer via NotificationService
            $custPhone = $invoice->customer?->phone;
            if ($custPhone && $invoice->customer) {
                try {
                    app(\App\Services\NotificationService::class)->send('invoice-overdue', $invoice->customer, [
                        'customer_name' => $invoice->customer->name,
                        'invoice_number' => $invoice->invoice_number,
                        'remaining' => $remaining,
                    ]);
                } catch (\Throwable $e) {
                    \Log::warning("Overdue WA send failed: {$e->getMessage()}");
                }

                \App\Models\ActivityLog::create([
                    'user_id' => null,
                    'event' => 'overdue_reminder',
                    'description' => "Pengingat overdue: Invoice {$invoice->invoice_number} ke {$invoice->customer->name}",
                    'subject_type' => 'Invoice',
                    'subject_id' => $invoice->id,
                ]);
                $sent++;
            }

            $this->line("  -> WA reminder queued for {$invoice->customer->name}");
        }

        $this->info("Done. {$sent} reminders logged for {$overdue->count()} overdue invoices.");
        return self::SUCCESS;
    }
}
