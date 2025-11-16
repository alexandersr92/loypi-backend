<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\Campaign;
use App\Models\Customer;
use App\Models\CustomerCampaign;
use App\Models\CustomerStreak;
use App\Models\CustomerToken;
use App\Models\Reward;
use App\Models\Staff;
use App\Models\Stamp;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Crear usuario admin
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@loypi.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        // Crear 4 negocios con sus owners
        $businesses = collect();

        for ($i = 1; $i <= 4; $i++) {
            $owner = User::create([
                'name' => "Owner {$i}",
                'email' => "owner{$i}@example.com",
                'password' => Hash::make('password'),
                'role' => 'owner',
            ]);

            $business = Business::create([
                'user_id' => $owner->id,
                'slug' => "negocio-{$i}",
                'name' => "Negocio {$i}",
                'branding_json' => [
                    'primary_color' => fake()->hexColor(),
                    'secondary_color' => fake()->hexColor(),
                ],
            ]);

            $businesses->push($business);

            // Crear 2-3 staff por negocio
            Staff::factory()
                ->count(fake()->numberBetween(2, 3))
                ->for($business)
                ->create();

            // Crear 10 clientes por negocio
            $customers = collect();
            for ($j = 1; $j <= 10; $j++) {
                $customers->push(Customer::factory()->create());
            }

            // Crear tokens para cada cliente en este negocio
            foreach ($customers as $customer) {
                CustomerToken::factory()
                    ->for($customer)
                    ->for($business)
                    ->create();
            }

            // Crear 5 campañas de tipo punch card
            $punchCampaigns = collect();
            for ($j = 1; $j <= 5; $j++) {
                $punchCampaigns->push(
                    Campaign::factory()
                        ->punchCard(fake()->numberBetween(5, 15))
                        ->for($business)
                        ->create()
                );
            }

            // Crear 5 campañas de tipo streak
            $streakCampaigns = collect();
            for ($j = 1; $j <= 5; $j++) {
                $streakCampaigns->push(
                    Campaign::factory()
                        ->streak(fake()->numberBetween(5, 14))
                        ->for($business)
                        ->create()
                );
            }

            $allCampaigns = $punchCampaigns->merge($streakCampaigns);

            // Crear premios para cada campaña
            foreach ($allCampaigns as $campaign) {
                if ($campaign->required_stamps !== null) {
                    // Es una campaña punch card
                    Reward::factory()
                        ->punch($campaign->required_stamps)
                        ->for($campaign)
                        ->create();
                } else {
                    // Es una campaña streak - obtener el threshold del reward_json o usar un valor por defecto
                    $streakDays = fake()->numberBetween(5, 14);
                    Reward::factory()
                        ->streak($streakDays)
                        ->for($campaign)
                        ->create();
                }
            }

            // Asignar clientes a campañas y crear datos de progreso
            foreach ($customers as $customer) {
                // Cada cliente participa en 3-7 campañas aleatorias
                $numCampaigns = min(fake()->numberBetween(3, 7), $allCampaigns->count());
                $selectedCampaigns = $allCampaigns->random($numCampaigns);

                foreach ($selectedCampaigns as $campaign) {
                    $customerCampaign = CustomerCampaign::factory()
                        ->for($customer)
                        ->for($campaign)
                        ->withStamps(fake()->numberBetween(0, $campaign->required_stamps ?? 20))
                        ->create();

                    // Si es campaña streak, crear streak data
                    if ($campaign->required_stamps === null) {
                        CustomerStreak::factory()
                            ->active(fake()->numberBetween(0, 15))
                            ->for($customer)
                            ->for($campaign)
                            ->create();
                    }

                    // Crear algunos sellos para campañas punch card
                    if ($campaign->required_stamps !== null && $customerCampaign->stamps > 0) {
                        $stampsCount = min($customerCampaign->stamps, 10);
                        
                        $staffMembers = $business->staff;
                        
                        if ($staffMembers->isNotEmpty()) {
                            for ($k = 0; $k < $stampsCount; $k++) {
                                Stamp::factory()
                                    ->for($customerCampaign)
                                    ->for($staffMembers->random())
                                    ->create();
                            }
                        }
                    }
                }
            }
        }

        $this->command->info('✅ Se crearon:');
        $this->command->info("   - 1 usuario admin");
        $this->command->info("   - 4 usuarios owners");
        $this->command->info("   - 4 negocios");
        $this->command->info("   - " . Staff::count() . " staff");
        $this->command->info("   - " . Customer::count() . " clientes");
        $this->command->info("   - " . Campaign::count() . " campañas (5 punch + 5 streak por negocio)");
        $this->command->info("   - " . Reward::count() . " premios");
        $this->command->info("   - " . CustomerCampaign::count() . " participaciones de clientes");
        $this->command->info("   - " . Stamp::count() . " sellos");
        $this->command->info("   - " . CustomerStreak::count() . " rachas");
    }
}
