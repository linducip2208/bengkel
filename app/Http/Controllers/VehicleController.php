<?php

namespace App\Http\Controllers;

use App\Http\Requests\VehicleRequest;
use App\Models\Customer;
use App\Models\FuelType;
use App\Models\Vehicle;
use App\Models\VehicleBrand;
use App\Models\VehicleImage;
use App\Models\VehicleType;
use App\Services\VehicleService;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    public function __construct(
        protected VehicleService $vehicleService
    ) {}

    public function index(Request $request)
    {
        $vehicles = $this->vehicleService->list($request->only(['search', 'customer_id', 'vehicle_type_id', 'per_page']));
        $customers = Customer::orderBy('name')->get(['id', 'name']);
        $vehicleTypes = VehicleType::orderBy('name')->get();

        return view('vehicles.index', compact('vehicles', 'customers', 'vehicleTypes'));
    }

    public function create()
    {
        $customers = Customer::orderBy('name')->get(['id', 'name']);
        $vehicleTypes = VehicleType::orderBy('name')->get();
        $vehicleBrands = VehicleBrand::with('vehicleType')->orderBy('name')->get();
        $fuelTypes = FuelType::orderBy('name')->get();

        return view('vehicles.create', compact('customers', 'vehicleTypes', 'vehicleBrands', 'fuelTypes'));
    }

    public function store(VehicleRequest $request)
    {
        $this->vehicleService->create($request->validated());

        return redirect()->route('vehicles.index')
            ->with('success', 'Kendaraan berhasil ditambahkan.');
    }

    public function show(Vehicle $vehicle)
    {
        $vehicle->load(['customer', 'vehicleType', 'vehicleBrand', 'fuelType', 'images']);
        $serviceHistory = $this->vehicleService->getServiceHistory($vehicle);
        $nextService = $this->vehicleService->predictNextService($vehicle);

        return view('vehicles.show', compact('vehicle', 'serviceHistory', 'nextService'));
    }

    public function edit(Vehicle $vehicle)
    {
        $customers = Customer::orderBy('name')->get(['id', 'name']);
        $vehicleTypes = VehicleType::orderBy('name')->get();
        $vehicleBrands = VehicleBrand::with('vehicleType')->orderBy('name')->get();
        $fuelTypes = FuelType::orderBy('name')->get();

        return view('vehicles.edit', compact('vehicle', 'customers', 'vehicleTypes', 'vehicleBrands', 'fuelTypes'));
    }

    public function update(VehicleRequest $request, Vehicle $vehicle)
    {
        $this->vehicleService->update($vehicle, $request->validated());

        return redirect()->route('vehicles.index')
            ->with('success', 'Data kendaraan berhasil diperbarui.');
    }

    public function destroy(Vehicle $vehicle)
    {
        $this->vehicleService->delete($vehicle);

        return redirect()->route('vehicles.index')
            ->with('success', 'Kendaraan berhasil dihapus.');
    }

    public function uploadImage(Request $request, Vehicle $vehicle)
    {
        $request->validate([
            'image' => ['required', 'image', 'max:5120'],
            'caption' => ['nullable', 'string', 'max:255'],
        ]);

        $this->vehicleService->uploadImage(
            $vehicle,
            $request->file('image'),
            $request->input('caption')
        );

        return back()->with('success', 'Foto berhasil diunggah.');
    }

    public function deleteImage(VehicleImage $image)
    {
        \Storage::disk('public')->delete($image->image_path);
        $image->delete();

        return back()->with('success', 'Foto berhasil dihapus.');
    }
}
