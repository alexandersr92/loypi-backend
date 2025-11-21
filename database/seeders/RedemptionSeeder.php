<?php

namespace Database\Seeders;

use App\Models\Redemption;
use App\Models\RedemptionPin;
use App\Models\RewardUnlock;
use App\Models\Staff;
use Illuminate\Database\Seeder;

class RedemptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener unlocks que estén canjeados o desbloqueados
        $unlocks = RewardUnlock::with(['customerCampaign.campaign.business'])
            ->whereIn('status', ['redeemed', 'unlocked'])
            ->get();

        if ($unlocks->isEmpty()) {
            $this->command->warn('No hay reward_unlocks. Ejecuta primero RewardUnlockSeeder.');
            return;
        }

        foreach ($unlocks as $unlock) {
            $businessId = $unlock->customerCampaign->campaign->business_id;
            $staffMembers = Staff::where('business_id', $businessId)
                ->where('active', true)
                ->get();

            if ($staffMembers->isEmpty()) {
                continue;
            }

            // Solo crear redemptions para unlocks que estén canjeados
            // O crear algunos para unlocks desbloqueados (simulando que se generó PIN pero no se canjeó)
            if ($unlock->status === 'redeemed' && !$unlock->redemption_id) {
                // Crear redemption confirmada
                $staff = $staffMembers->random();
                $pin = str_pad((string) rand(0, 9999), 4, '0', STR_PAD_LEFT);

                $redemption = Redemption::create([
                    'reward_unlock_id' => $unlock->id,
                    'customer_campaign_id' => $unlock->customer_campaign_id,
                    'reward_id' => $unlock->reward_id,
                    'staff_id' => $staff->id,
                    'pin_code' => $pin,
                    'confirmed_at' => $unlock->redeemed_at ?? now(),
                    'meta' => [
                        'seeded' => true,
                    ],
                ]);

                // Crear redemption_pin (ya verificado)
                RedemptionPin::create([
                    'redemption_id' => $redemption->id,
                    'pin' => $pin,
                    'expires_at' => now()->subMinutes(5), // Expirado
                    'attempts' => 1,
                    'verified_at' => $redemption->confirmed_at->subMinutes(1),
                ]);

                // Actualizar unlock con redemption_id
                $unlock->update(['redemption_id' => $redemption->id]);
            } elseif ($unlock->status === 'unlocked' && fake()->boolean(30)) {
                // Crear algunos redemptions pendientes (con PIN generado pero no verificado)
                $pin = str_pad((string) rand(0, 9999), 4, '0', STR_PAD_LEFT);

                $redemption = Redemption::create([
                    'reward_unlock_id' => $unlock->id,
                    'customer_campaign_id' => $unlock->customer_campaign_id,
                    'reward_id' => $unlock->reward_id,
                    'staff_id' => null,
                    'pin_code' => $pin,
                    'confirmed_at' => null,
                    'meta' => [
                        'seeded' => true,
                        'pending' => true,
                    ],
                ]);

                // Crear redemption_pin (pendiente de verificación)
                RedemptionPin::create([
                    'redemption_id' => $redemption->id,
                    'pin' => $pin,
                    'expires_at' => fake()->boolean(50) 
                        ? now()->addMinutes(fake()->numberBetween(1, 3)) // Aún válido
                        : now()->subMinutes(fake()->numberBetween(1, 10)), // Expirado
                    'attempts' => 0,
                    'verified_at' => null,
                ]);
            }
        }

        $this->command->info('Redemptions creados exitosamente.');
    }
}
