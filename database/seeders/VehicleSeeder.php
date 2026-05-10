<?php

namespace Database\Seeders;

use App\Models\Vehicle;
use Illuminate\Database\Seeder;

class VehicleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Vehicle::create([
            'user_id' => 3,
            'vehicle_type' => 'Motor Roda 2',
            'brand' => 'Honda',
            'model' => 'PCX 160',
            'plate_number' => 'B 1234 ABC',
            'manufacturing_year' => 2022,
            'registration_doc' => 'doc_honda_pcx.pdf',
        ]);

        Vehicle::create([
            'user_id' => 3,
            'vehicle_type' => 'Motor Roda 2',
            'brand' => 'Yamaha',
            'model' => 'NMAX',
            'plate_number' => 'B 5678 DEF',
            'manufacturing_year' => 2021,
            'registration_doc' => 'doc_yamaha_nmax.pdf',
        ]);

        Vehicle::create([
            'user_id' => 4,
            'vehicle_type' => 'Motor Roda 2',
            'brand' => 'Suzuki',
            'model' => 'GSX-S150',
            'plate_number' => 'B 9012 GHI',
            'manufacturing_year' => 2023,
            'registration_doc' => 'doc_suzuki_gsx.pdf',
        ]);

        Vehicle::create([
            'user_id' => 5,
            'vehicle_type' => 'Motor Roda 2',
            'brand' => 'Kawasaki',
            'model' => 'Ninja 250',
            'plate_number' => 'B 3456 JKL',
            'manufacturing_year' => 2020,
            'registration_doc' => 'doc_kawasaki_ninja.pdf',
        ]);
    }
}
