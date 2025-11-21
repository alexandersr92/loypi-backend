<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Otp;
use App\Models\User;
use Illuminate\Database\Seeder;

class OtpSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener todos los phones de users y customers
        $userPhones = User::whereNotNull('phone')->pluck('phone')->toArray();
        $customerPhones = Customer::whereNotNull('phone')->pluck('phone')->toArray();
        $allPhones = array_unique(array_merge($userPhones, $customerPhones));

        if (empty($allPhones)) {
            $this->command->warn('No hay phones en users o customers. Ejecuta primero UserSeeder y CustomerSeeder.');
            return;
        }

        // Crear OTPs para cada phone (algunos verificados, algunos expirados, algunos pendientes)
        foreach ($allPhones as $phone) {
            // Crear 1-3 OTPs por phone
            $numOtps = fake()->numberBetween(1, 3);

            for ($i = 0; $i < $numOtps; $i++) {
                $status = fake()->randomElement(['pending', 'verified', 'expired']);
                $verifiedAt = null;
                $expiresAt = now()->addMinutes(10);

                if ($status === 'verified') {
                    $verifiedAt = fake()->dateTimeBetween('-7 days', 'now');
                    $expiresAt = fake()->dateTimeBetween($verifiedAt, 'now');
                } elseif ($status === 'expired') {
                    $expiresAt = fake()->dateTimeBetween('-30 days', '-1 minute');
                }

                Otp::create([
                    'phone' => $phone,
                    'code' => fake()->numerify('######'), // 6 dígitos
                    'type' => fake()->randomElement(['whatsapp', 'sms']),
                    'status' => $status,
                    'expires_at' => $expiresAt,
                    'verified_at' => $verifiedAt,
                    'ip_address' => fake()->optional()->ipv4(),
                ]);
            }
        }

        $this->command->info('OTPs creados exitosamente.');
    }
}
