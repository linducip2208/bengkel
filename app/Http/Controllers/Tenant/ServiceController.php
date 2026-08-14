<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Services\ServiceService;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function __construct(protected ServiceService $service) {}

    public function index(Request $request) { return $this->service->index($request); }
    public function create() { return $this->service->create(); }
    public function store(Request $request) { return $this->service->store($request); }
    public function show($id) { return $this->service->show($id); }
    public function edit($id) { return $this->service->edit($id); }
    public function update(Request $request, $id) { return $this->service->update($request, $id); }
    public function destroy($id) { return $this->service->destroy($id); }
    public function complete(Request $request, $id) { return $this->service->complete($request, $id); }
    public function start($id) { return $this->service->startService($id); }
    public function advance($id) { return $this->service->advanceWorkflow($id); }
    public function uploadImage(Request $request, $id) { return $this->service->uploadImage($request, $id); }
    public function searchCustomers(Request $request) { return $this->service->searchCustomers($request); }
    public function vehiclesByCustomer($customer) { return $this->service->vehiclesByCustomer($customer); }

    public function printNextServiceSticker(Service $service)
    {
        $service->load(['customer', 'vehicle.vehicleBrand', 'vehicle.vehicleType', 'repairCategory', 'jobcardDetail']);

        $companyName = config('app.name');
        $nextServiceDate = $service->jobcardDetail?->next_service_date;
        $nextServiceKm = $service->jobcardDetail?->next_service_km;

        if (!$nextServiceDate || !$nextServiceKm) {
            $calculated = app(\App\Services\JobcardService::class)->calculateNextService($service);
            $nextServiceDate = $nextServiceDate ?? $calculated['next_service_date'] ?? null;
            $nextServiceKm = $nextServiceKm ?? $calculated['next_service_km'] ?? null;
        }

        return view('services.next-service-sticker', compact(
            'service', 'companyName', 'nextServiceDate', 'nextServiceKm'
        ));
    }

    public function history(Request $request)
    {
        $services = \App\Models\Service::with(['customer', 'vehicle', 'repairCategory', 'technicians'])
            ->where('done_status', 2)
            ->when($request->filled('date_from'), fn($q) => $q->whereDate('service_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn($q) => $q->whereDate('service_date', '<=', $request->date_to))
            ->when($request->filled('customer_search'), fn($q) => $q->whereHas('customer', fn($c) => $c->where('name', 'like', '%' . $request->customer_search . '%')))
            ->when($request->filled('vehicle_search'), fn($q) => $q->whereHas('vehicle', fn($v) => $v->where('number_plate', 'like', '%' . $request->vehicle_search . '%')))
            ->when($request->filled('technician'), fn($q) => $q->whereHas('technicians', fn($t) => $t->where('users.id', $request->technician)))
            ->latest('completed_at')
            ->paginate(20)
            ->withQueryString();

        $technicians = \App\Models\User::role('mekanik')->get();
        $customers = \App\Models\Customer::orderBy('name')->get();

        return view('services.history', compact('services', 'technicians', 'customers'));
    }
}
