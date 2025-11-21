<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RewardResource extends JsonResource
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
            'business_id' => $this->business_id,
            'name' => $this->name,
            'type' => $this->type,
            'description' => $this->description,
            'image_url' => $this->image_url,
            'reward_json' => $this->reward_json,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            'business' => $this->whenLoaded('business', function () {
                return [
                    'id' => $this->business->id,
                    'slug' => $this->business->slug,
                    'name' => $this->business->name,
                ];
            }),
            'campaigns' => $this->whenLoaded('campaigns', function () {
                return $this->campaigns->map(function ($campaign) {
                    return [
                        'id' => $campaign->id,
                        'name' => $campaign->name,
                        'type' => $campaign->type,
                        'pivot' => [
                            'threshold_int' => $campaign->pivot->threshold_int,
                            'per_customer_limit' => $campaign->pivot->per_customer_limit,
                            'global_limit' => $campaign->pivot->global_limit,
                            'redeemed_count' => $campaign->pivot->redeemed_count,
                            'active' => $campaign->pivot->active,
                            'sort_order' => $campaign->pivot->sort_order,
                        ],
                    ];
                });
            }),
        ];
    }
}
