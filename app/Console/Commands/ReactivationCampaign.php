<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Voucher;
use Illuminate\Console\Command;

class ReactivationCampaign extends Command
{
    protected $signature = 'marketing:reactivation';

    protected $description = 'Find dormant customers (no service >6 months) and send promo vouchers';

    public function handle(): int
    {
        $sixMonthsAgo = now()->subMonths(6);

        $dormantCustomers = Customer::withoutGlobalScopes()
            ->whereNotNull('phone')
            ->whereHas('services', function ($q) {
                $q->whereNotNull('service_date');
            })
            ->addSelect(['last_service_date' => Service::select('service_date')
                ->whereColumn('customer_id', 'customers.id')
                ->whereNotNull('service_date')
                ->latest('service_date')
                ->limit(1),
            ])
            ->whereHas('services', function ($q) use ($sixMonthsAgo) {
                $q->where('service_date', '<', $sixMonthsAgo);
            })
            ->take(50)
            ->get();

        if ($dormantCustomers->isEmpty()) {
            $this->info('No dormant customers found.');

            return self::SUCCESS;
        }

        $sent = 0;
        foreach ($dormantCustomers as $customer) {
            ActivityLog::record(
                'marketing.reactivation',
                $customer,
                "Reactivation campaign — dormant customer: {$customer->name} / {$customer->phone}"
            );

            $code = 'WAKE-'.$customer->id.'-'.rand(100, 999);
            Voucher::create([
                'code' => $code,
                'name' => 'Welcome Back Promo '.$customer->name,
                'type' => 'percent',
                'value' => 15,
                'min_purchase' => 0,
                'max_discount' => 75000,
                'valid_from' => now(),
                'valid_until' => now()->addDays(14),
                'is_active' => true,
                'description' => 'Auto-generated reactivation voucher for '.$customer->name,
                'usage_limit' => 1,
                'used_count' => 0,
            ]);

            $sent++;
        }

        $this->info("Reactivation campaign sent to {$sent} dormant customer(s).");

        return self::SUCCESS;
    }
}
