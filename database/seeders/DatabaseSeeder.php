<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Database\Seeders\BookingSeeder;
use Database\Seeders\EmergencySeeder;
use Database\Seeders\MechanicSeeder;
use Database\Seeders\OrderDetailSeeder;
use Database\Seeders\OrderSeeder;
use Database\Seeders\ServiceSeeder;
use Database\Seeders\UserSeeder;
use Database\Seeders\VehicleSeeder;
use Database\Seeders\VoucherSeeder;
use Database\Seeders\WorkshopSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            VehicleSeeder::class,
            ServiceSeeder::class,
            WorkshopSeeder::class,
            MechanicSeeder::class,
            VoucherSeeder::class,
            BookingSeeder::class,
            EmergencySeeder::class,
            OrderSeeder::class,
            OrderDetailSeeder::class,
        ]);
    }
}
