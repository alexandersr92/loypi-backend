<?php

namespace Database\Factories;

use App\Models\CustomField;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CustomFieldOption>
 */
class CustomFieldOptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $value = fake()->unique()->slug();
        $label = fake()->words(2, true);
        
        return [
            'custom_field_id' => CustomField::factory(),
            'value' => $value,
            'label' => ucfirst($label),
            'sort_order' => fake()->numberBetween(0, 10),
        ];
    }
}
