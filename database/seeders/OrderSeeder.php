<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\NOrderService; // Pastikan model ini di-import
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ==========================================
        // ORDER 1
        // ==========================================
        $order1 = Order::create([
            'mechanic_id' => 1,
            'voucher_id' => 1,
            'total_price' => 97500,
            'status' => 'completed',
            'payment_status' => 'settlement',
            'payment_type' => 'credit_card',
            'transaction_id' => 'TRX001',
            'payment_url' => 'https://payment.gateway.com/TRX001',
            'scheduled_at' => now()->subDays(1),
        ]);

        // Detail Service untuk Order 1
        NOrderService::create([
            'order_id' => $order1->id,
            'service_id' => 1, // Pastikan Service dengan ID 1 ada di database
            'additional_service' => null,
            'price' => 97500, // Harga disamakan dengan total_price order
        ]);


        // ==========================================
        // ORDER 2
        // ==========================================
        $order2 = Order::create([
            'mechanic_id' => 2,
            'voucher_id' => 2,
            'total_price' => 300000,
            'status' => 'payment',
            'payment_status' => 'pending',
            'payment_type' => 'transfer_bank',
            'transaction_id' => 'TRX002',
            'payment_url' => 'https://payment.gateway.com/TRX002',
            'scheduled_at' => now()->addDays(1),
        ]);

        // Detail Service untuk Order 2 (Contoh ada 2 service)
        NOrderService::create([
            'order_id' => $order2->id,
            'service_id' => 2,
            'additional_service' => 'Service Lengkap',
            'price' => 200000, 
        ]);
        NOrderService::create([
            'order_id' => $order2->id,
            'service_id' => 3,
            'additional_service' => 'Ganti Aki',
            'price' => 100000, // Jika dijumlahkan 200k + 100k = 300k (Sesuai total_price)
        ]);


        // ==========================================
        // ORDER 3
        // ==========================================
        $order3 = Order::create([
            'mechanic_id' => 1,
            'voucher_id' => null,
            'total_price' => 200000,
            'status' => 'process',
            'payment_status' => 'pending',
            'payment_type' => 'cash',
            'transaction_id' => 'TRX003',
            'payment_url' => 'https://payment.gateway.com/TRX003',
            'scheduled_at' => now()->addDays(2),
        ]);

        // Detail Service untuk Order 3
        NOrderService::create([
            'order_id' => $order3->id,
            'service_id' => 1,
            'additional_service' => 'Ganti Ban',
            'price' => 200000,
        ]);


        // ==========================================
        // ORDER 4
        // ==========================================
        $order4 = Order::create([
            'mechanic_id' => 2,
            'voucher_id' => 3,
            'total_price' => 240000,
            'status' => 'completed',
            'payment_status' => 'settlement',
            'payment_type' => 'e_wallet',
            'transaction_id' => 'TRX004',
            'payment_url' => 'https://payment.gateway.com/TRX004',
            'scheduled_at' => now()->subDays(2),
        ]);

        // Detail Service untuk Order 4
        NOrderService::create([
            'order_id' => $order4->id,
            'service_id' => 2,
            'additional_service' => null,
            'price' => 240000,
        ]);
    }
}