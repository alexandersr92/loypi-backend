<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Business;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBusinessRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Por ahora permitimos, luego se puede agregar autorización
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $businessId = $this->route('business') ?? $this->route('id');

        return [
            'user_id' => ['sometimes', 'required', 'uuid', 'exists:users,id', Rule::unique(Business::class)->ignore($businessId)],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique(Business::class)->ignore($businessId), 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'description' => ['nullable', 'string', 'max:1000'],
            'logo' => ['nullable', 'string', 'url', 'max:500'],
            'cover' => ['nullable', 'string', 'url', 'max:500'],
            'branding_json' => ['nullable', 'array'],
            'address' => ['nullable', 'string', 'max:500'],
            'phone' => ['sometimes', 'required', 'string', 'regex:/^\+?[1-9]\d{1,14}$/'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'website' => ['nullable', 'string', 'url', 'max:500'],
            'city' => ['sometimes', 'required', 'string', 'max:100'],
            'state' => ['sometimes', 'required', 'string', 'max:100'],
            'country' => ['sometimes', 'required', 'string', 'max:100'],
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
            'user_id.required' => 'The user ID field is required.',
            'user_id.exists' => 'The specified user does not exist.',
            'user_id.unique' => 'This user already has an associated business.',
            'name.required' => 'The business name field is required.',
            'slug.unique' => 'This slug is already in use.',
            'slug.regex' => 'The slug can only contain lowercase letters, numbers, and hyphens.',
            'phone.required' => 'The phone field is required.',
            'phone.regex' => 'The phone number format is invalid. It must include the country code (e.g., +521234567890).',
            'city.required' => 'The city field is required.',
            'state.required' => 'The state field is required.',
            'country.required' => 'The country field is required.',
            'email.email' => 'The email must be a valid email address.',
            'website.url' => 'The website must be a valid URL.',
        ];
    }
}
