<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Staff;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStaffRequest extends FormRequest
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
        $staffId = $this->route('staff') ?? $this->route('id');
        $staff = Staff::find($staffId);
        $businessId = $staff ? $staff->business_id : $this->input('business_id');

        return [
            'business_id' => ['sometimes', 'required', 'uuid', 'exists:businesses,id'],
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('staff')->where(function ($query) use ($businessId) {
                    return $query->where('business_id', $businessId);
                })->ignore($staffId),
            ],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'pin' => ['sometimes', 'required', 'string', 'min:4', 'max:6'],
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
