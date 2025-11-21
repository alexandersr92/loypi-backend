<?php

namespace Database\Seeders;

use App\Models\CampaignReward;
use App\Models\CustomerCampaign;
use App\Models\RewardUnlock;
use Illuminate\Database\Seeder;

class RewardUnlockSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener todos los customer_campaigns que tengan stamps
        $customerCampaigns = CustomerCampaign::where('stamps', '>', 0)
            ->with(['campaign.rewards'])
            ->get();

        foreach ($customerCampaigns as $customerCampaign) {
            $campaign = $customerCampaign->campaign;
            $stamps = $customerCampaign->stamps;

            // Obtener todos los rewards activos de la campaign
            $campaignRewards = CampaignReward::where('campaign_id', $campaign->id)
                ->where('active', true)
                ->where('threshold_int', '<=', $stamps)
                ->with('reward')
                ->get();

            foreach ($campaignRewards as $campaignReward) {
                // Verificar que el tipo del reward coincida con el tipo de la campaign
                if ($campaignReward->reward->type !== $campaign->type) {
                    continue;
                }

                // Verificar si ya existe un unlock para este reward
                $existingUnlock = RewardUnlock::where('customer_campaign_id', $customerCampaign->id)
                    ->where('reward_id', $campaignReward->reward_id)
                    ->first();

                if ($existingUnlock) {
                    continue; // Ya existe
                }

                // Calcular fecha de expiración si aplica
                $unlockedAt = \Carbon\Carbon::parse(fake()->dateTimeBetween('-30 days', 'now'));
                $expiresAt = null;
                if ($campaignReward->expires_after_days !== null) {
                    $expiresAt = $unlockedAt->copy()->addDays($campaignReward->expires_after_days);
                }

                // Determinar estado (algunos ya canjeados, algunos expirados)
                $status = 'unlocked';
                $redeemedAt = null;
                if (fake()->boolean(20)) {
                    $status = 'redeemed';
                    $redeemedAt = \Carbon\Carbon::parse(fake()->dateTimeBetween($unlockedAt, 'now'));
                } elseif ($expiresAt && $expiresAt->isPast() && fake()->boolean(30)) {
                    $status = 'expired';
                }

                RewardUnlock::create([
                    'customer_campaign_id' => $customerCampaign->id,
                    'reward_id' => $campaignReward->reward_id,
                    'campaign_reward_id' => $campaignReward->id,
                    'unlocked_at' => $unlockedAt,
                    'expires_at' => $expiresAt,
                    'redeemed_at' => $redeemedAt,
                    'redemption_id' => null,
                    'status' => $status,
                ]);
            }
        }

        $this->command->info('Reward unlocks creados exitosamente.');
    }
}
