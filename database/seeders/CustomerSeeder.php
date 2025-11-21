<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\Campaign;
use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $businesses = Business::all();
        $allCampaigns = Campaign::all();

        // Array para rastrear customers que ya existen en múltiples businesses
        $crossBusinessCustomers = [];

        foreach ($businesses as $business) {
            $campaigns = Campaign::where('business_id', $business->id)->get();

            foreach ($campaigns as $campaign) {
                // Crear 10 customers por campaign
                for ($i = 0; $i < 10; $i++) {
                    // Algunos customers estarán en múltiples campaigns del mismo business
                    $phone = null;
                    $existingCustomer = null;

                    // 30% de probabilidad de reutilizar un customer existente en este business
                    if ($i > 0 && fake()->boolean(30)) {
                        $existingCustomer = Customer::where('business_id', $business->id)
                            ->inRandomOrder()
                            ->first();
                        
                        if ($existingCustomer) {
                            // Este customer ya está en otra campaign del mismo business
                            // No necesitamos crear uno nuevo, solo continuar
                            continue;
                        }
                    }

                    // Si no hay customer existente, crear uno nuevo
                    if (!$existingCustomer) {
                        // 20% de probabilidad de reutilizar un phone de otro business (cross-business)
                        if (fake()->boolean(20) && !empty($crossBusinessCustomers)) {
                            $crossBusinessCustomer = fake()->randomElement($crossBusinessCustomers);
                            $phone = $crossBusinessCustomer['phone'];
                        } else {
                            $phone = '+' . fake()->numerify('##########');
                        }

                        // Verificar que el phone no exista ya en este business
                        $customerExists = Customer::where('business_id', $business->id)
                            ->where('phone', $phone)
                            ->exists();

                        if (!$customerExists) {
                            $customer = Customer::create([
                                'business_id' => $business->id,
                                'phone' => $phone,
                                'name' => fake()->name(),
                            ]);

                            // Si es un customer cross-business, agregarlo a la lista
                            if (fake()->boolean(20)) {
                                $crossBusinessCustomers[] = [
                                    'phone' => $phone,
                                    'customer_id' => $customer->id,
                                ];
                            }
                        }
                    }
                }
            }
        }

        // Crear algunos customers adicionales que estén explícitamente en 2 o más businesses
        $selectedPhones = [];
        if (count($crossBusinessCustomers) > 0) {
            $selectedPhones = array_slice($crossBusinessCustomers, 0, min(5, count($crossBusinessCustomers)));
        }

        foreach ($selectedPhones as $crossCustomer) {
            // Seleccionar 2-3 businesses aleatorios adicionales
            $additionalBusinesses = Business::whereNotIn('id', function($query) use ($crossCustomer) {
                $query->select('business_id')
                    ->from('customers')
                    ->where('phone', $crossCustomer['phone']);
            })
            ->inRandomOrder()
            ->limit(fake()->numberBetween(1, 2))
            ->get();

            foreach ($additionalBusinesses as $additionalBusiness) {
                // Verificar que no exista ya
                $exists = Customer::where('business_id', $additionalBusiness->id)
                    ->where('phone', $crossCustomer['phone'])
                    ->exists();

                if (!$exists) {
                    Customer::create([
                        'business_id' => $additionalBusiness->id,
                        'phone' => $crossCustomer['phone'],
                        'name' => fake()->name(), // Puede tener nombre diferente en cada business
                    ]);
                }
            }
        }
    }
}
