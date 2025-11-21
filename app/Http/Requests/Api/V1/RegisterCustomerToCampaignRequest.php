<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class RegisterCustomerToCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Público, pero puede requerir autenticación si el customer ya existe
    }

    public function rules(): array
    {
        return [
            'campaign_code' => [
                'required',
                'string',
                'size:4',
                'exists:campaigns,code',
            ],
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
            // Si el customer es nuevo, requiere estos campos (se validará en el controller)
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
            ],
            'otp_code' => [
                'sometimes',
                'required',
                'string',
                'size:6',
            ],
            // Custom field values
            'field_values' => [
                'sometimes',
                'array',
            ],
            'field_values.*.custom_field_id' => [
                'required_with:field_values',
                'uuid',
                'exists:custom_fields,id',
            ],
            'field_values.*.string_value' => [
                'nullable',
                'string',
            ],
            'field_values.*.number_value' => [
                'nullable',
                'numeric',
            ],
            'field_values.*.date_value' => [
                'nullable',
                'date',
            ],
            'field_values.*.boolean_value' => [
                'nullable',
                'boolean',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'campaign_code.required' => 'El código de la campaign es requerido.',
            'campaign_code.size' => 'El código de la campaign debe tener 4 caracteres.',
            'campaign_code.exists' => 'La campaign no existe.',
            'phone.required' => 'El número de teléfono es requerido.',
            'phone.regex' => 'El formato del número de teléfono no es válido.',
            'business_slug.required' => 'El slug del negocio es requerido.',
            'business_slug.exists' => 'El negocio no existe.',
            'name.required' => 'El nombre es requerido para nuevos customers.',
            'otp_code.required' => 'El código OTP es requerido para nuevos customers.',
            'otp_code.size' => 'El código OTP debe tener 6 dígitos.',
            'field_values.*.custom_field_id.required_with' => 'Cada valor debe tener un custom_field_id.',
            'field_values.*.custom_field_id.exists' => 'Uno de los custom fields no existe.',
        ];
    }
}
