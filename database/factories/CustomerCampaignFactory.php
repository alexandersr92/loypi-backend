<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CustomerCampaign>
 */
class CustomerCampaignFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'campaign_id' => Campaign::factory(),
            'stamps' => fake()->numberBetween(0, 20),
            'redeemed_at' => fake()->optional(0.2)->dateTimeBetween('-1 month', 'now'),
        ];
    }
}
