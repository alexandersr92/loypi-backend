<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\Customer;
use App\Models\CustomerCampaign;
use App\Models\CustomerStreak;
use App\Models\Reward;
use App\Models\RewardUnlock;
use App\Models\Stamp;
use App\Models\Staff;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StampService
{
    public function addStamp(
        Customer $customer,
        Campaign $campaign,
        Staff $staff,
        array $meta = []
    ): Stamp {
        return DB::transaction(function () use ($customer, $campaign, $staff, $meta) {
            // Obtener o crear customer_campaign
            $customerCampaign = CustomerCampaign::firstOrCreate(
                [
                    'customer_id' => $customer->id,
                    'campaign_id' => $campaign->id,
                ],
                ['stamps' => 0]
            );

            // Crear el stamp
            $stamp = Stamp::create([
                'customer_campaign_id' => $customerCampaign->id,
                'staff_id' => $staff->id,
                'meta' => $meta,
            ]);

            // Incrementar contador de stamps
            $customerCampaign->increment('stamps');

            // Actualizar o crear streak si es necesario
            $this->updateStreak($customer, $campaign);

            // Verificar si se desbloquean premios
            $this->checkRewardUnlocks($customerCampaign, $campaign);

            return $stamp;
        });
    }

    private function updateStreak(Customer $customer, Campaign $campaign): void
    {
        $streak = CustomerStreak::firstOrCreate(
            [
                'customer_id' => $customer->id,
                'campaign_id' => $campaign->id,
            ],
            [
                'current_streak' => 0,
                'longest_streak' => 0,
            ]
        );

        $today = Carbon::today();
        $yesterday = Carbon::yesterday();

        if ($streak->last_event_date === null) {
            // Primera vez
            $streak->current_streak = 1;
            $streak->longest_streak = 1;
        } elseif ($streak->last_event_date->isToday()) {
            // Ya se registró hoy, no hacer nada
            return;
        } elseif ($streak->last_event_date->isYesterday()) {
            // Continuar racha
            $streak->current_streak++;
        } else {
            // Romper racha
            $streak->current_streak = 1;
        }

        if ($streak->current_streak > $streak->longest_streak) {
            $streak->longest_streak = $streak->current_streak;
        }

        $streak->last_event_date = $today;
        $streak->save();
    }

    private function checkRewardUnlocks(CustomerCampaign $customerCampaign, Campaign $campaign): void
    {
        $rewards = Reward::where('campaign_id', $campaign->id)
            ->where('active', true)
            ->get();

        foreach ($rewards as $reward) {
            // Verificar si ya está desbloqueado
            $existingUnlock = RewardUnlock::where('reward_id', $reward->id)
                ->where('customer_campaign_id', $customerCampaign->id)
                ->first();

            if ($existingUnlock) {
                continue;
            }

            // Verificar límites por cliente
            if ($reward->per_customer_limit !== null) {
                $unlockCount = RewardUnlock::where('reward_id', $reward->id)
                    ->where('customer_campaign_id', $customerCampaign->id)
                    ->count();
                
                if ($unlockCount >= $reward->per_customer_limit) {
                    continue;
                }
            }

            // Verificar límite global
            if ($reward->global_limit !== null && $reward->redeemed_count >= $reward->global_limit) {
                continue;
            }

            // Verificar condición según tipo
            $shouldUnlock = false;

            switch ($reward->type) {
                case 'punch':
                    $shouldUnlock = $customerCampaign->stamps >= $reward->threshold_int;
                    break;
                case 'streak':
                    $streak = CustomerStreak::where('customer_id', $customerCampaign->customer_id)
                        ->where('campaign_id', $campaign->id)
                        ->first();
                    $shouldUnlock = $streak && $streak->current_streak >= $reward->threshold_int;
                    break;
                case 'points':
                    // Implementar lógica de puntos si es necesario
                    break;
            }

            if ($shouldUnlock) {
                RewardUnlock::create([
                    'reward_id' => $reward->id,
                    'customer_campaign_id' => $customerCampaign->id,
                    'unlocked_at' => now(),
                    'expires_at' => null, // O calcular según configuración
                ]);
            }
        }
    }
}

