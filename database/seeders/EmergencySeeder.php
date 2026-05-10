<?php

namespace Database\Seeders;

use App\Models\Emergency;
use Illuminate\Database\Seeder;

class EmergencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Emergency::create([
            'user_id' => 3,
            'mechanic_id' => 1,
            'vehicle_id' => 1,
            'vehicle_model' => 'PCX 160',
            'vehicle_brand' => 'Honda',
            'vehicle_type' => 'Motor Roda 2',
            'plate_number' => 'B 1234 ABC',
            'workshop_id' => 1,
            'latitude' => -6.2088,
            'longitude' => 106.8456,
            'damage_photo' => 'emergency_1.jpg',
            'status' => 'dispatched',
            'requested_at' => now()->subHours(2),
        ]);

        Emergency::create([
            'user_id' => 4,
            'mechanic_id' => 2,
            'vehicle_id' => 3,
            'vehicle_model' => 'GSX-S150',
            'vehicle_brand' => 'Suzuki',
            'vehicle_type' => 'Motor Roda 2',
            'plate_number' => 'B 9012 GHI',
            'workshop_id' => 2,
            'latitude' => -6.9147,
            'longitude' => 107.6098,
            'damage_photo' => 'emergency_2.jpg',
            'status' => 'resolved',
            'requested_at' => now()->subHours(5),
        ]);

        Emergency::create([
            'user_id' => 5,
            'mechanic_id' => null,
            'vehicle_id' => 4,
            'vehicle_model' => 'Ninja 250',
            'vehicle_brand' => 'Kawasaki',
            'vehicle_type' => 'Motor Roda 2',
            'plate_number' => 'B 3456 JKL',
            'workshop_id' => 3,
            'latitude' => -7.2506,
            'longitude' => 112.7508,
            'damage_photo' => 'emergency_3.jpg',
            'status' => 'pending',
            'requested_at' => now()->subMinutes(30),
        ]);
    }
}
