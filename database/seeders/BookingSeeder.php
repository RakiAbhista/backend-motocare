<?php

namespace Database\Seeders;

use App\Models\Booking;
use Illuminate\Database\Seeder;

class BookingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Booking::create([
            'user_id' => 3,
            'vehicle_id' => 1,
            'workshop_id' => 1,
            'service_id' => 1,
            'complaint' => 'Oli sudah mengental, perlu dilakukan penggantian',
            'damage_photo' => 'damage_1.jpg',
            'booking_date' => now()->addDays(2),
        ]);

        Booking::create([
            'user_id' => 3,
            'vehicle_id' => 2,
            'workshop_id' => 1,
            'service_id' => 3,
            'complaint' => 'Rem terasa kurang ngos, kampas sudah tipis',
            'damage_photo' => 'damage_2.jpg',
            'booking_date' => now()->addDays(3),
        ]);

        Booking::create([
            'user_id' => 4,
            'vehicle_id' => 3,
            'workshop_id' => 2,
            'service_id' => 4,
            'complaint' => 'Performa mesin kurang, perlu tune-up',
            'damage_photo' => 'damage_3.jpg',
            'booking_date' => now()->addDays(5),
        ]);

        Booking::create([
            'user_id' => 5,
            'vehicle_id' => 4,
            'workshop_id' => 3,
            'service_id' => 5,
            'complaint' => 'Ban sudah gundul, perlu diganti baru',
            'damage_photo' => 'damage_4.jpg',
            'booking_date' => now()->addDays(4),
        ]);
    }
}
