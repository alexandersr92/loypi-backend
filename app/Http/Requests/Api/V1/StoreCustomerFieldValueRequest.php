<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerFieldValueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'values' => ['required', 'array', 'min:1'],
            'values.*.custom_field_id' => ['required', 'uuid', 'exists:custom_fields,id'],
            'values.*.string_value' => ['nullable', 'string'],
            'values.*.number_value' => ['nullable', 'numeric'],
            'values.*.date_value' => ['nullable', 'date'],
            'values.*.boolean_value' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'values.required' => 'Debes proporcionar al menos un valor.',
            'values.*.custom_field_id.required' => 'Cada valor debe tener un custom_field_id.',
            'values.*.custom_field_id.exists' => 'Uno de los custom fields no existe.',
        ];
    }
}
