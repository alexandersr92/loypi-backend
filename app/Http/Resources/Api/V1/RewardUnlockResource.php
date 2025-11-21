<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RewardUnlockResource extends JsonResource
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
            'customer_campaign_id' => $this->customer_campaign_id,
            'reward_id' => $this->reward_id,
            'campaign_reward_id' => $this->campaign_reward_id,
            'status' => $this->status,
            'unlocked_at' => $this->unlocked_at->toIso8601String(),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'redeemed_at' => $this->redeemed_at?->toIso8601String(),
            'redemption_id' => $this->redemption_id,
            'is_expired' => $this->isExpired(),
            'is_available' => $this->isAvailable(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            
            // Relaciones
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
            
            'campaign_reward' => $this->whenLoaded('campaignReward', function () {
                return [
                    'id' => $this->campaignReward->id,
                    'threshold_int' => $this->campaignReward->threshold_int,
                    'per_customer_limit' => $this->campaignReward->per_customer_limit,
                    'global_limit' => $this->campaignReward->global_limit,
                    'redeemed_count' => $this->campaignReward->redeemed_count,
                    'active' => $this->campaignReward->active,
                ];
            }),
            
            'redemption' => $this->whenLoaded('redemption', function () {
                return [
                    'id' => $this->redemption->id,
                    'confirmed_at' => $this->redemption->confirmed_at?->toIso8601String(),
                    'staff' => $this->whenLoaded('redemption.staff', function () {
                        return [
                            'id' => $this->redemption->staff->id,
                            'code' => $this->redemption->staff->code,
                            'name' => $this->redemption->staff->name,
                        ];
                    }),
                ];
            }),
        ];
    }
}
