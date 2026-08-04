<?php

namespace App\Services;

use App\Models\Reminder;
use App\Models\Service;
use App\Models\Vehicle;

class ReminderService
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function createReminder(array $data): Reminder
    {
        $data['created_by'] = $data['created_by'] ?? auth()->id();
        $data['sent'] = false;
        return Reminder::create($data);
    }

    public function sendDueReminders(): int
    {
        $reminders = Reminder::with(['customer', 'vehicle'])
            ->where('sent', false)
            ->where('reminder_date', '<=', now()->toDateString())
            ->get();

        $count = 0;
        foreach ($reminders as $reminder) {
            $this->sendSingleReminder($reminder);
            $count++;
        }

        return $count;
    }

    public function sendSingleReminder(Reminder $reminder): void
    {
        $customer = $reminder->customer;
        $vehicle = $reminder->vehicle;

        if (!$customer) {
            return;
        }

        $data = [
            'customer_name' => $customer->name,
            'vehicle_plate' => $vehicle->number_plate ?? '',
            'reminder_date' => $reminder->reminder_date->format('d/m/Y'),
            'reminder_type' => $reminder->reminder_type,
            'workshop_name' => config('app.name', 'Aplikasi Bengkel Terbaik'),
            'workshop_phone' => app(SettingsService::class)->get('company_phone', ''),
        ];

        $templateSlug = match ($reminder->reminder_type) {
            'service' => 'service_reminder',
            'insurance' => 'insurance_reminder',
            'stnk' => 'stnk_reminder',
            default => 'service_reminder',
        };

        $this->notificationService->send($templateSlug, $customer, $data);

        $reminder->update([
            'sent' => true,
            'sent_at' => now(),
        ]);
    }

    public function scheduleServiceReminder(Service $service): void
    {
        $jobcard = $service->jobcardDetail()->first();
        if (!$jobcard || !$jobcard->next_service_date) {
            return;
        }

        $exists = Reminder::where('service_id', $service->id)
            ->where('reminder_type', 'service')
            ->exists();

        if ($exists) {
            return;
        }

        $this->createReminder([
            'customer_id' => $service->customer_id,
            'vehicle_id' => $service->vehicle_id,
            'service_id' => $service->id,
            'reminder_type' => 'service',
            'reminder_date' => \Carbon\Carbon::parse($jobcard->next_service_date)->subDays(7)->toDateString(),
            'message' => "Reminder: Service is due for vehicle {$service->vehicle->number_plate} on {$jobcard->next_service_date}",
        ]);
    }

    /*
    public function scheduleInsuranceReminder(Vehicle $vehicle): void
    {
        $insuranceExpiry = $vehicle->insurance_expiry ?? null;
        if (!$insuranceExpiry) {
            return;
        }

        $exists = Reminder::where('vehicle_id', $vehicle->id)
            ->where('reminder_type', 'insurance')
            ->where('reminder_date', '>=', now()->subMonth()->toDateString())
            ->exists();

        if ($exists) {
            return;
        }

        $this->createReminder([
            'customer_id' => $vehicle->customer_id,
            'vehicle_id' => $vehicle->id,
            'reminder_type' => 'insurance',
            'reminder_date' => \Carbon\Carbon::parse($insuranceExpiry)->subDays(14)->toDateString(),
            'message' => "Reminder: Insurance expires for vehicle {$vehicle->number_plate} on {$insuranceExpiry}",
        ]);
    }

    public function scheduleStnkReminder(Vehicle $vehicle): void
    {
        $stnkExpiry = $vehicle->stnk_expiry ?? null;
        if (!$stnkExpiry) {
            return;
        }

        $exists = Reminder::where('vehicle_id', $vehicle->id)
            ->where('reminder_type', 'stnk')
            ->where('reminder_date', '>=', now()->subMonth()->toDateString())
            ->exists();

        if ($exists) {
            return;
        }

        $this->createReminder([
            'customer_id' => $vehicle->customer_id,
            'vehicle_id' => $vehicle->id,
            'reminder_type' => 'stnk',
            'reminder_date' => \Carbon\Carbon::parse($stnkExpiry)->subDays(30)->toDateString(),
            'message' => "Reminder: STNK expires for vehicle {$vehicle->number_plate} on {$stnkExpiry}",
        ]);
    }
    */
}
