<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRewardRequest extends FormRequest
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
        $user = $this->user();
        $businessId = $user->business?->id;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'type' => ['sometimes', 'required', 'string', 'in:punch,streak'],
            'description' => ['nullable', 'string'],
            'image_url' => ['nullable', 'string', 'max:500', 'url'],
            'reward_json' => ['nullable', 'array'],
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
            'name.required' => 'The reward name is required.',
            'type.required' => 'The reward type is required.',
            'type.in' => 'The reward type must be either "punch" or "streak".',
            'image_url.url' => 'The image URL must be a valid URL.',
        ];
    }
}
