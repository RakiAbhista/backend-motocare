<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Booking;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ServiceHistoryController extends Controller
{
    /**
     * Riwayat service untuk 1 kendaraan yang dipilih
     */
    public function show(Request $request, $vehicleId)
    {
        $userId = Auth::id();

        // Pastikan kendaraan milik user yang login
        $vehicle = Vehicle::where('id', $vehicleId)
            ->where('user_id', $userId)
            ->select('id', 'brand', 'model', 'plate_number', 'vehicle_type', 'manufacturing_year')
            ->first();

        if (!$vehicle) {
            return response()->json([
                'success' => false,
                'message' => 'Kendaraan tidak ditemukan atau bukan milik Anda'
            ], 404);
        }

        // Ambil semua order completed milik kendaraan ini
        // Alur: Order → OrderDetail (service_type=booking) → Booking (vehicle_id)
        $orders = Order::where('status', 'completed')
            ->whereHas('orderDetails', function ($query) use ($vehicleId) {
                $query->where('service_type', 'booking')
                    ->whereHasMorph('source', [Booking::class], function ($q) use ($vehicleId) {
                        $q->where('vehicle_id', $vehicleId);
                    });
            })
            ->with([
                'orderDetails' => function ($query) {
                    $query->where('service_type', 'booking');
                },
                'orderDetails.source.workshop:id,name',
                'orderServices.service:id,service_name,base_price',
            ])
            ->latest()
            ->get();

        // Format riwayat service
        $serviceHistory = $orders->map(function ($order) {
            $booking = $order->orderDetails->first()?->source;

            return [
                'order_id'     => $order->id,
                'booking_date' => $booking?->booking_date,
                'total_price'  => $order->total_price,
                'workshop'     => $booking?->workshop ? [
                    'id'      => $booking->workshop->id,
                    'name'    => $booking->workshop->name,
                ] : null,
                'services' => $order->orderServices->map(fn($os) => [
                    'id'           => $os->service->id,
                    'service_name' => $os->service->service_name,
                    'base_price'   => $os->service->base_price,
                ]),
            ];
        });

        // Waktu terakhir service
        $lastService = $serviceHistory->first(); // sudah di-sort latest()

        return response()->json([
            'success' => true,
            'data'    => [
                'vehicle'          => $vehicle,
                'last_service_date' => $lastService['booking_date'] ?? null,
                'total_services'   => $serviceHistory->count(),
                'service_history'  => $serviceHistory,
            ]
        ], 200);
    }

    public function vehicles()
    {
        $userId = Auth::id();

        $vehicles = Vehicle::where('user_id', $userId)
            ->select('id', 'brand', 'model', 'plate_number', 'vehicle_type', 'manufacturing_year')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $vehicles
        ], 200);
    }

    public function vehicleDetail($vehicleId)
    {
        $userId = Auth::id();

        $vehicle = Vehicle::where('id', $vehicleId)
            ->where('user_id', $userId)
            ->select('id', 'brand', 'model', 'plate_number', 'vehicle_type', 'manufacturing_year', 'registration_doc')
            ->first();

        if (!$vehicle) {
            return response()->json([
                'success' => false,
                'message' => 'Kendaraan tidak ditemukan atau bukan milik Anda'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $vehicle
        ], 200);
    }
}