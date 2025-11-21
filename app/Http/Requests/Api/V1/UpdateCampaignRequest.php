<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCampaignRequest extends FormRequest
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
            'type' => ['sometimes', 'required', 'string', 'in:punch,streak'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'limit' => ['nullable', 'integer', 'min:1'],
            'reward_json' => ['nullable', 'array'],
            'required_stamps' => ['nullable', 'integer', 'min:1'],
            'active' => ['nullable', 'boolean'],
            'cover_image' => ['nullable', 'string', 'max:500', 'url'],
            'cover_color' => ['nullable', 'string', 'max:7', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'logo_url' => ['nullable', 'string', 'max:500', 'url'],
            'streak_time_limit_hours' => ['nullable', 'integer', 'min:1'],
            'streak_reset_time' => ['nullable', 'date_format:H:i:s'],
            'per_customer_limit' => ['nullable', 'integer', 'min:1'],
            'per_week_limit' => ['nullable', 'integer', 'min:1'],
            'per_month_limit' => ['nullable', 'integer', 'min:1'],
            'max_redemptions_per_day' => ['nullable', 'integer', 'min:1'],
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
            'name.required' => 'The campaign name is required.',
            'name.max' => 'The campaign name must not exceed 255 characters.',
            'limit.integer' => 'The limit must be an integer.',
            'limit.min' => 'The limit must be at least 1.',
            'reward_json.array' => 'The reward JSON must be a valid array.',
            'required_stamps.integer' => 'The required stamps must be an integer.',
            'required_stamps.min' => 'The required stamps must be at least 1.',
            'cover_image.url' => 'The cover image must be a valid URL.',
            'cover_color.regex' => 'The cover color must be a valid hexadecimal color (e.g., #FF5733).',
            'logo_url.url' => 'The logo URL must be a valid URL.',
            'streak_time_limit_hours.integer' => 'The streak time limit must be an integer.',
            'streak_time_limit_hours.min' => 'The streak time limit must be at least 1 hour.',
            'streak_reset_time.date_format' => 'The streak reset time must be in format HH:mm:ss (e.g., 00:00:00).',
        ];
    }
}
