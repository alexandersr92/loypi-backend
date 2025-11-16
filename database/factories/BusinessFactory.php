<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Business>
 */
class BusinessFactory extends Factory
{
    protected $model = Business::class;

    public function definition(): array
    {
        $name = fake()->company();
        $slug = \Illuminate\Support\Str::slug($name);

        return [
            'user_id' => User::factory(),
            'slug' => $slug,
            'name' => $name,
            'branding_json' => [
                'primary_color' => fake()->hexColor(),
                'secondary_color' => fake()->hexColor(),
                'logo' => null,
                'cover_image' => null,
            ],
        ];
    }
}

