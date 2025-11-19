<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear tu usuario admin específico
        User::firstOrCreate(
            ['email' => 'alexande@loypi.com'],
            [
                'name' => 'Alexander',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'status' => 'active',
                'email_verified_at' => now(),
                'timezone' => 'America/Mexico_City',
                'locale' => 'es',
            ]
        );

        // Crear usuarios adicionales usando la factory
        User::factory()
            ->count(10)
            ->create();
    }
}
