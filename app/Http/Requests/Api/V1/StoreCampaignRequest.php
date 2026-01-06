<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCampaignRequest extends FormRequest
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
            'type' => ['required', 'string', 'in:punch,streak'],
            'name' => ['required', 'string', 'max:255'],
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
            // Se puede enviar reward_ids (array de IDs) o rewards (array de objetos)
            'reward_ids' => ['nullable', 'array', 'min:1'],
            'reward_ids.*' => ['required', 'uuid', 'exists:rewards,id'],
            'reward_pivot_data' => ['nullable', 'array'],
            'reward_pivot_data.*.threshold_int' => ['required_with:reward_pivot_data', 'integer', 'min:1'],
            'reward_pivot_data.*.per_customer_limit' => ['nullable', 'integer', 'min:1'],
            'reward_pivot_data.*.global_limit' => ['nullable', 'integer', 'min:1'],
            'reward_pivot_data.*.active' => ['nullable', 'boolean'],
            'reward_pivot_data.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'rewards' => ['nullable', 'array', 'min:1'],
            'rewards.*.name' => ['required_with:rewards', 'string', 'max:255'],
            'rewards.*.type' => ['required_with:rewards', 'string', 'in:punch,streak'],
            'rewards.*.description' => ['nullable', 'string'],
            'rewards.*.image_url' => ['nullable', 'string', 'max:500', 'url'],
            'rewards.*.reward_json' => ['nullable', 'array'],
            'rewards.*.threshold_int' => ['required_with:rewards', 'integer', 'min:1'],
            'rewards.*.per_customer_limit' => ['nullable', 'integer', 'min:1'],
            'rewards.*.global_limit' => ['nullable', 'integer', 'min:1'],
            'rewards.*.active' => ['nullable', 'boolean'],
            'rewards.*.sort_order' => ['nullable', 'integer', 'min:0'],
            // Custom fields: se puede enviar custom_field_ids (array de IDs) o custom_fields (array de objetos)
            'custom_field_ids' => ['nullable', 'array'],
            'custom_field_ids.*' => ['required', 'uuid', 'exists:custom_fields,id'],
            'custom_fields' => ['nullable', 'array'],
            'custom_fields.*.key' => ['required_with:custom_fields', 'string', 'max:255', 'regex:/^[a-z0-9_]+$/'],
            'custom_fields.*.label' => ['required_with:custom_fields', 'string', 'max:255'],
            'custom_fields.*.description' => ['nullable', 'string'],
            'custom_fields.*.type' => ['required_with:custom_fields', 'string', 'in:text,number,date,boolean,select,phone'],
            'custom_fields.*.required' => ['nullable', 'boolean'],
            'custom_fields.*.extra' => ['nullable', 'array'],
            'custom_fields.*.options' => ['nullable', 'array'],
            'custom_fields.*.options.*.value' => ['required_with:custom_fields.*.options', 'string', 'max:255'],
            'custom_fields.*.options.*.label' => ['required_with:custom_fields.*.options', 'string', 'max:255'],
            'custom_fields.*.options.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'custom_fields.*.validations' => ['nullable', 'array'],
            'custom_fields.*.validations.*.operator' => ['required_with:custom_fields.*.validations', 'string', 'in:=,!=,>,>=,<,<=,in,not_in,regex'],
            'custom_fields.*.validations.*.value_string' => ['nullable', 'string'],
            'custom_fields.*.validations.*.value_number' => ['nullable', 'numeric'],
            'custom_fields.*.validations.*.value_date' => ['nullable', 'date'],
            'custom_fields.*.validations.*.message' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $type = $this->input('type');
            $rewardIds = $this->input('reward_ids', []);
            $rewards = $this->input('rewards', []);
            $user = $this->user();
            $businessId = $user->business?->id;

            // Validar que se proporcione al menos uno: reward_ids o rewards
            if (empty($rewardIds) && empty($rewards)) {
                $validator->errors()->add(
                    'reward_ids',
                    'You must provide either reward_ids or rewards.'
                );
                return;
            }

            // Si se proporcionan ambos, error
            if (!empty($rewardIds) && !empty($rewards)) {
                $validator->errors()->add(
                    'reward_ids',
                    'You cannot provide both reward_ids and rewards. Use one or the other.'
                );
                return;
            }

            // Si se usan reward_ids, validar que existan y pertenezcan al business
            if (!empty($rewardIds)) {
                $existingRewards = \App\Models\Reward::whereIn('id', $rewardIds)
                    ->where('business_id', $businessId)
                    ->get();

                if ($existingRewards->count() !== count($rewardIds)) {
                    $validator->errors()->add(
                        'reward_ids',
                        'One or more reward IDs do not exist or do not belong to your business.'
                    );
                } else {
                    // Validar que los tipos coincidan con el tipo de campaign
                    foreach ($existingRewards as $reward) {
                        if ($reward->type !== $type) {
                            $validator->errors()->add(
                                'reward_ids',
                                "Reward '{$reward->name}' type ({$reward->type}) does not match campaign type ({$type})."
                            );
                        }
                    }

                    // Si es tipo punch, solo puede haber 1 reward
                    if ($type === 'punch' && count($rewardIds) > 1) {
                        $validator->errors()->add(
                            'reward_ids',
                            'Campaigns of type "punch" can only have one reward.'
                        );
                    }
                }
            }

            // Si se usan rewards (objetos), validar como antes
            if (!empty($rewards)) {
                // Validar que los rewards coincidan con el tipo de campaign
                foreach ($rewards as $index => $reward) {
                    if (isset($reward['type']) && $reward['type'] !== $type) {
                        $validator->errors()->add(
                            "rewards.{$index}.type",
                            "The reward type must match the campaign type ({$type})."
                        );
                    }
                }

                // Si es tipo punch, solo puede haber 1 reward
                if ($type === 'punch' && count($rewards) > 1) {
                    $validator->errors()->add(
                        'rewards',
                        'Campaigns of type "punch" can only have one reward.'
                    );
                }

                // Si es tipo punch, validar que el reward sea tipo punch
                if ($type === 'punch') {
                    $punchRewards = array_filter($rewards, fn($r) => ($r['type'] ?? null) === 'punch');
                    if (count($punchRewards) !== 1) {
                        $validator->errors()->add(
                            'rewards',
                            'Campaigns of type "punch" must have exactly one reward of type "punch".'
                        );
                    }
                }

                // Si es tipo streak, todos los rewards deben ser tipo streak
                if ($type === 'streak') {
                    $streakRewards = array_filter($rewards, fn($r) => ($r['type'] ?? null) === 'streak');
                    if (count($streakRewards) !== count($rewards)) {
                        $validator->errors()->add(
                            'rewards',
                            'Campaigns of type "streak" can only have rewards of type "streak".'
                        );
                    }
                }
            }

            // Validar custom fields
            $customFields = $this->input('custom_fields', []);
            if (!empty($customFields)) {
                foreach ($customFields as $index => $field) {
                    $fieldType = $field['type'] ?? null;
                    $fieldOptions = $field['options'] ?? [];
                    
                    // Si el tipo es "select", options debe tener al menos 1 elemento
                    if ($fieldType === 'select') {
                        if (empty($fieldOptions) || count($fieldOptions) < 1) {
                            $validator->errors()->add(
                                "custom_fields.{$index}.options",
                                'The options field must have at least 1 item when the type is "select".'
                            );
                        }
                    }
                }
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type.required' => 'The campaign type is required.',
            'type.in' => 'The campaign type must be either "punch" or "streak".',
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
            'per_customer_limit.integer' => 'The per customer limit must be an integer.',
            'per_week_limit.integer' => 'The per week limit must be an integer.',
            'per_month_limit.integer' => 'The per month limit must be an integer.',
            'max_redemptions_per_day.integer' => 'The max redemptions per day must be an integer.',
            'reward_ids.array' => 'Reward IDs must be an array.',
            'reward_ids.min' => 'At least one reward ID is required.',
            'reward_ids.*.exists' => 'One or more reward IDs do not exist.',
            'rewards.array' => 'Rewards must be an array.',
            'rewards.min' => 'At least one reward is required.',
            'rewards.*.name.required' => 'Each reward must have a name.',
            'rewards.*.type.required' => 'Each reward must have a type.',
            'rewards.*.type.in' => 'Each reward type must be either "punch" or "streak".',
            'rewards.*.threshold_int.required' => 'Each reward must have a threshold.',
            'rewards.*.threshold_int.integer' => 'The threshold must be an integer.',
            'rewards.*.threshold_int.min' => 'The threshold must be at least 1.',
        ];
    }
}
