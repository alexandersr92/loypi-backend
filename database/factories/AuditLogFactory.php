<?php

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\Campaign;
use App\Models\Customer;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AuditLog>
 */
class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $actions = ['create', 'update', 'delete', 'login', 'logout', 'redeem', 'stamp', 'unlock'];
        $actorTypes = [User::class, Staff::class, Customer::class];
        $auditableTypes = [Campaign::class, Customer::class, Staff::class];

        $action = fake()->randomElement($actions);
        $actorType = fake()->randomElement($actorTypes);

        return [
            'action' => $action,
            'actor_type' => $actorType,
            'actor_id' => $actorType::factory(),
            'auditable_type' => fake()->boolean(70) ? fake()->randomElement($auditableTypes) : null,
            'auditable_id' => null,
            'description' => $this->generateDescription($action),
            'old_values' => fake()->boolean(30) ? ['status' => 'active'] : null,
            'new_values' => fake()->boolean(30) ? ['status' => 'inactive'] : null,
            'meta' => [
                'method' => fake()->randomElement(['GET', 'POST', 'PUT', 'DELETE', 'PATCH']),
                'endpoint' => fake()->randomElement(['/api/v1/campaigns', '/api/v1/customers', '/api/v1/staff']),
            ],
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
        ];
    }

    /**
     * Generate description based on action
     */
    private function generateDescription(string $action): string
    {
        return match ($action) {
            'create' => 'Created new resource',
            'update' => 'Updated resource',
            'delete' => 'Deleted resource',
            'login' => 'User logged in',
            'logout' => 'User logged out',
            'redeem' => 'Redeemed reward',
            'stamp' => 'Applied stamp',
            'unlock' => 'Unlocked reward',
            default => 'Performed action',
        };
    }

    /**
     * Indicate that the audit log is for a create action
     */
    public function created(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => 'create',
            'description' => 'Created new resource',
        ]);
    }

    /**
     * Indicate that the audit log is for an update action
     */
    public function updated(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => 'update',
            'description' => 'Updated resource',
            'old_values' => ['name' => fake()->name()],
            'new_values' => ['name' => fake()->name()],
        ]);
    }

    /**
     * Indicate that the audit log is for a delete action
     */
    public function deleted(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => 'delete',
            'description' => 'Deleted resource',
        ]);
    }

    /**
     * Indicate that the audit log is for a login action
     */
    public function login(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => 'login',
            'description' => 'User logged in',
            'auditable_type' => null,
            'auditable_id' => null,
        ]);
    }
}
