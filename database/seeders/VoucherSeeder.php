<?php

namespace Database\Seeders;

use App\Models\Voucher;
use Illuminate\Database\Seeder;

class VoucherSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Voucher::create([
            'code' => 'MOTOCARE10',
            'discount_value' => 10,
            'discount_type' => 'percentage',
            'min_spend' => 100000,
            'expiry_date' => now()->addMonths(3),
            'usage_limit' => 100,
        ]);

        Voucher::create([
            'code' => 'MOTOCARE50K',
            'discount_value' => 50000,
            'discount_type' => 'fixed',
            'min_spend' => 200000,
            'expiry_date' => now()->addMonths(2),
            'usage_limit' => 50,
        ]);

        Voucher::create([
            'code' => 'MEMBER20',
            'discount_value' => 20,
            'discount_type' => 'percentage',
            'min_spend' => 150000,
            'expiry_date' => now()->addMonths(6),
            'usage_limit' => 200,
        ]);

        Voucher::create([
            'code' => 'GRATIS100K',
            'discount_value' => 100000,
            'discount_type' => 'fixed',
            'min_spend' => 500000,
            'expiry_date' => now()->addMonth(),
            'usage_limit' => 10,
        ]);
    }
}
