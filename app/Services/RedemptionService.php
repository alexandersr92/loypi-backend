<?php

namespace App\Services;

use App\Models\CustomerCampaign;
use App\Models\Redemption;
use App\Models\RedemptionPin;
use App\Models\Reward;
use App\Models\RewardUnlock;
use App\Models\Staff;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RedemptionService
{
    public function redeem(
        CustomerCampaign $customerCampaign,
        Reward $reward,
        Staff $staff,
        array $meta = []
    ): Redemption {
        return DB::transaction(function () use ($customerCampaign, $reward, $staff, $meta) {
            // Verificar que el reward esté desbloqueado
            $unlock = RewardUnlock::where('reward_id', $reward->id)
                ->where('customer_campaign_id', $customerCampaign->id)
                ->whereNull('redeemed_at')
                ->first();

            if (!$unlock) {
                throw new \Exception('El premio no está desbloqueado o ya fue canjeado.');
            }

            if ($unlock->isExpired()) {
                throw new \Exception('El premio ha expirado.');
            }

            // Verificar límites
            if ($reward->global_limit !== null && $reward->redeemed_count >= $reward->global_limit) {
                throw new \Exception('Se ha alcanzado el límite global de canjes para este premio.');
            }

            // Crear redención
            $redemption = Redemption::create([
                'customer_campaign_id' => $customerCampaign->id,
                'reward_id' => $reward->id,
                'staff_id' => $staff->id,
                'confirmed_at' => now(),
                'meta' => $meta,
            ]);

            // Actualizar unlock
            $unlock->redeemed_at = now();
            $unlock->redemption_id = $redemption->id;
            $unlock->save();

            // Incrementar contador
            $reward->increment('redeemed_count');
            $customerCampaign->campaign->increment('redeemed_count');

            // Generar PIN si es necesario
            if (isset($meta['require_pin']) && $meta['require_pin']) {
                $this->generateRedemptionPin($redemption);
            }

            return $redemption;
        });
    }

    public function generateRedemptionPin(Redemption $redemption): RedemptionPin
    {
        $pin = str_pad((string) random_int(1000, 9999), 4, '0', STR_PAD_LEFT);
        
        return RedemptionPin::create([
            'redemption_id' => $redemption->id,
            'pin_hash' => Hash::make($pin),
            'expires_at' => now()->addHours(24),
            'attempts' => 0,
        ]);
    }

    public function verifyRedemptionPin(RedemptionPin $redemptionPin, string $pin): bool
    {
        if (!$redemptionPin->isValid()) {
            return false;
        }

        if ($redemptionPin->verifyPin($pin)) {
            return true;
        }

        $redemptionPin->incrementAttempts();
        return false;
    }
}

