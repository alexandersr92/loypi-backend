<?php

namespace Tests\Feature\Api\V1;

use App\Models\Business;
use App\Models\Campaign;
use App\Models\Customer;
use App\Models\CustomerCampaign;
use App\Models\Staff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StampTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_apply_stamp(): void
    {
        $business = Business::factory()->create();
        $campaign = Campaign::factory()->create([
            'business_id' => $business->id,
            'type' => 'punch',
            'code' => 'TEST',
        ]);
        $customer = Customer::factory()->create([
            'business_id' => $business->id,
        ]);
        $customerCampaign = CustomerCampaign::factory()->create([
            'customer_id' => $customer->id,
            'campaign_id' => $campaign->id,
        ]);
        $staff = Staff::factory()->create([
            'business_id' => $business->id,
            'active' => true,
        ]);
        $token = $staff->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/staff/apply-stamp', [
                'customer_code' => $customer->short_code,
                'campaign_code' => $campaign->code,
                'type' => 'stamp',
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'stamp_id',
                    'stamps',
                ],
            ]);
    }
}
