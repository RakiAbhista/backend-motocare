<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    /**
     * Display a listing of vehicles
     */
    public function index(Request $request)
    {
        $query = Vehicle::with('user');

        // Search berdasarkan brand, model, atau plate_number
        if ($request->has('search')) {
            $search = $request->search;
            $query->where('brand', 'like', "%$search%")
                  ->orWhere('model', 'like', "%$search%")
                  ->orWhere('plate_number', 'like', "%$search%");
        }

        // Filter berdasarkan vehicle_type
        if ($request->has('vehicle_type')) {
            $query->where('vehicle_type', $request->vehicle_type);
        }

        $vehicles = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'status' => 'success',
            'data' => $vehicles
        ], 200);
    }

    /**
     * Display the specified vehicle
     */
    public function show(Vehicle $vehicle)
    {
        $vehicle->load('user');

        return response()->json([
            'status' => 'success',
            'data' => $vehicle
        ], 200);
    }

    /**
     * Remove the specified vehicle from database
     */
    public function destroy(Vehicle $vehicle)
    {
        $vehicle->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Kendaraan berhasil dihapus.'
        ], 200);
    }
}
