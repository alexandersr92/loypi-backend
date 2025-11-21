<?php

namespace Database\Factories;

use App\Models\CustomerCampaign;
use App\Models\Redemption;
use App\Models\Reward;
use App\Models\RewardUnlock;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Redemption>
 */
class RedemptionFactory extends Factory
{
    protected $model = Redemption::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $rewardUnlock = RewardUnlock::factory()->create();
        $customerCampaign = $rewardUnlock->customerCampaign;
        $staff = Staff::where('business_id', $customerCampaign->campaign->business_id)->first();

        if (!$staff) {
            $staff = Staff::factory()->create([
                'business_id' => $customerCampaign->campaign->business_id,
            ]);
        }

        $pin = str_pad((string) rand(0, 9999), 4, '0', STR_PAD_LEFT);

        return [
            'reward_unlock_id' => $rewardUnlock->id,
            'customer_campaign_id' => $customerCampaign->id,
            'reward_id' => $rewardUnlock->reward_id,
            'staff_id' => $staff->id,
            'pin_code' => $pin,
            'confirmed_at' => fake()->boolean(70) ? fake()->dateTimeBetween('-7 days', 'now') : null,
            'meta' => null,
        ];
    }

    /**
     * Indicate that the redemption is confirmed
     */
    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'confirmed_at' => fake()->dateTimeBetween($attributes['created_at'] ?? '-7 days', 'now'),
        ]);
    }

    /**
     * Indicate that the redemption is pending (not confirmed)
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'confirmed_at' => null,
            'staff_id' => null,
        ]);
    }
}
