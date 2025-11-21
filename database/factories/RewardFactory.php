<?php

namespace Database\Factories;

use App\Models\Business;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Reward>
 */
class RewardFactory extends Factory
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
            'name' => fake()->words(2, true),
            'type' => fake()->randomElement(['punch', 'streak']),
            'description' => fake()->optional()->sentence(),
            'image_url' => fake()->optional()->imageUrl(),
            'reward_json' => fake()->optional()->randomElement([
                ['type' => 'discount', 'value' => fake()->numberBetween(10, 50)],
                ['type' => 'free_item', 'item' => fake()->word()],
                null,
            ]),
        ];
    }
}
