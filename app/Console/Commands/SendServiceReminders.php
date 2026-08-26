<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\Service;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class SendServiceReminders extends Command
{
    protected $signature = 'services:send-reminders';

    protected $description = 'Send H-1 reminders for upcoming services (notification queue + audit log)';

    public function handle(NotificationService $notifications): int
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
            if (! $customer || (! $customer->phone && ! $customer->email)) {
                continue;
            }

            $plate = $service->vehicle?->number_plate ?? '-';

            // Real customer notification — NotificationService no-ops safely
            // when the template is missing/disabled, so this is deploy-safe.
            try {
                $notifications->send('service-reminder', $customer, [
                    'customer_name' => $customer->name,
                    'job_no' => $service->job_no,
                    'plate' => $plate,
                    'service_date' => $service->service_date?->format('d M Y'),
                    'workshop_name' => config('app.name'),
                ]);
            } catch (\Throwable $e) {
                \Log::warning("Service reminder send failed for {$service->job_no}: {$e->getMessage()}");
            }

            $this->line("Reminder: {$customer->name} | {$plate} | Tomorrow");

            ActivityLog::create([
                'user_id' => null,
                'event' => 'service_reminder',
                'description' => "Pengingat service besok: {$service->job_no} ke {$customer->name}",
                'subject_type' => Service::class,
                'subject_id' => $service->id,
            ]);
            $sent++;
        }

        $this->info("Done. {$sent} reminders sent for tomorrow's services.");

        return self::SUCCESS;
    }
}
