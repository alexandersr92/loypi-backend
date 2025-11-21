<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
            ],
            'phone' => [
                'sometimes',
                'required',
                'string',
                'regex:/^\+?[1-9]\d{1,14}$/',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es requerido.',
            'name.max' => 'El nombre no puede exceder 255 caracteres.',
            'phone.required' => 'El número de teléfono es requerido.',
            'phone.regex' => 'El formato del número de teléfono no es válido.',
        ];
    }
}
