<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\Staff;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class StaffSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear staff para cada negocio
        $businesses = Business::all();

        foreach ($businesses as $business) {
            // Crear exactamente 3 staff por negocio
            for ($i = 1; $i <= 3; $i++) {
                Staff::factory()
                    ->for($business, 'business')
                    ->create([
                        'code' => 'EMP' . str_pad($i, 3, '0', STR_PAD_LEFT),
                        'name' => fake()->name(),
                        'passcode_hash' => Hash::make('1234'), // PIN por defecto: 1234
                        'active' => true,
                        'failed_login_attempts' => 0,
                        'locked_until' => null,
                        'last_login_at' => fake()->optional(0.7)->dateTimeBetween('-1 month', 'now'),
                    ]);
            }
        }
    }
}
