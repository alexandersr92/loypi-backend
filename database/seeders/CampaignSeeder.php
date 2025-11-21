<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\Campaign;
use App\Models\CampaignReward;
use App\Models\Reward;
use Illuminate\Database\Seeder;

class CampaignSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $businesses = Business::all();

        foreach ($businesses as $business) {
            // Crear 1 campaign tipo "punch" con 1 reward
            $punchCampaign = Campaign::factory()
                ->for($business, 'business')
                ->create([
                    'type' => 'punch',
                    'name' => fake()->words(3, true) . ' - Punch Card',
                ]);

            // Crear 1 reward para la campaign punch
            $punchReward = Reward::factory()
                ->create([
                    'business_id' => $business->id,
                    'type' => 'punch',
                    'name' => fake()->words(2, true),
                ]);

            // Asociar reward a campaign con pivot data
            CampaignReward::create([
                'campaign_id' => $punchCampaign->id,
                'reward_id' => $punchReward->id,
                'threshold_int' => fake()->numberBetween(5, 10),
                'per_customer_limit' => null,
                'global_limit' => null,
                'redeemed_count' => 0,
                'active' => true,
                'sort_order' => 1,
                'expires_after_days' => fake()->optional(0.5)->numberBetween(7, 30),
            ]);

            // Crear 1 campaign tipo "streak" con 4 rewards
            $streakCampaign = Campaign::factory()
                ->for($business, 'business')
                ->create([
                    'type' => 'streak',
                    'name' => fake()->words(3, true) . ' - Streak Challenge',
                    'streak_time_limit_hours' => fake()->randomElement([24, 48]),
                    'streak_reset_time' => fake()->optional()->randomElement(['00:00:00', '06:00:00']),
                ]);

            // Crear 4 rewards para la campaign streak (10, 20, 50, 100 rachas)
            $streakThresholds = [10, 20, 50, 100];
            foreach ($streakThresholds as $index => $threshold) {
                $streakReward = Reward::factory()
                    ->create([
                        'business_id' => $business->id,
                        'type' => 'streak',
                        'name' => fake()->words(2, true),
                    ]);

                // Asociar reward a campaign con pivot data
                CampaignReward::create([
                    'campaign_id' => $streakCampaign->id,
                    'reward_id' => $streakReward->id,
                    'threshold_int' => $threshold,
                    'per_customer_limit' => null,
                    'global_limit' => null,
                    'redeemed_count' => 0,
                    'active' => true,
                    'sort_order' => $index + 1,
                    'expires_after_days' => fake()->optional(0.5)->numberBetween(7, 30),
                ]);
            }
        }
    }
}
