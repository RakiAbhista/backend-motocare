<?php

namespace Database\Seeders;

use App\Models\OrderDetail;
use Illuminate\Database\Seeder;

class OrderDetailSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        OrderDetail::create([
            'order_id' => 1,
            'service_type' => 'booking',
            'reference_id' => 1,
            'price' => 75000,
        ]);

        OrderDetail::create([
            'order_id' => 2,
            'service_type' => 'booking',
            'reference_id' => 2,
            'price' => 150000,
        ]);

        OrderDetail::create([
            'order_id' => 3,
            'service_type' => 'booking',
            'reference_id' => 3,
            'price' => 200000,
        ]);

        OrderDetail::create([
            'order_id' => 4,
            'service_type' => 'emergency',
            'reference_id' => 1,
            'price' => 300000,
        ]);
    }
}
