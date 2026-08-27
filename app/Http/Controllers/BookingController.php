<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\FuelType;
use App\Models\RepairCategory;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleBrand;
use App\Models\VehicleType;
use App\Services\ServiceService;
use App\Support\IdentityNormalizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    /** Public: form booking */
    public function publicForm()
    {
        $branches = Branch::where('is_active', true)->orderBy('name')->get();
        $categories = RepairCategory::where('is_active', true)->orderBy('repair_category_name')->get();
        $technicians = User::role('mekanik')->where('is_active', true)->orderBy('name')->get();

        return view('public.booking-form', compact('branches', 'categories', 'technicians'));
    }

    public function publicStore(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:30',
            'email' => 'nullable|email|max:255',
            'vehicle_plate' => 'nullable|string|max:20',
            'vehicle_brand' => 'nullable|string|max:50',
            'vehicle_model' => 'nullable|string|max:100',
            'repair_category_id' => 'nullable|exists:repair_categories,id',
            'technician_id' => 'nullable|exists:users,id',
            'booking_at' => 'required|date|after:now',
            'complaint' => 'nullable|string|max:1000',
        ]);

        $validated['technician_id'] = $validated['technician_id'] ?? null;

        if ($validated['technician_id'] && Booking::technicianIsBusy($validated['technician_id'], $validated['booking_at'])) {
            return back()->withInput()->with('warning', 'Teknisi pilihan sudah memiliki booking/service pada tanggal tersebut. Silakan pilih teknisi lain atau ubah jadwal.');
        }

        Booking::withoutGlobalScopes()->create($validated + ['status' => 'pending']);

        return view('public.booking-success', ['name' => $validated['name'], 'booking_at' => $validated['booking_at']]);
    }

    /** Admin */
    public function adminIndex(Request $request)
    {
        $query = Booking::with('customer', 'service', 'technician');
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('technician_id')) {
            $query->where('technician_id', $request->technician_id);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('booking_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('booking_at', '<=', $request->date_to);
        }
        $bookings = $query->latest('booking_at')->paginate(20)->withQueryString();

        $summary = [
            'pending' => Booking::where('status', 'pending')->count(),
            'today' => Booking::whereDate('booking_at', today())->count(),
            'this_week' => Booking::whereBetween('booking_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
        ];

        $technicians = User::role(['mekanik', 'service_advisor'])->orderBy('name')->get();

        return view('bookings.index', compact('bookings', 'summary', 'technicians'));
    }

    public function adminUpdate(Request $request, Booking $booking)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,confirmed,in_progress,done,cancelled',
            'admin_notes' => 'nullable|string',
            'technician_id' => 'nullable|exists:users,id',
        ]);
        $validated['technician_id'] = $validated['technician_id'] ?? null;

        if ($validated['technician_id'] && Booking::technicianIsBusy($validated['technician_id'], $booking->booking_at, $booking->id)) {
            return back()->with('warning', 'Teknisi sudah memiliki booking/service pada tanggal tersebut.');
        }

        $booking->update($validated);

        return back()->with('success', 'Status booking diperbarui.');
    }

    public function adminDestroy(Booking $booking)
    {
        $booking->delete();

        return back()->with('success', 'Booking dihapus.');
    }

    public function convertToService(Booking $booking)
    {
        try {
            return DB::transaction(function () use ($booking) {
                // Lock + re-check: concurrent double-click converts once only.
                $locked = Booking::query()->whereKey($booking->id)->lockForUpdate()->first();

                if ($locked->status !== 'pending' && $locked->status !== 'confirmed') {
                    return back()->with('error', 'Hanya booking dengan status pending/confirmed yang bisa dikonversi.');
                }
                if ($locked->service_id) {
                    return back()->with('error', 'Booking ini sudah dikonversi ke service.');
                }

                // Find or create customer by phone
                $phone = IdentityNormalizer::indonesianPhone($locked->phone);
                $email = IdentityNormalizer::email($locked->email);
                $customer = Customer::withoutGlobalScopes()->firstOrCreate(
                    ['phone' => $phone],
                    ['name' => $locked->name, 'email' => $email]
                );

                // services.vehicle_id is NOT NULL: reuse the customer's first
                // matching vehicle or register one from the booking.
                $vehicle = null;
                if ($locked->vehicle_plate) {
                    $plate = IdentityNormalizer::vehiclePlate($locked->vehicle_plate);
                    $vehicle = Vehicle::withoutGlobalScopes()->where('number_plate', $plate)->first();
                    if ($vehicle && $vehicle->customer_id !== $customer->id) {
                        throw new \RuntimeException('Nomor polisi sudah terdaftar pada pelanggan lain; periksa identitas booking.');
                    }
                    if (! $vehicle) {
                        $vehicle = Vehicle::withoutGlobalScopes()->create([
                            'customer_id' => $customer->id,
                            'number_plate' => $plate,
                            'model_name' => trim(($locked->vehicle_brand ?: '').' '.($locked->vehicle_model ?: '')) ?: null,
                        ]);
                    }
                } else {
                    $vehicle = Vehicle::withoutGlobalScopes()
                        ->where('customer_id', $customer->id)
                        ->first();
                    if (! $vehicle) {
                        // Minimal placeholder so the job card can be opened;
                        // the service advisor completes it at check-in.
                        $fuelId = FuelType::query()->value('id');
                        $typeId = VehicleType::query()->value('id');
                        $brand = $typeId ? VehicleBrand::where('vehicle_type_id', $typeId)->value('id') : null;

                        if (! $typeId || ! $brand || ! $fuelId) {
                            throw new \RuntimeException('Data master kendaraan (tipe/merek/bahan bakar) belum lengkap — lengkapi terlebih dahulu.');
                        }

                        $vehicle = Vehicle::create([
                            'customer_id' => $customer->id,
                            'vehicle_type_id' => $typeId,
                            'vehicle_brand_id' => $brand,
                            'fuel_type_id' => $fuelId,
                            'number_plate' => 'PLAT-BELUM-DISET-'.$locked->id,
                            'model_name' => trim(($locked->vehicle_brand ?: '').' '.($locked->vehicle_model ?: '')) ?: 'Belum diisi',
                        ]);
                    }
                }

                // services.repair_category_id is NOT NULL: fall back to a
                // general category instead of crashing on un-categorized bookings.
                $repairCategoryId = $locked->repair_category_id
                    ?? RepairCategory::query()->value('id');
                if (! $repairCategoryId) {
                    $repairCategoryId = RepairCategory::createWithUniqueSlug([
                        'repair_category_name' => 'Lain-lain',
                        'is_active' => true,
                    ])->id;
                }

                // Create service
                $jobNo = app(ServiceService::class)->generateJobNo();
                $service = \App\Models\Service::create([
                    'job_no' => $jobNo,
                    'customer_id' => $customer->id,
                    'vehicle_id' => $vehicle->id,
                    'repair_category_id' => $repairCategoryId,
                    'service_date' => $locked->booking_at,
                    'description' => $locked->complaint,
                    'title' => $locked->complaint ?: 'Servis dari booking',
                    'done_status' => 0,
                    'workflow_status' => 0,
                    'created_by' => auth()->id() ?? 1,
                    'branch_id' => $locked->branch_id,
                ]);

                $locked->update([
                    'customer_id' => $customer->id,
                    'service_id' => $service->id,
                    'status' => 'confirmed',
                ]);

                ActivityLog::record('booking.convert', $locked, "Booking dikonversi ke Service {$jobNo}");

                return redirect()->route('services.show', $service)
                    ->with('success', 'Booking berhasil dikonversi ke Service #'.$jobNo);
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function calendar()
    {
        return view('bookings.calendar');
    }

    public function calendarEvents()
    {
        $start = request('start', now()->startOfMonth()->toDateString());
        $end = request('end', now()->endOfMonth()->toDateString());
        $bookings = Booking::with('technician')->whereBetween('booking_at', [$start, $end])->get()->map(fn ($b) => [
            'id' => $b->id, 'title' => ($b->technician?->name ? $b->technician->name.' - ' : '').($b->name ?? $b->customer->name ?? 'Booking').' - '.($b->vehicle_plate ?? ''),
            'start' => $b->booking_at->format('Y-m-d\TH:i'), 'backgroundColor' => $b->status === 'confirmed' ? '#10b981' : '#f59e0b', 'url' => route('bookings.index'),
        ]);
        $services = Service::with('customer')->whereBetween('service_date', [$start, $end])->get()->map(fn ($s) => [
            'id' => 'svc-'.$s->id, 'title' => '🔧 '.($s->customer->name ?? 'Service').' - '.$s->title,
            'start' => $s->service_date->format('Y-m-d\TH:i'), 'backgroundColor' => '#3b82f6', 'url' => route('services.show', $s),
        ]);

        return response()->json($bookings->concat($services));
    }
}
