<?php

namespace App\Http\Requests\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', Password::default(), 'confirmed'],
            'phone' => ['nullable', 'string', 'regex:/^\+?[1-9]\d{1,14}$/'],
            'avatar' => ['nullable', 'string', 'url', 'max:500'],
            'status' => ['nullable', 'string', Rule::in(['active', 'inactive', 'suspended'])],
            'timezone' => ['nullable', 'string', 'max:50'],
            'locale' => ['nullable', 'string', 'size:2'],
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
            'name.required' => 'The name field is required.',
            'email.required' => 'The email field is required.',
            'email.email' => 'The email must be a valid email address.',
            'email.unique' => 'This email is already registered.',
            'password.required' => 'The password field is required.',
            'password.confirmed' => 'The password confirmation does not match.',
            'phone.regex' => 'The phone number format is invalid. It must include the country code (e.g., +521234567890).',
            'status.in' => 'The status must be active, inactive, or suspended.',
            'locale.size' => 'The locale must be 2 characters (e.g., es, en).',
        ];
    }
}
