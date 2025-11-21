<?php

namespace Tests\Feature\Api\V1;

use App\Models\Business;
use App\Models\Campaign;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampaignTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_campaign(): void
    {
        $user = User::factory()->create();
        $business = Business::factory()->create(['user_id' => $user->id]);
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/campaigns', [
                'name' => 'Test Campaign',
                'type' => 'punch',
                'description' => 'Test description',
                'active' => true,
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'name',
                    'type',
                    'code',
                ],
            ]);
    }

    public function test_owner_can_list_campaigns(): void
    {
        $user = User::factory()->create();
        $business = Business::factory()->create(['user_id' => $user->id]);
        Campaign::factory()->count(3)->create(['business_id' => $business->id]);
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/campaigns');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'type',
                        'code',
                    ],
                ],
            ]);
    }

    public function test_public_can_get_campaign_by_code(): void
    {
        $business = Business::factory()->create();
        $campaign = Campaign::factory()->create([
            'business_id' => $business->id,
            'code' => 'TEST',
        ]);

        $response = $this->getJson('/api/v1/campaigns/code/TEST');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'code' => 'TEST',
                ],
            ]);
    }
}
