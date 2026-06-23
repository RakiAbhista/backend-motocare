<?php

namespace App\Http\Controllers\Api\V1\CS;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // User CS yang sedang login
        $user = $request->user();

        // Statistik (hari ini)
        $today = Carbon::today();

        $totalOrders = Order::whereDate('created_at', $today)->count();

        $completedRepairs = Order::where('status', 'completed')
            ->whereDate('created_at', $today)
            ->count();

        // Latest Orders (only pending/process/payment) and must have booking-type order detail
        $orders = Order::with([
            'orderDetails.booking.user',
            'orderDetails.booking.vehicle',
            'orderDetails.emergency.user',
        ])
        ->whereIn('status', ['pending', 'process', 'payment'])
        ->where(function ($q) {
            // Include booking orders
            $q->whereHas('orderDetails', function ($q2) {
                $q2->where('service_type', 'booking');
            })
            // Or include emergency orders only when is_towing is true
            ->orWhere(function ($q3) {
                $q3->where('is_towing', 'yes')
                   ->whereHas('orderDetails', function ($q4) {
                       $q4->where('service_type', 'emergency');
                   });
            });
        })
        ->latest()
        ->get();

        // Format data order untuk Flutter
        $latestOrders = $orders->map(function ($order) {

            $detail = $order->orderDetails->whereIn('service_type', ['booking', 'emergency'])->first();

            $customer = null;
            $vehicle = null;

            if ($detail?->service_type === 'booking') {
                $booking = $detail->booking;
                $customer = $booking?->user;
                $vehicle = $booking?->vehicle;
            } elseif ($detail?->service_type === 'emergency') {
                $emergency = $detail->emergency;
                $customer = $emergency?->user;
                $vehicle = $emergency?->vehicle ?? null;
            }

            return [
                'id' => $order->id,

                'customer_name' => $customer?->name,

                'vehicle_brand' => $vehicle?->brand,

                'vehicle_model' => $vehicle?->model,

                'plate_number' => $vehicle?->plate_number,

                'status' => $order->status,

                'created_at' => $order->created_at,
            ];
        });

        return response()->json([
            'status' => 'success',

            'data' => [

                // Nama CS Login
                'cs_name' => $user->name,

                // Statistik
                'statistics' => [
                    'total_orders' => $totalOrders,
                    'completed_repairs' => $completedRepairs,
                ],

                // Latest Orders
                'latest_orders' => $latestOrders,
            ]
        ], 200);
    }
}