<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\Campaign;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Campaign>
 */
class CampaignFactory extends Factory
{
    protected $model = Campaign::class;

    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'limit' => fake()->optional()->numberBetween(100, 1000),
            'redeemed_count' => 0,
            'reward_json' => [
                'title' => fake()->sentence(),
                'description' => fake()->paragraph(),
            ],
            'required_stamps' => null,
            'active' => true,
            'cover_image' => null,
            'cover_color' => fake()->hexColor(),
            'logo_url' => null,
        ];
    }

    public function punchCard(int $requiredStamps = 10): static
    {
        return $this->state(fn (array $attributes) => [
            'required_stamps' => $requiredStamps,
            'reward_json' => [
                'type' => 'punch_card',
                'title' => 'Tarjeta de Sellos',
                'description' => "Obtén {$requiredStamps} sellos y gana un premio",
            ],
        ]);
    }

    public function streak(int $requiredDays = 7): static
    {
        return $this->state(fn (array $attributes) => [
            'required_stamps' => null,
            'reward_json' => [
                'type' => 'streak',
                'title' => 'Racha de Visitas',
                'description' => "Visita {$requiredDays} días seguidos y gana un premio",
            ],
        ]);
    }
}

