<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\Reward;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Reward>
 */
class RewardFactory extends Factory
{
    protected $model = Reward::class;

    public function definition(): array
    {
        return [
            'campaign_id' => Campaign::factory(),
            'name' => fake()->words(2, true),
            'type' => 'punch',
            'threshold_int' => 10,
            'description' => fake()->sentence(),
            'per_customer_limit' => null,
            'global_limit' => null,
            'redeemed_count' => 0,
            'active' => true,
            'reward_json' => [
                'title' => fake()->sentence(),
                'value' => fake()->randomFloat(2, 10, 100),
            ],
        ];
    }

    public function punch(int $threshold = 10): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'punch',
            'threshold_int' => $threshold,
            'name' => "Premio por {$threshold} sellos",
            'reward_json' => [
                'type' => 'punch',
                'title' => "Descuento del 20%",
                'description' => "Obtén {$threshold} sellos para desbloquear este premio",
            ],
        ]);
    }

    public function streak(int $threshold = 7): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'streak',
            'threshold_int' => $threshold,
            'name' => "Premio por {$threshold} días de racha",
            'reward_json' => [
                'type' => 'streak',
                'title' => "Producto gratis",
                'description' => "Visita {$threshold} días seguidos para desbloquear este premio",
            ],
        ]);
    }
}

