<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ServiceRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'customer_id' => 'required|exists:customers,id',
            'vehicle_id' => 'required|exists:vehicles,id',
            'repair_category_id' => 'required|exists:repair_categories,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'service_date' => 'required|date',
            'charge' => 'nullable|numeric|min:0',
            'estimated_hours' => 'nullable|numeric|min:0.5|max:24',
            'done_status' => 'nullable|integer|in:0,1,2',
            'assign_to' => 'nullable|array',
            'assign_to.*' => 'exists:users,id',
            'service_advisor_id' => 'nullable|exists:users,id',
            'products' => 'nullable|array',
            'products.*.product_id' => 'required_with:products|exists:products,id',
            'products.*.product_name' => 'required|string|max:255',
            'products.*.quantity' => 'required|numeric|min:0.01',
            'products.*.price' => 'required|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.required' => 'Pelanggan wajib dipilih.',
            'vehicle_id.required' => 'Kendaraan wajib dipilih.',
            'repair_category_id.required' => 'Kategori perbaikan wajib dipilih.',
            'title.required' => 'Judul servis wajib diisi.',
            'service_date.required' => 'Tanggal servis wajib diisi.',
        ];
    }
}
