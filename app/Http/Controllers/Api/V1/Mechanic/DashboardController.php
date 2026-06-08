<?php

namespace App\Http\Controllers\Api\V1\Mechanic;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Mechanic;
use App\Models\OrderDetail;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $mechanic = Mechanic::with('user')->firstOrCreate(
            ['user_id' => $user->id],
            ['status' => 'available']
        );

        $today = Carbon::today();

        // 1. Total orders by status (hari ini)
        $totalProcess = Order::whereDate('created_at', $today)
            ->where('status', 'process')
            ->count();

        $totalPending = Order::whereDate('created_at', $today)
            ->where('status', 'pending')
            ->count();

        $totalCompleted = Order::whereDate('created_at', $today)
            ->where('status', 'completed')
            ->count();

        // 2. Incoming queue — semua order pending/process hari ini
        $incomingQueue = Order::with([
                'orderDetails.booking.vehicle',
                'orderDetails.booking.user',
            ])
            ->whereDate('created_at', $today)
            ->whereIn('status', ['pending', 'process'])
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($order) {
                $detail = $order->orderDetails->first();
                $booking = $detail?->booking;
                $vehicle = $booking?->vehicle;
                $customer = $booking?->user;

                return [
                    'order_id'       => $order->id,
                    'status'         => $order->status,
                    'customer_name'  => $customer?->name,
                    'vehicle'        => $vehicle ? [
                        'brand'            => $vehicle->brand,
                        'model'            => $vehicle->model,
                        'manufacturing_year' => $vehicle->manufacturing_year,
                        'plate_number'     => $vehicle->plate_number,
                    ] : null,
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => [
                'mechanic' => [
                    'id'     => $mechanic->id,
                    'status' => $mechanic->status,
                    'user'   => [
                        'id'           => $user->id,
                        'name'         => $user->name,
                        'email'        => $user->email,
                        'phone_number' => $user->phone_number,
                    ],
                ],
                'stats' => [
                    'total_process'   => $totalProcess,
                    'total_pending'   => $totalPending,
                    'total_completed' => $totalCompleted,
                ],
                'incoming_queue' => $incomingQueue,
            ]
        ], 200);
    }

    public function show(Request $request, $orderId)
    {
        $order = Order::with([
                'orderDetails.booking.vehicle',
                'orderDetails.booking.user',
                'nOrderServices.service',
            ])
            ->findOrFail($orderId);

        $detail  = $order->orderDetails->first();
        $booking = $detail?->booking;
        $vehicle = $booking?->vehicle;
        $customer = $booking?->user;

        return response()->json([
            'status' => 'success',
            'data' => [
                'order_id'       => $order->id,
                'status'         => $order->status,
                'payment_status' => $order->payment_status,
                'total_price'    => $order->total_price,
                'is_towing'      => $order->is_towing,
                'customer' => [
                    'name'         => $customer?->name,
                    'phone_number' => $customer?->phone_number,
                    'email'        => $customer?->email,
                ],
                'vehicle' => $vehicle ? [
                    'brand'              => $vehicle->brand,
                    'model'              => $vehicle->model,
                    'manufacturing_year' => $vehicle->manufacturing_year,
                    'plate_number'       => $vehicle->plate_number,
                    'vehicle_type'       => $vehicle->vehicle_type,
                ] : null,
                'complaint'    => $booking?->complaint,
                'damage_photo' => $booking?->damage_photo
                    ? url('storage/' . $booking->damage_photo)
                    : null,
                'services' => $order->nOrderServices->map(fn($s) => [
                    'service_name' => $s->service?->service_name,
                    'price'        => $s->price,
                ]),
            ]
        ], 200);
    }

    public function updateStatus(Request $request)
    {
        $request->validate([
            'status' => 'required|in:available,busy',
        ]);

        $mechanic = Mechanic::firstOrCreate(
            ['user_id' => $request->user()->id],
            ['status' => $request->status]
        );

        $mechanic->update(['status' => $request->status]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Status berhasil diperbarui.',
            'data'    => ['status' => $mechanic->status],
        ], 200);
    }

    public function acceptOrder(Request $request, $orderId)
    {
        $request->validate([
            'order_id' => 'nullable|numeric',
        ]);

        $order = Order::findOrFail($orderId);

        // Validasi status harus pending
        if ($order->status !== 'pending') {
            return response()->json([
                'status'  => 'error',
                'message' => 'Order harus berstatus pending untuk diterima.',
            ], 400);
        }

        // Update status menjadi process
        $order->update(['status' => 'process']);

        return response()->json([
            'status'  => 'success',
            'message' => 'Order berhasil diterima.',
            'data'    => [
                'order_id' => $order->id,
                'status'   => $order->status,
            ],
        ], 200);
    }

    public function completeOrder(Request $request, $orderId)
    {
        $request->validate([
            'order_id' => 'nullable|numeric',
        ]);

        $order = Order::findOrFail($orderId);

        // Validasi status harus process
        if ($order->status !== 'process') {
            return response()->json([
                'status'  => 'error',
                'message' => 'Order harus berstatus process untuk diselesaikan.',
            ], 400);
        }

        // Update status menjadi payment
        $order->update(['status' => 'payment']);

        return response()->json([
            'status'  => 'success',
            'message' => 'Order berhasil diselesaikan.',
            'data'    => [
                'order_id' => $order->id,
                'status'   => $order->status,
            ],
        ], 200);
    }
}