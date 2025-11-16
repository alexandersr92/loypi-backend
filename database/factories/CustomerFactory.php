<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Services\ShortCodeService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'short_code' => ShortCodeService::generate(),
            'phone' => fake()->phoneNumber(),
            'name' => fake()->name(),
        ];
    }
}

