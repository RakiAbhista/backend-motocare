<?php

namespace App\Http\Controllers\Api\V1\CustomerService;

use App\Http\Controllers\Controller;
use App\Models\Emergency;
use App\Models\Order;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'status' => 'success',
            'data' => [
                'nama' => $user->name,
                'nomor_telepon' => $user->phone_number,
                'avatar' => $user->avatar ? asset('storage/' . $user->avatar) : null,
            ]
        ], 200);
    }

    public function workHistory(Request $request)
    {
        $orders = Order::where('status', 'completed')
            ->with(['orderDetails.booking.user', 'orderDetails.booking.vehicle'])
            ->latest()
            ->get();

        $history = $orders->map(function ($order) {
            $detail = $order->orderDetails->first();
            $booking = $detail?->booking;
            $customer = $booking?->user;

            return [
                'id' => $order->id,
                'customer_name' => $customer?->name,
                'vehicle_brand' => $booking?->vehicle?->brand,
                'vehicle_model' => $booking?->vehicle?->model,
                'plate_number' => $booking?->vehicle?->plate_number,
                'status' => $order->status,
                'completed_at' => $order->updated_at,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $history,
        ], 200);
    }
}
