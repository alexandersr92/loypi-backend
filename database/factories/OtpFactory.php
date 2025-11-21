<?php

namespace Database\Factories;

use App\Models\Otp;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Otp>
 */
class OtpFactory extends Factory
{
    protected $model = Otp::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $status = fake()->randomElement(['pending', 'verified', 'expired']);
        $verifiedAt = null;
        $expiresAt = now()->addMinutes(10);

        if ($status === 'verified') {
            $verifiedAt = fake()->dateTimeBetween('-1 hour', 'now');
            $expiresAt = fake()->dateTimeBetween($verifiedAt, 'now');
        } elseif ($status === 'expired') {
            $expiresAt = fake()->dateTimeBetween('-1 day', '-1 minute');
        }

        return [
            'phone' => '+52' . fake()->numerify('##########'),
            'code' => fake()->numerify('######'), // 6 dígitos
            'type' => fake()->randomElement(['whatsapp', 'sms']),
            'status' => $status,
            'expires_at' => $expiresAt,
            'verified_at' => $verifiedAt,
            'ip_address' => fake()->optional()->ipv4(),
        ];
    }

    /**
     * Indicate that the OTP is pending (not verified)
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'verified_at' => null,
            'expires_at' => now()->addMinutes(10),
        ]);
    }

    /**
     * Indicate that the OTP is verified
     */
    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'verified',
            'verified_at' => fake()->dateTimeBetween('-1 hour', 'now'),
            'expires_at' => fake()->dateTimeBetween('-1 hour', 'now'),
        ]);
    }

    /**
     * Indicate that the OTP is expired
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'expired',
            'verified_at' => null,
            'expires_at' => fake()->dateTimeBetween('-1 day', '-1 minute'),
        ]);
    }
}
