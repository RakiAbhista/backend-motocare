<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Service::create([
            'service_name' => 'Ganti Oli',
            'base_price' => 75000,
        ]);

        Service::create([
            'service_name' => 'Ganti Filter Oli',
            'base_price' => 50000,
        ]);

        Service::create([
            'service_name' => 'Ganti Kampas Rem',
            'base_price' => 150000,
        ]);

        Service::create([
            'service_name' => 'Tune-Up Engine',
            'base_price' => 200000,
        ]);

        Service::create([
            'service_name' => 'Ganti Ban',
            'base_price' => 300000,
        ]);

        Service::create([
            'service_name' => 'Perbaikan Sistem Kelistrikan',
            'base_price' => 250000,
        ]);

        Service::create([
            'service_name' => 'Servis Rantai',
            'base_price' => 100000,
        ]);

        Service::create([
            'service_name' => 'Perbaikan Karburator',
            'base_price' => 180000,
        ]);
    }
}
