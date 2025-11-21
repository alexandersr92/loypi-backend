<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class CheckPhoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => [
                'required',
                'string',
                'regex:/^\+?[1-9]\d{1,14}$/',
            ],
            'business_slug' => [
                'required',
                'string',
                'exists:businesses,slug',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => 'El número de teléfono es requerido.',
            'phone.regex' => 'El formato del número de teléfono no es válido. Debe incluir el código de país (ej: +521234567890).',
            'business_slug.required' => 'El slug del negocio es requerido.',
            'business_slug.exists' => 'El negocio no existe.',
        ];
    }
}
