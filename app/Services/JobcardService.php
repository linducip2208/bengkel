<?php

namespace App\Services;

use App\Http\Requests\JobcardRequest;
use App\Models\Service;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JobcardService extends BaseService
{
    public function index(Request $request)
    {
        $jobcards = Service::with(['customer', 'vehicle', 'jobcardDetail', 'repairCategory'])
            ->whereHas('jobcardDetail')
            ->when($request->filled('status'), function ($q) use ($request) {
                $q->where('done_status', $request->status);
            })
            ->when($request->filled('date_from'), function ($q) use ($request) {
                $q->whereHas('jobcardDetail', fn ($j) => $j->whereDate('in_date', '>=', $request->date_from));
            })
            ->when($request->filled('date_to'), function ($q) use ($request) {
                $q->whereHas('jobcardDetail', fn ($j) => $j->whereDate('in_date', '<=', $request->date_to));
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('jobcards.index', compact('jobcards'));
    }

    public function store(Request $request)
    {
        $serviceId = $request->route('service');
        $service = Service::findOrFail($serviceId);
        $validated = JobcardRequest::createFrom($request)->validated();

        DB::transaction(function () use ($service, $validated) {
            $validated['jobcard_no'] = $service->job_no;
            $validated['customer_id'] = $service->customer_id;
            $validated['vehicle_id'] = $service->vehicle_id;

            $jobcard = $service->jobcardDetail;

            if ($jobcard) {
                $jobcard->update($validated);
            } else {
                $service->jobcardDetail()->create($validated);
            }
        });

        return redirect()
            ->route('services.show', $service)
            ->with('success', 'Jobcard berhasil disimpan.');
    }

    public function show($id)
    {
        $service = Service::with(['customer', 'vehicle', 'jobcardDetail', 'repairCategory', 'technicians'])->findOrFail($id);
        $nextService = $this->calculateNextService($service);

        return view('jobcards.show', compact('service', 'nextService'));
    }

    public function update(Request $request, $id)
    {
        $service = Service::findOrFail($id);
        $validated = JobcardRequest::createFrom($request)->validated();

        DB::transaction(function () use ($service, $validated) {
            $validated['jobcard_no'] = $service->job_no;
            $validated['customer_id'] = $service->customer_id;
            $validated['vehicle_id'] = $service->vehicle_id;

            $jobcard = $service->jobcardDetail;

            if ($jobcard) {
                $jobcard->update($validated);
            } else {
                $service->jobcardDetail()->create($validated);
            }
        });

        return redirect()
            ->route('services.show', $service)
            ->with('success', 'Jobcard berhasil diperbarui.');
    }

    public function print($id)
    {
        $service = Service::with([
            'customer', 'vehicle.vehicleType', 'vehicle.vehicleBrand',
            'jobcardDetail', 'repairCategory', 'technicians',
            'serviceObservationPoints.observationPoint',
        ])->findOrFail($id);

        $pdf = Pdf::loadView('jobcards.print', compact('service'));
        $pdf->setPaper('a4');

        return $pdf->download('jobcard-'.$service->job_no.'.pdf');
    }

    public function gatePass($id)
    {
        $service = Service::with(['customer', 'vehicle', 'jobcardDetail'])->findOrFail($id);

        $pdf = Pdf::loadView('jobcards.gatepass', compact('service'));
        $pdf->setPaper([0, 0, 210, 330], 'portrait');

        return $pdf->download('gatepass-'.$service->job_no.'.pdf');
    }

    public function calculateNextService(Service $service): array
    {
        $jobcard = $service->jobcardDetail;

        if (! $jobcard) {
            return ['next_service_date' => null, 'next_service_km' => null];
        }

        return [
            'next_service_date' => $jobcard->next_service_date ?? now()->addMonths(6)->toDateString(),
            'next_service_km' => $jobcard->next_service_km ?? ($jobcard->odometer_in + 10000),
        ];
    }
}
