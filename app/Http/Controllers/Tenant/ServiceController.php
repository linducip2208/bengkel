<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Service;
use App\Models\ServiceEstimate;
use App\Models\User;
use App\Services\EstimateService;
use App\Services\JobcardService;
use App\Services\ServiceService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ServiceController extends Controller
{
    public function __construct(protected ServiceService $service) {}

    public function index(Request $request)
    {
        return $this->service->index($request);
    }

    public function create()
    {
        return $this->service->create();
    }

    public function store(Request $request)
    {
        return $this->service->store($request);
    }

    public function show($id)
    {
        return $this->service->show($id);
    }

    public function edit($id)
    {
        return $this->service->edit($id);
    }

    public function update(Request $request, $id)
    {
        return $this->service->update($request, $id);
    }

    public function destroy($id)
    {
        return $this->service->destroy($id);
    }

    public function complete(Request $request, $id)
    {
        return $this->service->complete($request, $id);
    }

    public function start($id)
    {
        return $this->service->startService($id);
    }

    public function advance($id)
    {
        return $this->service->advanceWorkflow($id);
    }

    public function uploadImage(Request $request, $id)
    {
        return $this->service->uploadImage($request, $id);
    }

    public function searchCustomers(Request $request)
    {
        return $this->service->searchCustomers($request);
    }

    public function vehiclesByCustomer($customer)
    {
        return $this->service->vehiclesByCustomer($customer);
    }

    public function surveyLink(Service $service)
    {
        if (! $service->survey_token) {
            $service->update(['survey_token' => Str::random(32)]);
        }

        $url = route('survey.show', $service->survey_token);

        return response()->json([
            'url' => $url,
            'wa' => 'https://wa.me/?text='.urlencode('Mohon beri rating untuk service Anda: '.$url),
        ]);
    }

    public function printNextServiceSticker(Service $service)
    {
        $service->load(['customer', 'vehicle.vehicleBrand', 'vehicle.vehicleType', 'repairCategory', 'jobcardDetail']);

        $companyName = config('app.name');
        $nextServiceDate = $service->jobcardDetail?->next_service_date;
        $nextServiceKm = $service->jobcardDetail?->next_service_km;

        if (! $nextServiceDate || ! $nextServiceKm) {
            $calculated = app(JobcardService::class)->calculateNextService($service);
            $nextServiceDate = $nextServiceDate ?? $calculated['next_service_date'] ?? null;
            $nextServiceKm = $nextServiceKm ?? $calculated['next_service_km'] ?? null;
        }

        return view('services.next-service-sticker', compact(
            'service', 'companyName', 'nextServiceDate', 'nextServiceKm'
        ));
    }

    public function printConditionReport(Service $service)
    {
        $service->load([
            'customer',
            'vehicle.vehicleBrand',
            'vehicle.vehicleType',
            'serviceAdvisor',
            'technicians',
            'jobcardDetail',
            'serviceObservationPoints.observationPoint.observationType',
            'images',
        ]);

        return view('services.condition-report', [
            'service' => $service,
            'companyName' => config('app.name'),
        ]);
    }

    public function sendWA(Service $service)
    {
        $phone = $service->customer?->phone;
        if (! $phone) {
            return redirect()->back()->with('error', 'Nomor WA pelanggan tidak tersedia.');
        }

        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (substr($phone, 0, 1) === '0') {
            $phone = '62'.substr($phone, 1);
        }

        // Prefer the dedicated estimate flow when an estimate exists.
        $estimate = app(EstimateService::class)->latestActiveEstimate($service);
        if ($estimate !== null && $estimate->status !== ServiceEstimate::STATUS_DRAFT) {
            return app(EstimateController::class)->sendWA($estimate);
        }

        $token = $service->getOrCreateApprovalToken();
        $approveUrl = url('/approve/'.$token);
        $rejectUrl = url('/reject/'.$token);

        $message = "Halo {$service->customer->name}, mohon persetujuan estimasi servis:\n"
            ."*{$service->job_no}*\n"
            ."Kendaraan: {$service->vehicle?->number_plate}\n"
            ."Keluhan: {$service->title}\n"
            .'Estimasi biaya: Rp '.number_format($service->charge ?? 0, 0, ',', '.')."\n\n"
            ."Setujui: {$approveUrl}\n"
            ."Tolak: {$rejectUrl}\n\n"
            .'Terima kasih.';

        $url = "https://wa.me/{$phone}?text=".urlencode($message);

        return redirect()->away($url);
    }

    public function history(Request $request)
    {
        $services = Service::with(['customer', 'vehicle', 'repairCategory', 'technicians'])
            ->where('done_status', 2)
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('service_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('service_date', '<=', $request->date_to))
            ->when($request->filled('customer_search'), fn ($q) => $q->whereHas('customer', fn ($c) => $c->where('name', 'like', '%'.$request->customer_search.'%')))
            ->when($request->filled('vehicle_search'), fn ($q) => $q->whereHas('vehicle', fn ($v) => $v->where('number_plate', 'like', '%'.$request->vehicle_search.'%')))
            ->when($request->filled('technician'), fn ($q) => $q->whereHas('technicians', fn ($t) => $t->where('users.id', $request->technician)))
            ->latest('completed_at')
            ->paginate(20)
            ->withQueryString();

        $technicians = User::role('mekanik')->get();
        $customers = Customer::orderBy('name')->get();

        return view('services.history', compact('services', 'technicians', 'customers'));
    }
}
