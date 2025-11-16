<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\Customer;
use App\Models\CustomerCampaign;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CustomerCampaign>
 */
class CustomerCampaignFactory extends Factory
{
    protected $model = CustomerCampaign::class;

    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'campaign_id' => Campaign::factory(),
            'stamps' => 0,
            'redeemed_at' => null,
        ];
    }

    public function withStamps(int $stamps): static
    {
        return $this->state(fn (array $attributes) => [
            'stamps' => $stamps,
        ]);
    }
}

