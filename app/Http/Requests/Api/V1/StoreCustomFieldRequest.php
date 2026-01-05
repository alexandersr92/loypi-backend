<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCustomFieldRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $user = $this->user();
        $businessId = $user->business->id ?? null;

        return [
            'key' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9_]+$/',
                Rule::unique('custom_fields')->where(function ($query) use ($businessId) {
                    return $query->where('business_id', $businessId);
                }),
            ],
            'label' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'string', 'in:text,number,date,boolean,select'],
            'required' => ['nullable', 'boolean'],
            'extra' => ['nullable', 'array'],
            'active' => ['nullable', 'boolean'],
            // Opciones para campos tipo select
            'options' => ['required_if:type,select', 'array', Rule::when($this->input('type') === 'select', 'min:1')],
            'options.*.value' => ['required_with:options', 'string', 'max:255'],
            'options.*.label' => ['required_with:options', 'string', 'max:255'],
            'options.*.sort_order' => ['nullable', 'integer', 'min:0'],
            // Validaciones opcionales
            'validations' => ['nullable', 'array'],
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
            'key.required' => 'El campo key es obligatorio.',
            'key.unique' => 'Este key ya existe en tu negocio.',
            'key.regex' => 'El key solo puede contener letras minúsculas, números y guiones bajos.',
            'label.required' => 'El campo label es obligatorio.',
            'type.required' => 'El campo type es obligatorio.',
            'type.in' => 'El tipo debe ser: text, number, date, boolean o select.',
            'options.required_if' => 'Los campos tipo select requieren al menos una opción.',
            'options.*.value.required_with' => 'Cada opción debe tener un value.',
            'options.*.label.required_with' => 'Cada opción debe tener un label.',
        ];
    }
}
