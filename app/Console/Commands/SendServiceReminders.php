<?php

namespace App\Console\Commands;

use App\Models\Service;
use Illuminate\Console\Command;

class SendServiceReminders extends Command
{
    protected $signature = 'services:send-reminders';
    protected $description = 'Send H-1 reminders for upcoming services';

    public function handle(): int
    {
        $tomorrow = now()->addDay()->toDateString();

        $services = Service::with(['customer', 'vehicle'])
            ->whereDate('service_date', $tomorrow)
            ->where('done_status', '<', 2)
            ->get();

        if ($services->isEmpty()) {
            $this->info('No upcoming services for tomorrow.');
            return self::SUCCESS;
        }

        $sent = 0;
        foreach ($services as $service) {
            $customer = $service->customer;
            if (!$customer?->phone) continue;

            $phone = preg_replace('/[^0-9]/', '', $customer->phone);
            if (substr($phone, 0, 1) === '0') $phone = '62' . substr($phone, 1);

            $plate = $service->vehicle?->number_plate ?? '-';
            $this->line("Reminder: {$customer->name} | {$plate} | Tomorrow");

            \App\Models\ActivityLog::create([
                'user_id' => null,
                'action' => 'service_reminder',
                'description' => "Pengingat service besok: {$service->job_no} ke {$customer->name}",
                'model_type' => 'Service',
                'model_id' => $service->id,
            ]);
            $sent++;
        }

        $this->info("Done. {$sent} reminders logged for tomorrow's services.");
        return self::SUCCESS;
    }
}
