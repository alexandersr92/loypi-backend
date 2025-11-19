<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Business>
 */
class BusinessFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->company();

        return [
            'user_id' => User::factory(),
            'slug' => Str::slug($name) . '-' . fake()->unique()->numerify('####'),
            'name' => $name,
            'description' => fake()->optional()->paragraph(),
            'logo' => fake()->optional()->imageUrl(200, 200, 'business'),
            'branding_json' => [
                'primary_color' => fake()->hexColor(),
                'secondary_color' => fake()->hexColor(),
                'logo_url' => fake()->optional()->imageUrl(200, 200),
            ],
            'address' => fake()->optional()->address(),
            'phone' => fake()->e164PhoneNumber(),
            'email' => fake()->optional()->companyEmail(),
            'website' => fake()->optional()->url(),
            'city' => fake()->city(),
            'state' => fake()->state(),
            'country' => fake()->country(),
        ];
    }

}
