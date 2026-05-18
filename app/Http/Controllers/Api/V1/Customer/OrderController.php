<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Emergency;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::whereHas('orderDetails', function ($q) {
            $q->whereHasMorph('source', [Booking::class, Emergency::class], function ($q) {
                $q->where('user_id', Auth::id());
            });
        })
        ->with([
            'orderDetails.source.workshop',
            'orderDetails.source.vehicle',
            'orderServices.service',
        ])
        ->orderBy('created_at', 'desc')
        ->get()
        ->map(function ($order) {
            $detail = $order->orderDetails->first();
            $source = $detail?->source;
            $order->setRelation('workshop', $source?->workshop);
            $order->setRelation('vehicle', $source?->vehicle);
            $order->setRelation('services', $order->orderServices);
            unset($order->orderDetails, $order->orderServices);
            return $order;
        });

        return response()->json([
            'success' => true,
            'message' => 'Riwayat pesanan berhasil diambil',
            'data' => $orders
        ], 200);
    }

    public function show($id)
    {
        $order = Order::where('id', $id)
            ->whereHas('orderDetails', function ($q) {
                $q->whereHasMorph('source', [Booking::class, Emergency::class], function ($q) {
                    $q->where('user_id', Auth::id());
                });
            })
            ->with([
                'orderDetails.source.workshop',
                'orderDetails.source.vehicle',
                'orderServices.service',
            ])
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Pesanan tidak ditemukan atau Anda tidak memiliki akses'
            ], 404);
        }

        $detail = $order->orderDetails->first();
        $source = $detail?->source;
        $order->setRelation('workshop', $source?->workshop);
        $order->setRelation('vehicle', $source?->vehicle);
        $order->setRelation('services', $order->orderServices);
        unset($order->orderDetails, $order->orderServices);

        return response()->json([
            'success' => true,
            'message' => 'Detail pesanan berhasil diambil',
            'data' => $order
        ], 200);
    }
}