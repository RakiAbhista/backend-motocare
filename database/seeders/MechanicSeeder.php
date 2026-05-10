<?php

namespace Database\Seeders;

use App\Models\Mechanic;
use Illuminate\Database\Seeder;

class MechanicSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Mechanic::create([
            'user_id' => 6,
            'status' => 'available',
        ]);

        Mechanic::create([
            'user_id' => 7,
            'status' => 'available',
        ]);
    }
}
