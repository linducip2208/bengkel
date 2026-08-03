<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class JobcardRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'odometer_in' => 'required|integer|min:0',
            'in_date' => 'required|date',
            'next_service_date' => 'nullable|date',
            'next_service_km' => 'nullable|integer|min:0',
            'out_date' => 'nullable|date',
            'odometer_out' => 'nullable|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'odometer_in.required' => 'Odometer masuk wajib diisi.',
            'in_date.required' => 'Tanggal masuk wajib diisi.',
        ];
    }
}
