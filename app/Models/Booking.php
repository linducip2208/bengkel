<?php

namespace App\Models;

use App\Support\IdentityNormalizer;
use App\Traits\HasBranchScope;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'branch_id', 'customer_id', 'name', 'phone', 'email',
    'vehicle_plate', 'vehicle_brand', 'vehicle_model',
    'booking_at', 'complaint', 'status', 'service_id', 'admin_notes',
    'repair_category_id', 'technician_id',
])]
class Booking extends Model
{
    use HasBranchScope;

    protected static function booted(): void
    {
        static::saving(function (self $booking): void {
            $booking->phone = IdentityNormalizer::indonesianPhone($booking->phone);
            $booking->email = IdentityNormalizer::email($booking->email);
            $booking->vehicle_plate = IdentityNormalizer::vehiclePlate($booking->vehicle_plate);
        });
    }

    protected $casts = ['booking_at' => 'datetime'];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public static function technicianAvailability($date): array
    {
        $technicians = User::workshopStaffQuery()->where('is_active', true)->orderBy('name')->get();

        $busy = static::withoutGlobalScopes()
            ->whereDate('booking_at', Carbon::parse($date)->toDateString())
            ->whereNotNull('technician_id')
            ->whereIn('status', ['pending', 'confirmed', 'in_progress'])
            ->groupBy('technician_id')
            ->selectRaw('technician_id, COUNT(*) as total')
            ->pluck('total', 'technician_id');

        return $technicians->map(fn ($t) => [
            'id' => $t->id,
            'name' => $t->name,
            'bookings' => (int) $busy->get($t->id, 0),
            'available' => (int) $busy->get($t->id, 0) === 0,
        ])->all();
    }

    public static function technicianIsBusy(?int $technicianId, $date, ?int $excludeBookingId = null): bool
    {
        if (! $technicianId) {
            return false;
        }

        $day = Carbon::parse($date)->toDateString();

        $bookingBusy = static::withoutGlobalScopes()
            ->where('technician_id', $technicianId)
            ->whereDate('booking_at', $day)
            ->whereIn('status', ['pending', 'confirmed', 'in_progress'])
            ->when($excludeBookingId, fn ($q) => $q->where('id', '!=', $excludeBookingId))
            ->exists();

        if ($bookingBusy) {
            return true;
        }

        return Service::withoutGlobalScopes()
            ->whereDate('service_date', $day)
            ->where(function ($q) use ($technicianId) {
                $q->where('assign_to', $technicianId)
                    ->orWhereHas('technicians', fn ($t) => $t->where('users.id', $technicianId));
            })
            ->where('done_status', '<', 2)
            ->exists();
    }
}
