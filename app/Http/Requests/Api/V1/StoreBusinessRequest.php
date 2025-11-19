<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Business;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBusinessRequest extends FormRequest
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
        return [
            'user_id' => ['required', 'uuid', 'exists:users,id', 'unique:businesses,user_id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:businesses,slug', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'description' => ['nullable', 'string', 'max:1000'],
            'logo' => ['nullable', 'string', 'url', 'max:500'],
            'branding_json' => ['nullable', 'array'],
            'branding_json.primary_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'branding_json.secondary_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'address' => ['nullable', 'string', 'max:500'],
            'phone' => ['required', 'string', 'regex:/^\+?[1-9]\d{1,14}$/'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'website' => ['nullable', 'string', 'url', 'max:500'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'max:100'],
            'country' => ['required', 'string', 'max:100'],
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
            'branding_json.primary_color.regex' => 'The primary color must be a valid hexadecimal code (e.g., #FF5733).',
            'branding_json.secondary_color.regex' => 'The secondary color must be a valid hexadecimal code (e.g., #FF5733).',
        ];
    }
}
