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
        $route = $this->route('observation_type');
        $id = is_object($route) ? $route->id : $route;

        return [
            'observation_type' => ['required', 'string', 'max:255', Rule::unique('observation_types')->ignore($id)],
        ];
    }
}
