<?php

namespace Database\Seeders;

use App\Models\Workshop;
use Illuminate\Database\Seeder;

class WorkshopSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Workshop::create([
            'name' => 'Workshop Pusat Jakarta',
            'latitude' => -6.2088,
            'longitude' => 106.8456,
        ]);

        Workshop::create([
            'name' => 'Workshop Bandung',
            'latitude' => -6.9147,
            'longitude' => 107.6098,
        ]);

        Workshop::create([
            'name' => 'Workshop Surabaya',
            'latitude' => -7.2506,
            'longitude' => 112.7508,
        ]);

        Workshop::create([
            'name' => 'Workshop Medan',
            'latitude' => 3.1957,
            'longitude' => 98.6722,
        ]);

        Workshop::create([
            'name' => 'Workshop Makassar',
            'latitude' => -5.1477,
            'longitude' => 119.4327,
        ]);
    }
}
