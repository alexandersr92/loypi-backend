<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreCampaignCustomFieldRequest extends FormRequest
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
            'custom_field_ids' => ['required', 'array', 'min:1'],
            'custom_field_ids.*' => [
                'required',
                'uuid',
                'exists:custom_fields,id',
                function ($attribute, $value, $fail) use ($businessId) {
                    $field = \App\Models\CustomField::find($value);
                    if ($field && $field->business_id !== $businessId) {
                        $fail('El custom field no pertenece a tu negocio.');
                    }
                },
            ],
            'sort_orders' => ['nullable', 'array'],
            'sort_orders.*' => ['nullable', 'integer', 'min:0'],
            'required_overrides' => ['nullable', 'array'],
            'required_overrides.*' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'custom_field_ids.required' => 'Debes proporcionar al menos un custom field.',
            'custom_field_ids.*.exists' => 'Uno de los custom fields no existe.',
        ];
    }
}
