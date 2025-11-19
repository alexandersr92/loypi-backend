<?php

namespace Database\Factories;

use App\Models\Business;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Staff>
 */
class StaffFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'code' => 'EMP' . fake()->numerify('###'),
            'name' => fake()->name(),
            'passcode_hash' => Hash::make('1234'), // PIN por defecto: 1234
            'active' => fake()->boolean(90), // 90% activos
            'failed_login_attempts' => fake()->numberBetween(0, 3),
            'locked_until' => fake()->optional(0.1)->dateTimeBetween('now', '+30 days'), // 10% bloqueados
            'last_login_at' => fake()->optional(0.7)->dateTimeBetween('-1 month', 'now'), // 70% han iniciado sesión
        ];
    }
}
