<?php

namespace Database\Factories;

use App\Models\CustomerCampaign;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Stamp>
 */
class StampFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_campaign_id' => CustomerCampaign::factory(),
            'staff_id' => Staff::factory(),
            'type' => fake()->randomElement(['stamp', 'streak']),
            'meta' => [
                'applied_at' => fake()->dateTimeBetween('-1 month', 'now')->format('c'),
            ],
        ];
    }
}
