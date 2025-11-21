<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerRequest extends FormRequest
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
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'business_slug' => [
                'required',
                'string',
                'exists:businesses,slug',
            ],
            'otp_code' => [
                'required',
                'string',
                'size:6',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => 'El número de teléfono es requerido.',
            'phone.regex' => 'El formato del número de teléfono no es válido.',
            'name.required' => 'El nombre es requerido.',
            'name.max' => 'El nombre no puede exceder 255 caracteres.',
            'business_slug.required' => 'El slug del negocio es requerido.',
            'business_slug.exists' => 'El negocio no existe.',
            'otp_code.required' => 'El código OTP es requerido.',
            'otp_code.size' => 'El código OTP debe tener 6 dígitos.',
        ];
    }
}
