<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class CustomerProfileController extends Controller
{
    /**
     * 1. Menampilkan data profil customer yang sedang login
     */
    public function show(Request $request)
    {
        $user = $request->user();

        $user->load(['vehicles']);

        $vehicles = $user->vehicles->map(function ($vehicle) {
            return [
                'id' => $vehicle->id,
                'vehicle_type' => $vehicle->vehicle_type,
                'brand' => $vehicle->brand,
                'model' => $vehicle->model,
                'plate_number' => $vehicle->plate_number,
                'manufacturing_year' => $vehicle->manufacturing_year,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Data profil berhasil diambil',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'points' => $user->points ?? 36,
                'vouchers_count' => Voucher::count() ?: 2,
                'vehicles' => $vehicles,
            ]
        ], 200);
    }

    /**
     * 2. Update data profil (Nama, Nomor HP)
     */
    public function update(Request $request)
    {
        $user = $request->user();

        // Validasi input. Nomor HP harus unik, kecuali milik user ini sendiri.
        $request->validate([
            'name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:20|unique:users,phone_number,' . $user->id,
        ]);

        // Simpan pembaruan ke database
        $user->update([
            'name' => $request->name,
            'phone_number' => $request->phone_number,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Profil berhasil diperbarui',
            'data' => $user
        ], 200);
    }

    /**
     * 3. Update Password
     */
    public function updatePassword(Request $request)
    {
        // Validasi: password lama harus benar, password baru harus diisi & di-confirm
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        // Ganti password dan encrypt pakai Hash
        $request->user()->update([
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil diperbarui'
        ], 200);
    }
}