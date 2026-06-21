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
            'orderDetails.booking.vehicle'
        ])
        ->whereIn('status', ['pending', 'process', 'payment'])
        ->whereHas('orderDetails', function ($q) {
            $q->where('service_type', 'booking');
        })
        ->latest()
        ->get();

        // Format data order untuk Flutter
        $latestOrders = $orders->map(function ($order) {

            $detail = $order->orderDetails->where('service_type', 'booking')->first();

            $booking = $detail?->booking;

            $customer = $booking?->user;

            $vehicle = $booking?->vehicle;

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