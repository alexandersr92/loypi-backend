<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\Customer;
use App\Models\CustomerToken;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CustomerToken>
 */
class CustomerTokenFactory extends Factory
{
    protected $model = CustomerToken::class;

    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'business_id' => Business::factory(),
            'token' => CustomerToken::generateToken(),
            'expires_at' => now()->addMonths(6),
            'active' => true,
        ];
    }
}

