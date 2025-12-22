<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CampaignResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'business_id' => $this->business_id,
            'type' => $this->type,
            'name' => $this->name,
            'description' => $this->description,
            'limit' => $this->limit,
            'redeemed_count' => $this->redeemed_count,
            'reward_json' => $this->reward_json,
            'required_stamps' => $this->required_stamps,
            'active' => $this->active,
            'cover_image' => $this->cover_image,
            'cover_color' => $this->cover_color,
            'logo_url' => $this->logo_url,
            'streak_time_limit_hours' => $this->streak_time_limit_hours,
            'streak_reset_time' => $this->streak_reset_time,
            'per_customer_limit' => $this->per_customer_limit,
            'per_week_limit' => $this->per_week_limit,
            'per_month_limit' => $this->per_month_limit,
            'max_redemptions_per_day' => $this->max_redemptions_per_day,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            'business' => $this->whenLoaded('business', function () {
                return [
                    'id' => $this->business->id,
                    'slug' => $this->business->slug,
                    'name' => $this->business->name,
                ];
            }),
            'rewards' => $this->whenLoaded('rewards', function () {
                return $this->rewards->map(function ($reward) {
                    return [
                        'id' => $reward->id,
                        'name' => $reward->name,
                        'type' => $reward->type,
                        'description' => $reward->description,
                        'image_url' => $reward->image_url,
                        'reward_json' => $reward->reward_json,
                        // Datos del pivot
                        'pivot' => [
                            'threshold_int' => $reward->pivot->threshold_int,
                            'per_customer_limit' => $reward->pivot->per_customer_limit,
                            'global_limit' => $reward->pivot->global_limit,
                            'redeemed_count' => $reward->pivot->redeemed_count,
                            'active' => $reward->pivot->active,
                            'sort_order' => $reward->pivot->sort_order,
                        ],
                    ];
                });
            }),
            'customers' => $this->whenLoaded('customers', function () {
                return $this->customers->map(function ($customer) {
                    return [
                        'id' => $customer->id,
                        'name' => $customer->name,
                        'phone' => $customer->phone,
                        'stamps' => $customer->pivot->stamps ?? 0,
                        'redeemed_at' => $customer->pivot->redeemed_at?->toIso8601String(),
                        'registered_at' => $customer->pivot->created_at?->toIso8601String(),
                    ];
                });
            }),
            'custom_fields' => $this->whenLoaded('customFields', function () {
                return $this->customFields->map(function ($field) {
                    $fieldData = [
                        'id' => $field->id,
                        'key' => $field->key,
                        'label' => $field->label,
                        'description' => $field->description,
                        'type' => $field->type,
                        'required' => $field->pivot->required_override ?? $field->required,
                        'extra' => $field->extra,
                        'active' => $field->active,
                        // Datos del pivot
                        'pivot' => [
                            'sort_order' => $field->pivot->sort_order,
                            'required_override' => $field->pivot->required_override,
                        ],
                    ];
                    
                    // Opciones (si es tipo select y están cargadas)
                    if ($field->relationLoaded('options')) {
                        $fieldData['options'] = $field->options->map(function ($option) {
                            return [
                                'id' => $option->id,
                                'value' => $option->value,
                                'label' => $option->label,
                                'sort_order' => $option->sort_order,
                            ];
                        })->values()->all();
                    }
                    
                    // Validaciones (si están cargadas)
                    if ($field->relationLoaded('validations')) {
                        $fieldData['validations'] = $field->validations->map(function ($validation) {
                            return [
                                'id' => $validation->id,
                                'operator' => $validation->operator,
                                'value_string' => $validation->value_string,
                                'value_number' => $validation->value_number,
                                'value_date' => $validation->value_date?->toIso8601String(),
                                'message' => $validation->message,
                            ];
                        })->values()->all();
                    }
                    
                    return $fieldData;
                })->values()->all();
            }),
        ];
    }
}
