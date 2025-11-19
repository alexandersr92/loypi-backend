<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStaffRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // La autorización se maneja en el controller
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
   
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('staff')->where('business_id', $this->input('business_id')),
            ],
            'name' => ['required', 'string', 'max:255'],
            'pin' => ['required', 'string', 'min:4', 'max:6'],
            'active' => ['nullable', 'boolean'],
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
            'business_id.required' => 'The business ID is required.',
            'business_id.exists' => 'The business does not exist.',
            'code.required' => 'The staff code is required.',
            'code.unique' => 'This code is already in use for this business.',
            'name.required' => 'The name field is required.',
            'pin.required' => 'The PIN is required.',
            'pin.min' => 'The PIN must be at least 4 characters.',
            'pin.max' => 'The PIN must not exceed 6 characters.',
        ];
    }
}
