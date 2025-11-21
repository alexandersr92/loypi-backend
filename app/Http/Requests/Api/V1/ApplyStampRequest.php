<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class ApplyStampRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Se validará en el controller que sea staff autenticado
    }

    public function rules(): array
    {
        return [
            'customer_code' => [
                'required',
                'string',
                'size:6',
            ],
            'campaign_code' => [
                'required',
                'string',
                'size:4',
                'exists:campaigns,code',
            ],
            'type' => [
                'required',
                'string',
                'in:stamp,streak',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_code.required' => 'El código del customer es requerido.',
            'customer_code.size' => 'El código del customer debe tener 6 caracteres.',
            'campaign_code.required' => 'El código de la campaign es requerido.',
            'campaign_code.size' => 'El código de la campaign debe tener 4 caracteres.',
            'campaign_code.exists' => 'La campaign no existe.',
            'type.required' => 'El tipo de stamp es requerido.',
            'type.in' => 'El tipo debe ser "stamp" o "streak".',
        ];
    }
}
