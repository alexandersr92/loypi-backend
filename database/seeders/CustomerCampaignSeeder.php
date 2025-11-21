<?php

namespace Database\Seeders;

use App\Models\Campaign;
use App\Models\Customer;
use App\Models\CustomerCampaign;
use Illuminate\Database\Seeder;

class CustomerCampaignSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customers = Customer::all();
        $campaigns = Campaign::all();

        if ($customers->isEmpty() || $campaigns->isEmpty()) {
            $this->command->warn('No hay customers o campaigns. Ejecuta primero CustomerSeeder y CampaignSeeder.');
            return;
        }

        // Registrar cada customer en 1-3 campaigns aleatorias
        foreach ($customers as $customer) {
            $numCampaigns = fake()->numberBetween(1, min(3, $campaigns->count()));
            $selectedCampaigns = $campaigns->random($numCampaigns);

            foreach ($selectedCampaigns as $campaign) {
                // Verificar que el customer y la campaign pertenezcan al mismo business
                if ($customer->business_id !== $campaign->business_id) {
                    continue;
                }

                // Verificar que no esté ya registrado
                $exists = CustomerCampaign::where('customer_id', $customer->id)
                    ->where('campaign_id', $campaign->id)
                    ->exists();

                if (!$exists) {
                    CustomerCampaign::create([
                        'customer_id' => $customer->id,
                        'campaign_id' => $campaign->id,
                        'stamps' => fake()->numberBetween(0, 15),
                        'redeemed_at' => fake()->optional(0.15)->dateTimeBetween('-1 month', 'now'),
                    ]);
                }
            }
        }

        $this->command->info('CustomerCampaigns creados exitosamente.');
    }
}
