<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\Campaign;
use App\Models\Customer;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Seeder;

class AuditLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $staffMembers = Staff::all();
        $customers = Customer::all();
        $campaigns = Campaign::all();

        if ($users->isEmpty() && $staffMembers->isEmpty() && $customers->isEmpty()) {
            $this->command->warn('No hay users, staff o customers. Ejecuta primero UserSeeder, StaffSeeder y CustomerSeeder.');
            return;
        }

        // Crear logs de login/logout
        foreach ($users->take(10) as $user) {
            AuditLog::factory()->login()->create([
                'actor_type' => User::class,
                'actor_id' => $user->id,
                'description' => 'User logged in',
            ]);

            if (fake()->boolean(80)) {
                AuditLog::factory()->create([
                    'action' => 'logout',
                    'actor_type' => User::class,
                    'actor_id' => $user->id,
                    'description' => 'User logged out',
                ]);
            }
        }

        // Crear logs de CRUD de campaigns
        if ($campaigns->isNotEmpty() && $users->isNotEmpty()) {
            foreach ($campaigns->take(5) as $campaign) {
                $user = $users->random();

                AuditLog::factory()->created()->create([
                    'actor_type' => User::class,
                    'actor_id' => $user->id,
                    'auditable_type' => Campaign::class,
                    'auditable_id' => $campaign->id,
                    'description' => "Created campaign: {$campaign->name}",
                ]);

                if (fake()->boolean(60)) {
                    AuditLog::factory()->updated()->create([
                        'actor_type' => User::class,
                        'actor_id' => $user->id,
                        'auditable_type' => Campaign::class,
                        'auditable_id' => $campaign->id,
                        'description' => "Updated campaign: {$campaign->name}",
                    ]);
                }
            }
        }

        // Crear logs de staff actions
        if ($staffMembers->isNotEmpty()) {
            foreach ($staffMembers->take(10) as $staff) {
                AuditLog::factory()->create([
                    'action' => 'stamp',
                    'actor_type' => Staff::class,
                    'actor_id' => $staff->id,
                    'description' => 'Applied stamp to customer',
                ]);

                if (fake()->boolean(30)) {
                    AuditLog::factory()->create([
                        'action' => 'redeem',
                        'actor_type' => Staff::class,
                        'actor_id' => $staff->id,
                        'description' => 'Redeemed reward for customer',
                    ]);
                }
            }
        }

        // Crear logs de customer actions
        if ($customers->isNotEmpty()) {
            foreach ($customers->take(10) as $customer) {
                AuditLog::factory()->login()->create([
                    'action' => 'login',
                    'actor_type' => Customer::class,
                    'actor_id' => $customer->id,
                    'description' => 'Customer logged in',
                ]);

                if (fake()->boolean(20)) {
                    AuditLog::factory()->create([
                        'action' => 'unlock',
                        'actor_type' => Customer::class,
                        'actor_id' => $customer->id,
                        'description' => 'Unlocked reward',
                    ]);
                }
            }
        }

        $this->command->info('Audit logs creados exitosamente.');
    }
}
