<?php

namespace Tests\Feature\Api\V1;

use App\Models\Business;
use App\Models\Campaign;
use App\Models\Customer;
use App\Models\Otp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_register_via_qr(): void
    {
        $business = Business::factory()->create();

        // Crear OTP primero
        $otp = \App\Models\Otp::factory()->create([
            'phone' => '+521234567890',
            'code' => '123456',
            'status' => 'pending',
            'expires_at' => now()->addMinutes(10),
        ]);

        $response = $this->postJson('/api/v1/customers/register', [
            'business_slug' => $business->slug,
            'phone' => '+521234567890',
            'name' => 'Test Customer',
            'otp_code' => '123456',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'customer',
                    'token',
                ],
            ]);
    }

    public function test_customer_can_login_with_otp(): void
    {
        $business = Business::factory()->create();
        $customer = Customer::factory()->create([
            'business_id' => $business->id,
            'phone' => '+521234567890',
        ]);

        $otp = Otp::factory()->create([
            'phone' => '+521234567890',
            'code' => '123456',
            'status' => 'pending',
            'expires_at' => now()->addMinutes(10),
        ]);

        $response = $this->postJson('/api/v1/customers/login', [
            'business_slug' => $business->slug,
            'phone' => '+521234567890',
            'otp_code' => '123456',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'customer',
                    'token',
                ],
            ]);
    }

    public function test_customer_can_get_authenticated_customer(): void
    {
        $business = Business::factory()->create();
        $customer = Customer::factory()->create([
            'business_id' => $business->id,
        ]);

        $token = $customer->createToken('test-token')->plainTextToken;
        
        // Crear CustomerToken manualmente para que tenga business_id
        \App\Models\CustomerToken::create([
            'customer_id' => $customer->id,
            'business_id' => $business->id,
            'token' => explode('|', $token)[1],
            'expires_at' => null,
            'active' => true,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/customers/me');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'short_code',
                    'name',
                    'phone',
                ],
            ]);
    }
}
