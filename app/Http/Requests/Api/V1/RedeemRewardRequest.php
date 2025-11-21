<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class RedeemRewardRequest extends FormRequest
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
            'pin' => [
                'required',
                'string',
                'size:4',
                'regex:/^[0-9]{4}$/',
            ],
            'unlock_id' => [
                'required',
                'uuid',
                'exists:reward_unlocks,id',
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
            'pin.required' => 'El PIN es requerido.',
            'pin.size' => 'El PIN debe tener 4 dígitos.',
            'pin.regex' => 'El PIN debe contener solo números.',
            'unlock_id.required' => 'El ID del unlock es requerido.',
            'unlock_id.exists' => 'El unlock no existe.',
        ];
    }
}
