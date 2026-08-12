<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Voucher;
use Illuminate\Console\Command;

class SendBirthdayVouchers extends Command
{
    protected $signature = 'loyalty:birthday-vouchers';
    protected $description = 'Auto-generate birthday vouchers for customers whose birth_date matches today';

    public function handle(): int
    {
        $today = now();

        $customers = Customer::withoutGlobalScopes()
            ->whereNotNull('birth_date')
            ->whereRaw('MONTH(birth_date) = ?', [$today->month])
            ->whereRaw('DAY(birth_date) = ?', [$today->day])
            ->get();

        if ($customers->isEmpty()) {
            $this->info('No customers with birthday today.');
            return self::SUCCESS;
        }

        foreach ($customers as $customer) {
            $code = 'BDY-' . $customer->id . '-' . rand(100, 999);
            $voucher = Voucher::create([
                'code' => $code,
                'name' => 'Birthday Promo ' . $customer->name,
                'type' => 'percent',
                'value' => 10,
                'min_purchase' => 0,
                'max_discount' => 50000,
                'valid_from' => now(),
                'valid_until' => now()->addDays(30),
                'is_active' => true,
                'description' => 'Auto-generated birthday voucher for ' . $customer->name,
                'usage_limit' => 1,
                'used_count' => 0,
            ]);

            ActivityLog::record(
                'birthday.voucher',
                $customer,
                "Birthday voucher #{$voucher->code} ({$voucher->value}% up to Rp {$voucher->max_discount}) created for {$customer->name}"
            );

            $this->info("Birthday voucher created for {$customer->name} — code: {$code}");
        }

        $this->info("Total: {$customers->count()} birthday voucher(s) created.");
        return self::SUCCESS;
    }
}
