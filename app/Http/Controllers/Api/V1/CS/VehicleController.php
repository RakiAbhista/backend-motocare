<?php

namespace App\Http\Controllers\Api\V1\CS;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Vehicle;

class VehicleController extends Controller
{
    // 1. Tampilkan semua kendaraan (bisa filter by user_id, plate_number, dll)
    public function index(Request $request)
    {
        $query = Vehicle::query();

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('plate_number')) {
            $query->where('plate_number', 'like', '%' . $request->plate_number . '%');
        }

        if ($request->filled('vehicle_type')) {
            $query->where('vehicle_type', $request->vehicle_type);
        }

        if ($request->filled('brand')) {
            $query->where('brand', 'like', '%' . $request->brand . '%');
        }

        $vehicles = $query->latest()->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar kendaraan berhasil diambil',
            'data' => $vehicles
        ], 200);
    }
    // 2. Lihat detail satu kendaraan by ID
    public function show($id)
    {
        $vehicle = Vehicle::with('user')->find($id);

        if (!$vehicle) {
            return response()->json([
                'success' => false,
                'message' => 'Kendaraan tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail kendaraan berhasil diambil',
            'data' => $vehicle
        ], 200);
    }

    // 3. Update kendaraan milik customer (misal koreksi data)
    public function update(Request $request, $id)
    {
        $vehicle = Vehicle::find($id);

        if (!$vehicle) {
            return response()->json([
                'success' => false,
                'message' => 'Kendaraan tidak ditemukan'
            ], 404);
        }

        $request->validate([
            'vehicle_type'       => 'sometimes|string',
            'brand'              => 'sometimes|string',
            'model'              => 'sometimes|string',
            'plate_number'       => 'sometimes|string|unique:vehicles,plate_number,' . $vehicle->id,
            'manufacturing_year' => 'sometimes|integer',
        ]);

        $vehicle->update($request->only([
            'vehicle_type',
            'brand',
            'model',
            'plate_number',
            'manufacturing_year'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Kendaraan berhasil diperbarui oleh customer service',
            'data' => $vehicle
        ], 200);
    }

    // Cari kendaraan by plat nomor
    public function findByPlate(Request $request)
    {
        $request->validate([
            'plate_number' => 'required|string'
        ]);

        $vehicle = Vehicle::with('user')
            ->where('plate_number', strtoupper($request->plate_number))
            ->first();

        if (!$vehicle) {
            return response()->json([
                'success' => false,
                'message' => 'Kendaraan dengan plat nomor tersebut tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Kendaraan ditemukan',
            'data' => $vehicle
        ], 200);
    }

}