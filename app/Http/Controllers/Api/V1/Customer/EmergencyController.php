<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Models\Emergency;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Vehicle;
use App\Models\Workshop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EmergencyController extends Controller
{
    /**
     * Get user's vehicles for dropdown selection.
     */
    public function getVehicles()
    {
        $vehicles = Vehicle::where('user_id', Auth::id())
            ->select('id', 'brand', 'model', 'manufacturing_year', 'plate_number', 'vehicle_type')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $vehicles
        ], 200);
    }

    /**
     * Get nearest workshop from user's current coordinates.
     */
    public function getNearestWorkshop(Request $request)
    {
        $request->validate([
            'latitude'  => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $userLat = $request->latitude;
        $userLng = $request->longitude;

        $nearestWorkshop = $this->findNearestWorkshop($userLat, $userLng);

        if (!$nearestWorkshop) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada bengkel yang tersedia saat ini.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id'              => $nearestWorkshop->id,
                'name'            => $nearestWorkshop->name,
                'latitude'        => $nearestWorkshop->latitude,
                'longitude'       => $nearestWorkshop->longitude,
                'distance_meters' => round($nearestWorkshop->distance),
            ]
        ], 200);
    }

    /**
     * Create a new emergency request and order.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'latitude'       => 'required|numeric',
            'longitude'      => 'required|numeric',
            'vehicle_id'     => 'nullable|exists:vehicles,id',
            // Fallback fields required if vehicle_id is null
            'vehicle_brand'  => 'required_without:vehicle_id|string|max:255',
            'vehicle_type'   => 'required_without:vehicle_id|string|max:255',
            'vehicle_model'  => 'required_without:vehicle_id|string|max:255',
            'plate_number'   => 'required_without:vehicle_id|string|max:255',
            'complaint'      => 'nullable|string|max:255',
            'damage_photo'   => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120', // Max 5MB
            'emergency_type' => 'required|in:mechanic,towing',
        ]);

        $userLat = $validated['latitude'];
        $userLng = $validated['longitude'];
        $userId = Auth::id();

        // 1. Find the nearest workshop
        $nearestWorkshop = $this->findNearestWorkshop($userLat, $userLng);

        if (!$nearestWorkshop) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada bengkel yang tersedia saat ini.'
            ], 404);
        }

        $workshopId = $nearestWorkshop->id;

        // 2. Resolve Vehicle details
        $vehicleId = $request->input('vehicle_id');
        $vehicleModel = null;
        $vehicleBrand = null;
        $vehicleType = null;
        $plateNumber = null;

        if ($vehicleId) {
            // Verify vehicle belongs to user
            $vehicle = Vehicle::where('id', $vehicleId)
                ->where('user_id', $userId)
                ->first();

            if (!$vehicle) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kendaraan tidak ditemukan atau Anda tidak memiliki akses ke kendaraan ini.'
                ], 404);
            }

            $vehicleModel = $vehicle->model;
            $vehicleBrand = $vehicle->brand;
            $vehicleType = $vehicle->vehicle_type;
            $plateNumber = $vehicle->plate_number;
        } else {
            $vehicleModel = $request->input('vehicle_model');
            $vehicleBrand = $request->input('vehicle_brand');
            $vehicleType = $request->input('vehicle_type');
            $plateNumber = $request->input('plate_number');
        }

        try {
            return DB::transaction(function () use ($validated, $request, $userId, $vehicleId, $vehicleBrand, $vehicleModel, $vehicleType, $plateNumber, $workshopId, $nearestWorkshop) {
                // 3. Handle damage photo upload
                $photoPath = null;
                if ($request->hasFile('damage_photo')) {
                    $photoPath = $request->file('damage_photo')->store('damage_photos', 'public');
                }

                // 4. Create Emergency request
                $emergency = Emergency::create([
                    'user_id'       => $userId,
                    'mechanic_id'   => null,
                    'vehicle_id'    => $vehicleId,
                    'vehicle_model' => $vehicleModel,
                    'vehicle_brand' => $vehicleBrand,
                    'vehicle_type'  => $vehicleType,
                    'plate_number'  => $plateNumber,
                    'workshop_id'   => $workshopId,
                    'latitude'      => $validated['latitude'],
                    'longitude'     => $validated['longitude'],
                    'damage_photo'  => $photoPath,
                    'complaint'     => $request->input('complaint'),
                    'status'        => 'pending',
                    'requested_at'  => now(),
                ]);

                // 5. Create Order
                // For towing: is_towing = 'yes'
                // For mechanic: is_towing = 'no'
                $isTowing = $validated['emergency_type'] === 'towing';

                $order = Order::create([
                    'mechanic_id'    => null,
                    'voucher_id'     => null,
                    'total_price'    => 0, // initially 0 or null
                    'is_towing'      => $isTowing ? 'yes' : 'no',
                    'status'         => 'pending',
                    'payment_status' => 'pending',
                    'payment_url'    => null,
                    'scheduled_at'   => null,
                ]);

                // 6. Create OrderDetail (Polymorphic relationship to Emergency)
                OrderDetail::create([
                    'order_id'     => $order->id,
                    'service_type' => 'emergency',
                    'reference_id' => $emergency->id,
                    'price'        => 0,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => $isTowing 
                        ? 'Permintaan emergency towing berhasil dibuat. Kendaraan Anda akan dibawa ke bengkel terdekat.' 
                        : 'Permintaan emergency panggil mekanik berhasil dibuat. Mekanik akan segera menuju lokasi Anda.',
                    'data' => [
                        'order_id'       => $order->id,
                        'emergency_id'   => $emergency->id,
                        'emergency_type' => $validated['emergency_type'],
                        'status'         => $order->status,
                        'is_towing'      => $order->is_towing,
                        'vehicle' => [
                            'brand'        => $vehicleBrand,
                            'model'        => $vehicleModel,
                            'plate_number' => $plateNumber,
                            'vehicle_type' => $vehicleType,
                        ],
                        'workshop' => [
                            'id'   => $nearestWorkshop->id,
                            'name' => $nearestWorkshop->name,
                            'distance_meters' => round($nearestWorkshop->distance),
                        ],
                        'complaint'    => $emergency->complaint,
                        'damage_photo' => $photoPath ? url('storage/' . $photoPath) : null,
                        'latitude'     => $emergency->latitude,
                        'longitude'    => $emergency->longitude,
                        'requested_at' => $emergency->requested_at,
                    ]
                ], 201);
            });
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat permintaan emergency: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper function to find nearest workshop.
     */
    private function findNearestWorkshop($userLat, $userLng)
    {
        $workshops = Workshop::all();

        if ($workshops->isEmpty()) {
            return null;
        }

        return $workshops->map(function ($workshop) use ($userLat, $userLng) {
            $workshop->distance = $this->haversine($userLat, $userLng, $workshop->latitude, $workshop->longitude);
            return $workshop;
        })->sortBy('distance')->first();
    }

    /**
     * Haversine formula to calculate distance in meters.
     */
    private function haversine($lat1, $lng1, $lat2, $lng2): float
    {
        $earthRadius = 6371000; // in meters

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2)
           + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
           * sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
