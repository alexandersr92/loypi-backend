<?php

namespace Database\Factories;

use App\Models\CustomerCampaign;
use App\Models\Staff;
use App\Models\Stamp;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Stamp>
 */
class StampFactory extends Factory
{
    protected $model = Stamp::class;

    public function definition(): array
    {
        return [
            'customer_campaign_id' => CustomerCampaign::factory(),
            'staff_id' => Staff::factory(),
            'meta' => [
                'notes' => fake()->optional()->sentence(),
            ],
        ];
    }
}

