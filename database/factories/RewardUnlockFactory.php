<?php

namespace Database\Factories;

use App\Models\CampaignReward;
use App\Models\CustomerCampaign;
use App\Models\Reward;
use App\Models\RewardUnlock;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RewardUnlock>
 */
class RewardUnlockFactory extends Factory
{
    protected $model = RewardUnlock::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $customerCampaign = CustomerCampaign::factory()->create();
        $campaign = $customerCampaign->campaign;
        
        // Buscar un reward activo de la campaign
        $campaignReward = CampaignReward::where('campaign_id', $campaign->id)
            ->where('active', true)
            ->first();

        if (!$campaignReward) {
            // Si no hay, crear uno
            $reward = Reward::factory()->create([
                'business_id' => $campaign->business_id,
                'type' => $campaign->type,
            ]);
            
            $campaignReward = CampaignReward::create([
                'campaign_id' => $campaign->id,
                'reward_id' => $reward->id,
                'threshold_int' => 10,
                'active' => true,
            ]);
        }

        $unlockedAt = fake()->dateTimeBetween('-30 days', 'now');
        $expiresAfterDays = $campaignReward->expires_after_days;
        $expiresAt = $expiresAfterDays ? (clone $unlockedAt)->modify("+{$expiresAfterDays} days") : null;

        return [
            'customer_campaign_id' => $customerCampaign->id,
            'reward_id' => $campaignReward->reward_id,
            'campaign_reward_id' => $campaignReward->id,
            'unlocked_at' => $unlockedAt,
            'expires_at' => $expiresAt,
            'redeemed_at' => null,
            'redemption_id' => null,
            'status' => fake()->randomElement(['unlocked', 'redeemed', 'expired']),
        ];
    }

    /**
     * Indicate that the unlock is available (not expired, not redeemed)
     */
    public function available(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'unlocked',
            'expires_at' => now()->addDays(7),
            'redeemed_at' => null,
            'redemption_id' => null,
        ]);
    }

    /**
     * Indicate that the unlock is redeemed
     */
    public function redeemed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'redeemed',
            'redeemed_at' => fake()->dateTimeBetween($attributes['unlocked_at'], 'now'),
        ]);
    }

    /**
     * Indicate that the unlock is expired
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'expired',
            'expires_at' => fake()->dateTimeBetween('-30 days', '-1 day'),
        ]);
    }
}
