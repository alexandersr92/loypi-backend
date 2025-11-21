<?php

namespace Database\Factories;

use App\Models\Business;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Campaign>
 */
class CampaignFactory extends Factory
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
            'type' => fake()->randomElement(['punch', 'streak']),
            'name' => fake()->words(3, true),
            'description' => fake()->optional()->paragraph(),
            'limit' => fake()->optional()->numberBetween(10, 1000),
            'redeemed_count' => 0,
            'reward_json' => fake()->optional()->randomElement([
                ['type' => 'discount', 'value' => fake()->numberBetween(10, 50)],
                ['type' => 'free_item', 'item' => fake()->word()],
                null,
            ]),
            'required_stamps' => fake()->optional()->numberBetween(5, 20),
            'active' => true,
            'cover_image' => fake()->optional()->imageUrl(800, 400, 'campaign'),
            'cover_color' => fake()->optional()->hexColor(),
            'logo_url' => fake()->optional()->imageUrl(200, 200, 'logo'),
            'streak_time_limit_hours' => fake()->optional()->randomElement([24, 48, 72]),
            'streak_reset_time' => fake()->optional()->randomElement(['00:00:00', '06:00:00', '12:00:00', null]),
        ];
    }
}
