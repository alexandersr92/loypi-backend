<?php

namespace Tests\Feature\Api\V1;

use App\Models\Business;
use App\Models\Campaign;
use App\Models\Customer;
use App\Models\CustomerCampaign;
use App\Models\Reward;
use App\Models\RewardUnlock;
use App\Models\Staff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RedemptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_list_unlocks(): void
    {
        $business = Business::factory()->create();
        $campaign = Campaign::factory()->create(['business_id' => $business->id]);
        $customer = Customer::factory()->create(['business_id' => $business->id]);
        $customerCampaign = CustomerCampaign::factory()->create([
            'customer_id' => $customer->id,
            'campaign_id' => $campaign->id,
        ]);
        RewardUnlock::factory()->count(3)->create([
            'customer_campaign_id' => $customerCampaign->id,
        ]);

        $token = $customer->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/customers/me/unlocks');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'status',
                        'reward',
                    ],
                ],
            ]);
    }
}
