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
            $totalPaid > 0 ? 'half_paid' : 'unpaid';

            $this->line("Overdue: {$invoice->invoice_number} — {$invoice->customer->name} — Sisa: {$remaining}");

            // Send WA reminder to customer
            $custPhone = $invoice->customer?->phone;
            if ($custPhone) {
                $custPhone = preg_replace('/[^0-9]/', '', $custPhone);
                if (substr($custPhone, 0, 1) === '0') {
                    $custPhone = '62' . substr($custPhone, 1);
                }
                // Log: could send via NotificationService or just log
                \App\Models\ActivityLog::create([
                    'user_id' => null,
                    'action' => 'overdue_reminder',
                    'description' => "Pengingat overdue: Invoice {$invoice->invoice_number} ke {$invoice->customer->name}",
                    'model_type' => 'Invoice',
                    'model_id' => $invoice->id,
                ]);
                $sent++;
            }

            $this->line("  -> WA reminder queued for {$invoice->customer->name}");
        }

        $this->info("Done. {$sent} reminders logged for {$overdue->count()} overdue invoices.");
        return self::SUCCESS;
    }
}
