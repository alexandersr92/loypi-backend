<?php

namespace App\Http\Requests\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
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
        $userId = $this->route('user') ?? $this->route('id');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'string', 'email', 'max:255', Rule::unique(User::class)->ignore($userId)],
            'password' => ['sometimes', 'nullable', 'string', Password::default()],
            'phone' => ['nullable', 'string', 'regex:/^\+?[1-9]\d{1,14}$/'],
            'role' => ['sometimes', 'required', 'string', Rule::in(['admin', 'owner'])],
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
            'password.confirmed' => 'The password confirmation does not match.',
            'phone.regex' => 'The phone number format is invalid. It must include the country code (e.g., +521234567890).',
            'role.required' => 'The role field is required.',
            'role.in' => 'The role must be admin or owner.',
            'status.in' => 'The status must be active, inactive, or suspended.',
            'locale.size' => 'The locale must be 2 characters (e.g., es, en).',
        ];
    }
}
