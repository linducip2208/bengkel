<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Color;
use App\Models\FuelType;
use App\Models\PaymentMethod;
use App\Models\ProductType;
use App\Models\ProductUnit;
use App\Models\RepairCategory;
use App\Models\TaxRate;
use App\Models\VehicleBrand;
use App\Models\VehicleType;
use Illuminate\Http\JsonResponse;

class ApiMasterDataController extends Controller
{
    public function all(): JsonResponse
    {
        return response()->json([
            'vehicle_types' => VehicleType::orderBy('vehicle_type')->get(['id', 'vehicle_type']),
            'vehicle_brands' => VehicleBrand::with('vehicleType')->orderBy('vehicle_brand')->get()->map(fn($b) => [
                'id' => $b->id,
                'vehicle_type_id' => $b->vehicle_type_id,
                'vehicle_brand' => $b->vehicle_brand,
            ]),
            'fuel_types' => FuelType::orderBy('fuel_type')->get(['id', 'fuel_type']),
            'colors' => Color::orderBy('color')->get(['id', 'color']),
            'payment_methods' => PaymentMethod::orderBy('payment')->get(['id', 'payment']),
            'tax_rates' => TaxRate::orderBy('taxname')->get(['id', 'taxname', 'tax']),
            'repair_categories' => RepairCategory::orderBy('repair_category_name')->get(['id', 'repair_category_name', 'slug']),
            'product_types' => ProductType::orderBy('type')->get(['id', 'type']),
            'product_units' => ProductUnit::orderBy('name')->get(['id', 'name']),
            'service_statuses' => [
                ['value' => 0, 'label' => 'Pending'],
                ['value' => 1, 'label' => 'In Progress'],
                ['value' => 2, 'label' => 'Completed'],
            ],
            'invoice_payment_statuses' => [
                ['value' => 0, 'label' => 'Unpaid'],
                ['value' => 1, 'label' => 'Partially Paid'],
                ['value' => 2, 'label' => 'Paid'],
            ],
            'purchase_statuses' => [
                ['value' => 'draft', 'label' => 'Draft'],
                ['value' => 'ordered', 'label' => 'Ordered'],
                ['value' => 'received', 'label' => 'Received'],
                ['value' => 'cancelled', 'label' => 'Cancelled'],
            ],
        ]);
    }
}
