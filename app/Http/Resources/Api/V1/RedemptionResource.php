<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RedemptionResource extends JsonResource
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
            'reward_unlock_id' => $this->reward_unlock_id,
            'customer_campaign_id' => $this->customer_campaign_id,
            'reward_id' => $this->reward_id,
            'staff_id' => $this->staff_id,
            'pin_code' => $this->pin_code,
            'confirmed_at' => $this->confirmed_at?->toIso8601String(),
            'meta' => $this->meta,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            
            // Relaciones
            'reward_unlock' => $this->whenLoaded('rewardUnlock', function () {
                return [
                    'id' => $this->rewardUnlock->id,
                    'status' => $this->rewardUnlock->status,
                    'unlocked_at' => $this->rewardUnlock->unlocked_at->toIso8601String(),
                    'redeemed_at' => $this->rewardUnlock->redeemed_at?->toIso8601String(),
                    'expires_at' => $this->rewardUnlock->expires_at?->toIso8601String(),
                ];
            }),
            
            'customer_campaign' => $this->whenLoaded('customerCampaign', function () {
                return [
                    'id' => $this->customerCampaign->id,
                    'stamps' => $this->customerCampaign->stamps,
                    'customer' => $this->whenLoaded('customerCampaign.customer', function () {
                        return [
                            'id' => $this->customerCampaign->customer->id,
                            'short_code' => $this->customerCampaign->customer->short_code,
                            'name' => $this->customerCampaign->customer->name,
                            'phone' => $this->customerCampaign->customer->phone,
                        ];
                    }),
                    'campaign' => $this->whenLoaded('customerCampaign.campaign', function () {
                        return [
                            'id' => $this->customerCampaign->campaign->id,
                            'code' => $this->customerCampaign->campaign->code,
                            'name' => $this->customerCampaign->campaign->name,
                            'type' => $this->customerCampaign->campaign->type,
                        ];
                    }),
                ];
            }),
            
            'reward' => $this->whenLoaded('reward', function () {
                return [
                    'id' => $this->reward->id,
                    'name' => $this->reward->name,
                    'type' => $this->reward->type,
                    'description' => $this->reward->description,
                    'image_url' => $this->reward->image_url,
                ];
            }),
            
            'staff' => $this->whenLoaded('staff', function () {
                return [
                    'id' => $this->staff->id,
                    'code' => $this->staff->code,
                    'name' => $this->staff->name,
                ];
            }),
            
            'redemption_pin' => $this->whenLoaded('redemptionPin', function () {
                return [
                    'id' => $this->redemptionPin->id,
                    'pin' => $this->redemptionPin->pin,
                    'expires_at' => $this->redemptionPin->expires_at->toIso8601String(),
                    'attempts' => $this->redemptionPin->attempts,
                    'verified_at' => $this->redemptionPin->verified_at?->toIso8601String(),
                    'is_expired' => $this->redemptionPin->isExpired(),
                    'is_valid' => $this->redemptionPin->isValid(),
                ];
            }),
        ];
    }
}
