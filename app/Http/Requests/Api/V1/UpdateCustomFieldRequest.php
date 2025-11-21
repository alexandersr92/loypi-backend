<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomFieldRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'label' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'required' => ['nullable', 'boolean'],
            'extra' => ['nullable', 'array'],
            'active' => ['nullable', 'boolean'],
            // Opciones para campos tipo select (solo si ya es select)
            'options' => ['nullable', 'array'],
            'options.*.id' => ['nullable', 'uuid', 'exists:custom_field_options,id'],
            'options.*.value' => ['required_with:options', 'string', 'max:255'],
            'options.*.label' => ['required_with:options', 'string', 'max:255'],
            'options.*.sort_order' => ['nullable', 'integer', 'min:0'],
            // Validaciones
            'validations' => ['nullable', 'array'],
            'validations.*.id' => ['nullable', 'uuid', 'exists:custom_field_validations,id'],
            'validations.*.operator' => ['required_with:validations', 'string', 'in:=,!=,>,>=,<,<=,in,not_in,regex'],
            'validations.*.value_string' => ['nullable', 'string'],
            'validations.*.value_number' => ['nullable', 'numeric'],
            'validations.*.value_date' => ['nullable', 'date'],
            'validations.*.message' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'label.required' => 'El campo label es obligatorio.',
            'options.*.value.required_with' => 'Cada opción debe tener un value.',
            'options.*.label.required_with' => 'Cada opción debe tener un label.',
        ];
    }
}
