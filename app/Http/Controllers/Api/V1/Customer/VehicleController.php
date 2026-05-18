<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Vehicle;
use Illuminate\Support\Facades\Auth;

class VehicleController extends Controller
{
    // 1. Tampilkan semua kendaraan milik customer yang login
    public function index()
    {
        $vehicles = Vehicle::where('user_id', Auth::id())->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar kendaraan berhasil diambil',
            'data' => $vehicles
        ], 200);
    }

    // 2. Tambah kendaraan baru
    public function store(Request $request)
    {
        // Validasi input dari Flutter
        $request->validate([
            'vehicle_type' => 'required|string',
            'brand' => 'required|string',
            'model' => 'required|string',
            'plate_number' => 'required|string|unique:vehicles,plate_number',
            'manufacturing_year' => 'required|integer',
        ]);

        // Simpan ke database
        $vehicle = Vehicle::create([
            'user_id' => Auth::id(),
            'vehicle_type' => $request->vehicle_type,
            'brand' => $request->brand,
            'model' => $request->model,
            'plate_number' => $request->plate_number,
            'manufacturing_year' => $request->manufacturing_year,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Kendaraan berhasil ditambahkan',
            'data' => $vehicle
        ], 201);
    }

    // 3. Hapus kendaraan
    public function destroy($id)
    {
        $vehicle = Vehicle::where('id', $id)->where('user_id', Auth::id())->first();

        if (!$vehicle) {
            return response()->json([
                'success' => false,
                'message' => 'Kendaraan tidak ditemukan atau bukan milik Anda'
            ], 404);
        }

        $vehicle->delete();

        return response()->json([
            'success' => true,
            'message' => 'Kendaraan berhasil dihapus'
        ], 200);
    }
}