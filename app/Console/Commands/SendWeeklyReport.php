<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\EmailLog;
use App\Models\Service;
use App\Models\StockRecord;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class SendWeeklyReport extends Command
{
    protected $signature = 'reports:weekly-email';
    protected $description = 'Send weekly summary report email to admin/owner';

    public function handle(): int
    {
        $startOfWeek = now()->startOfWeek();
        $endOfWeek = now()->endOfWeek();
        $appName = config('app.name', 'Bengkel');

        $totalServices = Service::withoutGlobalScopes()
            ->whereBetween('service_date', [$startOfWeek, $endOfWeek])
            ->count();

        $totalRevenue = Service::withoutGlobalScopes()
            ->whereBetween('service_date', [$startOfWeek, $endOfWeek])
            ->sum('charge');

        $topTechnician = DB::table('service_technicians')
            ->join('services', 'service_technicians.service_id', '=', 'services.id')
            ->join('users', 'service_technicians.user_id', '=', 'users.id')
            ->whereBetween('services.service_date', [$startOfWeek, $endOfWeek])
            ->whereNull('services.deleted_at')
            ->groupBy('service_technicians.user_id', 'users.name')
            ->orderByRaw('COUNT(*) DESC')
            ->select('users.name', DB::raw('COUNT(*) as job_count'))
            ->first();

        $newCustomers = Customer::withoutGlobalScopes()
            ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
            ->count();

        $lowStockCount = StockRecord::withoutGlobalScopes()
            ->where('minimum_stock', '>', 0)
            ->whereRaw('quantity <= minimum_stock')
            ->count();

        $topTechName = $topTechnician ? $topTechnician->name . ' (' . $topTechnician->job_count . ' jobs)' : 'N/A';
        $formattedRevenue = 'Rp ' . number_format((float) $totalRevenue, 0, ',', '.');
        $formattedDate = $startOfWeek->format('d M Y') . ' - ' . $endOfWeek->format('d M Y');
        $lowStockColor = $lowStockCount > 0 ? '#dc2626' : '#059669';

        $html = <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;padding:20px;color:#333;">
    <div style="background:linear-gradient(135deg,#4F46E5,#7C3AED);color:white;padding:24px;border-radius:12px 12px 0 0;">
        <h2 style="margin:0;font-size:22px;">{$appName} — Laporan Mingguan</h2>
        <p style="margin:8px 0 0;opacity:.85;font-size:14px;">Periode: {$formattedDate}</p>
    </div>

    <div style="background:#fff;padding:24px;border:1px solid #e5e7eb;border-top:none;border-radius:0 0 12px 12px;">
        <table style="width:100%;border-collapse:collapse;">
            <tr style="border-bottom:1px solid #f3f4f6;">
                <td style="padding:14px 8px;font-weight:600;color:#6b7280;font-size:14px;">Total Service</td>
                <td style="padding:14px 8px;text-align:right;font-size:18px;font-weight:700;">{$totalServices}</td>
            </tr>
            <tr style="border-bottom:1px solid #f3f4f6;">
                <td style="padding:14px 8px;font-weight:600;color:#6b7280;font-size:14px;">Total Revenue</td>
                <td style="padding:14px 8px;text-align:right;font-size:18px;font-weight:700;color:#059669;">{$formattedRevenue}</td>
            </tr>
            <tr style="border-bottom:1px solid #f3f4f6;">
                <td style="padding:14px 8px;font-weight:600;color:#6b7280;font-size:14px;">Top Technician</td>
                <td style="padding:14px 8px;text-align:right;font-size:16px;font-weight:600;">{$topTechName}</td>
            </tr>
            <tr style="border-bottom:1px solid #f3f4f6;">
                <td style="padding:14px 8px;font-weight:600;color:#6b7280;font-size:14px;">New Customers</td>
                <td style="padding:14px 8px;text-align:right;font-size:18px;font-weight:700;color:#2563eb;">{$newCustomers}</td>
            </tr>
            <tr>
                <td style="padding:14px 8px;font-weight:600;color:#6b7280;font-size:14px;">Low Stock Items</td>
                <td style="padding:14px 8px;text-align:right;font-size:18px;font-weight:700;color:{$lowStockColor};">{$lowStockCount}</td>
            </tr>
        </table>

        <div style="margin-top:24px;padding:16px;background:#f9fafb;border-radius:8px;font-size:13px;color:#6b7280;">
            Laporan ini dikirim otomatis setiap hari Senin pukul 07:00 oleh sistem {$appName}.
        </div>
    </div>
</body>
</html>
HTML;

        $toAddress = config('mail.from.address');
        $subject = "{$appName} — Laporan Mingguan ({$formattedDate})";

        try {
            Mail::html($html, function ($message) use ($toAddress, $subject) {
                $message->to($toAddress)
                    ->subject($subject);
            });

            EmailLog::create([
                'to' => $toAddress,
                'subject' => $subject,
                'body' => $html,
                'status' => 'sent',
            ]);

            $this->info("Weekly report sent to {$toAddress}");
            $this->info("Services: {$totalServices} | Revenue: {$formattedRevenue} | Top Tech: {$topTechName} | New Customers: {$newCustomers} | Low Stock: {$lowStockCount}");
        } catch (\Throwable $e) {
            EmailLog::create([
                'to' => $toAddress,
                'subject' => $subject,
                'body' => $html,
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            $this->error("Failed to send weekly report: " . $e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
