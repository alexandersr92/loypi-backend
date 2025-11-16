<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\Customer;
use App\Models\CustomerStreak;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CustomerStreak>
 */
class CustomerStreakFactory extends Factory
{
    protected $model = CustomerStreak::class;

    public function definition(): array
    {
        $currentStreak = fake()->numberBetween(0, 30);
        $longestStreak = fake()->numberBetween($currentStreak, 60);

        return [
            'customer_id' => Customer::factory(),
            'campaign_id' => Campaign::factory(),
            'current_streak' => $currentStreak,
            'longest_streak' => $longestStreak,
            'last_event_date' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }

    public function active(int $days = 7): static
    {
        return $this->state(fn (array $attributes) => [
            'current_streak' => $days,
            'longest_streak' => max($days, fake()->numberBetween($days, 60)),
            'last_event_date' => now()->subDays(fake()->numberBetween(0, 1)),
        ]);
    }
}

