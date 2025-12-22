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
            'business_slug' => [
                'required',
                'string',
                'exists:businesses,slug',
            ],
            // Custom field values - name y phone vienen aquí
            'field_values' => [
                'required',
                'array',
            ],
            'field_values.*.custom_field_id' => [
                'required',
                'string',
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

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $fieldValues = $this->input('field_values', []);
            $allowedDefaults = ['default-name-field', 'default-phone-field'];
            
            foreach ($fieldValues as $index => $fieldValue) {
                $customFieldId = $fieldValue['custom_field_id'] ?? null;
                
                if (!$customFieldId) {
                    continue;
                }
                
                // Si es un campo default, está permitido
                if (in_array($customFieldId, $allowedDefaults)) {
                    continue;
                }
                
                // Si no es default, debe ser un UUID válido
                if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $customFieldId)) {
                    $validator->errors()->add(
                        "field_values.{$index}.custom_field_id",
                        'El custom_field_id debe ser un UUID válido o un campo default (default-name-field, default-phone-field).'
                    );
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'campaign_code.required' => 'El código de la campaign es requerido.',
            'campaign_code.size' => 'El código de la campaign debe tener 4 caracteres.',
            'campaign_code.exists' => 'La campaign no existe.',
            'business_slug.required' => 'El slug del negocio es requerido.',
            'business_slug.exists' => 'El negocio no existe.',
            'field_values.required' => 'Los valores de los campos son requeridos.',
            'field_values.array' => 'Los valores de los campos deben ser un array.',
            'field_values.*.custom_field_id.required' => 'Cada valor debe tener un custom_field_id.',
        ];
    }
}
