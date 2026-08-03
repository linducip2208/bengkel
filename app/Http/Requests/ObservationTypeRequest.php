<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ObservationTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('observation_type')?->id;

        return [
            'observation_type' => ['required', 'string', 'max:255', Rule::unique('observation_types')->ignore($id)],
        ];
    }
}
