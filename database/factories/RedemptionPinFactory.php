<?php

namespace Database\Factories;

use App\Models\Redemption;
use App\Models\RedemptionPin;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RedemptionPin>
 */
class RedemptionPinFactory extends Factory
{
    protected $model = RedemptionPin::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $redemption = Redemption::factory()->create();
        $pin = str_pad((string) rand(0, 9999), 4, '0', STR_PAD_LEFT);

        return [
            'redemption_id' => $redemption->id,
            'pin' => $pin,
            'expires_at' => now()->addMinutes(3),
            'attempts' => 0,
            'verified_at' => null,
        ];
    }

    /**
     * Indicate that the PIN is verified
     */
    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'verified_at' => fake()->dateTimeBetween($attributes['created_at'] ?? '-1 hour', 'now'),
            'attempts' => fake()->numberBetween(1, 3),
        ]);
    }

    /**
     * Indicate that the PIN is expired
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => fake()->dateTimeBetween('-1 day', '-1 minute'),
        ]);
    }
}
