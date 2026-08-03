<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\VehicleRequest;
use App\Models\Customer;
use App\Models\FuelType;
use App\Models\Vehicle;
use App\Models\VehicleImage;
use App\Models\VehicleBrand;
use App\Models\VehicleType;
use App\Services\VehicleService;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    public function __construct(protected VehicleService $service) {}

    public function index(Request $request)
    {
        $vehicles = $this->service->list($request->only(['search', 'customer_id', 'vehicle_type_id', 'per_page']));
        $customers = Customer::orderBy('name')->get();
        $vehicleTypes = VehicleType::orderBy('vehicle_type')->get();
        return view('vehicles.index', compact('vehicles', 'customers', 'vehicleTypes'));
    }

    public function create()
    {
        $customers = Customer::orderBy('name')->get();
        $vehicleTypes = VehicleType::orderBy('vehicle_type')->get();
        $vehicleBrands = VehicleBrand::orderBy('vehicle_brand')->get();
        $fuelTypes = FuelType::orderBy('fuel_type')->get();
        return view('vehicles.create', compact('customers', 'vehicleTypes', 'vehicleBrands', 'fuelTypes'));
    }

    public function store(VehicleRequest $request)
    {
        $this->service->create($request->validated());
        return redirect()->route('vehicles.index')->with('success', 'Kendaraan berhasil ditambahkan.');
    }

    public function show($id)
    {
        $vehicle = Vehicle::findOrFail($id);
        $nextService = $this->service->predictNextService($vehicle);
        $serviceHistory = $this->service->getServiceHistory($vehicle);
        return view('vehicles.show', compact('vehicle', 'nextService', 'serviceHistory'));
    }

    public function edit($id)
    {
        $vehicle = Vehicle::findOrFail($id);
        $customers = Customer::orderBy('name')->get();
        $vehicleTypes = VehicleType::orderBy('vehicle_type')->get();
        $vehicleBrands = VehicleBrand::orderBy('vehicle_brand')->get();
        $fuelTypes = FuelType::orderBy('fuel_type')->get();
        return view('vehicles.edit', compact('vehicle', 'customers', 'vehicleTypes', 'vehicleBrands', 'fuelTypes'));
    }

    public function update(VehicleRequest $request, $id)
    {
        $vehicle = Vehicle::findOrFail($id);
        $this->service->update($vehicle, $request->validated());
        return redirect()->route('vehicles.index')->with('success', 'Kendaraan berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $vehicle = Vehicle::findOrFail($id);
        $this->service->delete($vehicle);
        return redirect()->route('vehicles.index')->with('success', 'Kendaraan berhasil dihapus.');
    }

    public function uploadImage(Request $request, $id)
    {
        $request->validate([
            'image' => ['required', 'image', 'max:5120'],
            'caption' => ['nullable', 'string', 'max:255'],
        ]);

        $vehicle = Vehicle::findOrFail($id);
        $this->service->uploadImage($vehicle, $request->file('image'), $request->input('caption'));

        return back()->with('success', 'Foto berhasil diunggah.');
    }

    public function deleteImage($image)
    {
        $vehicleImage = VehicleImage::findOrFail($image);
        $vehicleImage->delete();
        return back()->with('success', 'Foto berhasil dihapus.');
    }
}
