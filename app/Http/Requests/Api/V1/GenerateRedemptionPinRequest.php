<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class GenerateRedemptionPinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Se validará en el controller que sea customer autenticado
    }

    public function rules(): array
    {
        return [
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
            'unlock_id.required' => 'El ID del unlock es requerido.',
            'unlock_id.exists' => 'El unlock no existe.',
        ];
    }
}
