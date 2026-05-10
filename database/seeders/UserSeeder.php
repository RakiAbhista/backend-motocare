<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        User::create([
            'role' => 'admin',
            'name' => 'Admin User',
            'phone_number' => '081234567890',
            'email' => 'admin@motocare.com',
            'password' => Hash::make('password123'),
            'points' => 0,
            'email_verified_at' => now(),
        ]);

        // Create customer service user
        User::create([
            'role' => 'customer_service',
            'name' => 'Customer Service',
            'phone_number' => '081234567891',
            'email' => 'cs@motocare.com',
            'password' => Hash::make('password123'),
            'points' => 0,
            'email_verified_at' => now(),
        ]);

        // Create customer users
        User::create([
            'role' => 'customer',
            'name' => 'Budi Santoso',
            'phone_number' => '085701234567',
            'email' => 'budi@example.com',
            'password' => Hash::make('password123'),
            'points' => 150,
            'email_verified_at' => now(),
        ]);

        User::create([
            'role' => 'customer',
            'name' => 'Siti Nurhaliza',
            'phone_number' => '085702345678',
            'email' => 'siti@example.com',
            'password' => Hash::make('password123'),
            'points' => 200,
            'email_verified_at' => now(),
        ]);

        User::create([
            'role' => 'customer',
            'name' => 'Riza Firmansyah',
            'phone_number' => '085703456789',
            'email' => 'riza@example.com',
            'password' => Hash::make('password123'),
            'points' => 100,
            'email_verified_at' => now(),
        ]);

        // Create mechanic users
        User::create([
            'role' => 'mechanic',
            'name' => 'Bambang Mekanik',
            'phone_number' => '085704567890',
            'email' => 'bambang@motocare.com',
            'password' => Hash::make('password123'),
            'points' => 0,
            'email_verified_at' => now(),
        ]);

        User::create([
            'role' => 'mechanic',
            'name' => 'Joko Teknisi',
            'phone_number' => '085705678901',
            'email' => 'joko@motocare.com',
            'password' => Hash::make('password123'),
            'points' => 0,
            'email_verified_at' => now(),
        ]);
    }
}
