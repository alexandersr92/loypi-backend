<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StaffLoginRequest extends FormRequest
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
            'business_slug' => ['required', 'string', 'exists:businesses,slug'],
            'code' => ['required', 'string'],
            'pin' => ['required', 'string', 'min:4', 'max:6'],
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
            'business_slug.required' => 'The business slug is required.',
            'business_slug.exists' => 'The business does not exist.',
            'code.required' => 'The staff code is required.',
            'pin.required' => 'The PIN is required.',
            'pin.min' => 'The PIN must be at least 4 characters.',
            'pin.max' => 'The PIN must not exceed 6 characters.',
        ];
    }
}
