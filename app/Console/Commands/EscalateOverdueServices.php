<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\Service;
use Illuminate\Console\Command;

class EscalateOverdueServices extends Command
{
    protected $signature = 'services:escalate-overdue';
    protected $description = 'Escalate overdue services beyond estimated hours to branch manager';

    public function handle(): int
    {
        $overdue = Service::with(['customer', 'vehicle', 'technicians'])
            ->whereNotNull('started_at')
            ->whereNull('completed_at')
            ->whereNotNull('estimated_hours')
            ->where('estimated_hours', '>', 0)
            ->whereIn('workflow_status', [2, 3, 4, 5, 6, 7, 8])
            ->get()
            ->filter(function ($service) {
                $elapsedHours = now()->diffInHours($service->started_at);
                return $elapsedHours > $service->estimated_hours;
            });

        if ($overdue->isEmpty()) {
            $this->info('Tidak ada service overdue.');
            return self::SUCCESS;
        }

        $sent = 0;
        $skip = 0;

        foreach ($overdue as $service) {
            $alreadyEscalated = ActivityLog::where('subject_type', Service::class)
                ->where('subject_id', $service->id)
                ->where('event', 'overdue_service')
                ->where('created_at', '>=', now()->subHours(4))
                ->exists();

            if ($alreadyEscalated) {
                $this->line("Skip (already escalated <4h): {$service->job_no}");
                $skip++;
                continue;
            }

            $elapsedHours = now()->diffInHours($service->started_at);
            $estHours = $service->estimated_hours;
            $overBy = round($elapsedHours - $estHours, 1);

            $customerName = $service->customer?->name ?? '-';
            $plate = $service->vehicle?->number_plate ?? '-';

            ActivityLog::create([
                'user_id' => null,
                'event' => 'overdue_service',
                'description' => "Service {$service->job_no} overdue {$overBy} jam dari estimasi {$estHours} jam. Pelanggan: {$customerName}, Kendaraan: {$plate}",
                'subject_type' => Service::class,
                'subject_id' => $service->id,
            ]);

            $this->line("Escalated: {$service->job_no} — {$customerName} — {$elapsedHours}j / {$estHours}j est. (+{$overBy}j)");
            $sent++;
        }

        $this->info("Done. {$sent} escalated, {$skip} skipped.");
        return self::SUCCESS;
    }
}
