<?php

namespace Database\Factories;

use App\Models\Business;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CustomField>
 */
class CustomFieldFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = ['text', 'number', 'date', 'boolean', 'select'];
        $type = fake()->randomElement($types);
        
        $key = fake()->unique()->slug();
        $label = fake()->words(2, true);
        
        $extra = [];
        
        // Configuraciones según el tipo
        switch ($type) {
            case 'text':
                $extra = [
                    'placeholder' => fake()->optional()->sentence(),
                    'min_length' => fake()->optional()->numberBetween(1, 5),
                    'max_length' => fake()->optional()->numberBetween(50, 200),
                ];
                break;
            case 'number':
                $extra = [
                    'min' => fake()->optional()->numberBetween(0, 10),
                    'max' => fake()->optional()->numberBetween(100, 1000),
                    'step' => fake()->optional()->randomElement([1, 0.1, 0.01]),
                ];
                break;
            case 'date':
                $extra = [
                    'min_date' => fake()->optional()->date('Y-m-d', '-100 years'),
                    'max_date' => fake()->optional()->date('Y-m-d', 'now'),
                ];
                break;
            case 'boolean':
                $extra = [
                    'default' => fake()->optional()->boolean(),
                ];
                break;
            case 'select':
                $extra = [
                    'multiple' => false,
                ];
                break;
        }
        
        return [
            'business_id' => Business::factory(),
            'key' => $key,
            'label' => ucfirst($label),
            'description' => fake()->optional()->sentence(),
            'type' => $type,
            'required' => fake()->boolean(30), // 30% de probabilidad de ser requerido
            'extra' => !empty($extra) ? $extra : null,
            'active' => true,
        ];
    }

    /**
     * Indicate that the field is of type text.
     */
    public function text(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'text',
            'extra' => [
                'placeholder' => fake()->sentence(),
                'max_length' => fake()->numberBetween(50, 200),
            ],
        ]);
    }

    /**
     * Indicate that the field is of type number.
     */
    public function number(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'number',
            'extra' => [
                'min' => fake()->numberBetween(0, 10),
                'max' => fake()->numberBetween(100, 1000),
            ],
        ]);
    }

    /**
     * Indicate that the field is of type date.
     */
    public function date(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'date',
            'extra' => [
                'min_date' => '1900-01-01',
                'max_date' => 'now',
            ],
        ]);
    }

    /**
     * Indicate that the field is of type boolean.
     */
    public function boolean(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'boolean',
        ]);
    }

    /**
     * Indicate that the field is of type select.
     */
    public function select(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'select',
            'extra' => [
                'multiple' => false,
            ],
        ]);
    }

    /**
     * Indicate that the field is required.
     */
    public function required(): static
    {
        return $this->state(fn (array $attributes) => [
            'required' => true,
        ]);
    }
}
