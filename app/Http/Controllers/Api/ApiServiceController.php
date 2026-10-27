<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ServiceImageResource;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use App\Services\ServiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ApiServiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Service::query()->with(['customer', 'vehicle', 'repairCategory', 'technicians']);

        if ($request->has('done_status')) {
            $query->where('done_status', $request->get('done_status'));
        }

        if ($customerId = $request->get('customer_id')) {
            $query->where('customer_id', $customerId);
        }

        if ($dateFrom = $request->get('date_from')) {
            $query->whereDate('service_date', '>=', $dateFrom);
        }

        if ($dateTo = $request->get('date_to')) {
            $query->whereDate('service_date', '<=', $dateTo);
        }

        $services = $query->latest()->paginate($request->get('per_page', 20));

        return response()->json(ServiceResource::collection($services)->response()->getData(true));
    }

    public function show(Service $service): JsonResponse
    {
        $service->load([
            'customer',
            'vehicle',
            'repairCategory',
            'technicians',
            'images',
            'jobcardDetail',
            'checkoutResults',
            'invoice',
        ]);

        return response()->json(new ServiceResource($service));
    }

    // NOTE: create/update/delete service via API sengaja tidak diekspos
    // (tidak ada route-nya). Service dibuat/diubah lewat web + workflow
    // agar validasi bisnis, branch scope, dan audit trail tetap terjaga.

    public function complete(Service $service): JsonResponse
    {
        try {
            $result = app(ServiceService::class)->executeComplete($service);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => $result['already'] ? 'Service sudah selesai sebelumnya.' : 'Service selesai.',
            'already_processed' => $result['already'],
            'service' => new ServiceResource($service->fresh(['invoice', 'technicians'])),
            'invoice_id' => $result['invoice']?->id,
        ]);
    }

    public function uploadImage(Request $request, Service $service): JsonResponse
    {
        $validated = $request->validate([
            'image' => ['required', 'image', 'mimes:jpeg,png,webp', 'max:5120'],
            'type' => ['nullable', 'string', 'max:50'],
            'caption' => ['nullable', 'string', 'max:255'],
        ]);

        // store() generates a random hashed filename on the public disk.
        $path = $request->file('image')->store('service-images', 'public');

        $image = $service->images()->create([
            'image_path' => $path,
            'type' => $validated['type'] ?? null,
            'caption' => $validated['caption'] ?? null,
        ]);

        return response()->json([
            'message' => 'Foto berhasil diunggah.',
            'data' => array_merge((new ServiceImageResource($image))->toArray($request), [
                'url' => url(Storage::url($path)),
            ]),
        ], 201);
    }

    public function deleteImage(Service $service, int $image): JsonResponse
    {
        // Scoped through the branch-checked service: cross-branch ids 404.
        $service->images()->findOrFail($image)->delete();

        return response()->json(['message' => 'Foto dihapus.']);
    }
}
