<?php

namespace Database\Seeders;

use App\Models\Campaign;
use App\Models\CustomerCampaign;
use App\Models\Staff;
use App\Models\Stamp;
use Illuminate\Database\Seeder;

class StampSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customerCampaigns = CustomerCampaign::with(['campaign', 'customer.business'])->get();

        if ($customerCampaigns->isEmpty()) {
            $this->command->warn('No hay customer_campaigns. Ejecuta primero CustomerCampaignSeeder.');
            return;
        }

        foreach ($customerCampaigns as $customerCampaign) {
            // Obtener staff del mismo business
            $staffMembers = Staff::where('business_id', $customerCampaign->customer->business_id)
                ->where('active', true)
                ->get();

            if ($staffMembers->isEmpty()) {
                continue;
            }

            // Crear 1-5 stamps por customer_campaign
            $numStamps = fake()->numberBetween(1, 5);
            $campaignType = $customerCampaign->campaign->type;

            for ($i = 0; $i < $numStamps; $i++) {
                $staff = $staffMembers->random();

                // El tipo debe coincidir con el tipo de campaign
                $stampType = $campaignType === 'punch' ? 'stamp' : 'streak';

                Stamp::create([
                    'customer_campaign_id' => $customerCampaign->id,
                    'staff_id' => $staff->id,
                    'type' => $stampType,
                    'meta' => [
                        'applied_at' => fake()->dateTimeBetween('-1 month', 'now')->format('c'),
                    ],
                ]);

                // Actualizar el contador de stamps en customer_campaigns
                $customerCampaign->increment('stamps');
            }
        }

        $this->command->info('Stamps creados exitosamente.');
    }
}
