<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'phone' => [
                'required',
                'string',
                'regex:/^\+?[1-9]\d{1,14}$/',
            ],
            'otp' => [
                'required',
                'string',
                'size:6',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.required' => 'El número de teléfono es requerido.',
            'phone.regex' => 'El formato del número de teléfono no es válido.',
            'otp.required' => 'El código OTP es requerido.',
            'otp.size' => 'El código OTP debe tener 6 dígitos.',
        ];
    }
}
