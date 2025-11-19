<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\User;
use Illuminate\Database\Seeder;

class BusinessSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear un negocio para cada usuario owner (1:1)
        $owners = User::where('role', 'owner')->get();

        foreach ($owners as $owner) {
            // Solo crear negocio si el usuario no tiene uno ya
            if (! $owner->business) {
                Business::factory()
                    ->for($owner, 'user')
                    ->create();
            }
        }
    }
}
