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
            'name' => 'Workshop Semarang A',
            'latitude' => -7.01296700,
            'longitude' => 110.46409700,
        ]);

        Workshop::create([
            'name' => 'Workshop Semarang B',
            'latitude' => -6.98127600,
            'longitude' => 110.39045400,
        ]);

        Workshop::create([
            'name' => 'Workshop Semarang C',
            'latitude' => -6.96474800,
            'longitude' => 110.45826100,
        ]);

        Workshop::create([
            'name' => 'Workshop Semarang D',
            'latitude' => -7.01577800,
            'longitude' => 110.47422500,
        ]);

        Workshop::create([
            'name' => 'Workshop Semarang E',
            'latitude' => -7.06954400,
            'longitude' => 110.43256800,
        ]);
    }
}
