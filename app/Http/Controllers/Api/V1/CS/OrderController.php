<?php

namespace App\Http\Controllers\Api\V1\CS;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\NOrderService;
use App\Models\Vehicle;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::with([
                'orderDetails.source.workshop',
                'orderDetails.source.vehicle',
                'orderDetails.source.user',
                'orderServices.service',
            ])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($order) {
                $detail = $order->orderDetails->first();
                $source = $detail?->source;
                $order->setRelation('workshop', $source?->workshop);
                $order->setRelation('vehicle', $source?->vehicle);
                $order->setRelation('user', $source?->user);
                $order->setRelation('services', $order->orderServices);
                unset($order->orderDetails, $order->orderServices);
                return $order;
            });

        return response()->json([
            'success' => true,
            'message' => 'Riwayat pesanan berhasil diambil',
            'data'    => $orders
        ], 200);
    }

    public function show($id)
    {
        $order = Order::where('id', $id)
            ->with([
                'orderDetails.source.workshop',
                'orderDetails.source.vehicle',
                'orderDetails.source.user',
                'orderServices.service',
            ])
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Pesanan tidak ditemukan'
            ], 404);
        }

        $detail = $order->orderDetails->first();
        $source = $detail?->source;
        $order->setRelation('workshop', $source?->workshop);
        $order->setRelation('vehicle', $source?->vehicle);
        $order->setRelation('user', $source?->user);
        $order->setRelation('services', $order->orderServices);
        unset($order->orderDetails, $order->orderServices);

        return response()->json([
            'success' => true,
            'message' => 'Detail pesanan berhasil diambil',
            'data'    => $order
        ], 200);
    }

    public function findVehicle(Request $request)
    {
        $request->validate([
            'plate_number' => 'required|string'
        ]);

        $vehicle = Vehicle::with('user')
            ->where('plate_number', $request->plate_number)
            ->first();

        if (!$vehicle) {
            return response()->json([
                'success' => false,
                'message' => 'Kendaraan tidak ditemukan',
                'data'    => null
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Kendaraan ditemukan',
            'data'    => $vehicle
        ], 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id'            => 'required|exists:users,id',
            'vehicle_id'         => 'required|exists:vehicles,id',
            'workshop_id'        => 'required|exists:workshops,id',
            'service_id'         => 'required|exists:services,id',
            'complaint'          => 'required|string',
            'damage_photo'       => 'nullable|string',
            'additional_service' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $lastOrder = Order::whereNotNull('scheduled_at')
                ->orderBy('scheduled_at', 'desc')
                ->first();

            $scheduledAt = $lastOrder && $lastOrder->scheduled_at
                ? \Carbon\Carbon::parse($lastOrder->scheduled_at)->addMinutes(45)
                : now();

            $service = Service::findOrFail($request->service_id);

            $booking = Booking::create([
                'user_id'      => $request->user_id,
                'vehicle_id'   => $request->vehicle_id,
                'workshop_id'  => $request->workshop_id,
                'service_id'   => $request->service_id,
                'complaint'    => $request->complaint,
                'damage_photo' => $request->damage_photo,
                'booking_date' => $scheduledAt,
            ]);

            $order = Order::create([
                'mechanic_id'    => null,
                'voucher_id'     => null,
                'status'         => 'process',
                'payment_status' => 'pending',
                'total_price'    => $service->base_price,
                'scheduled_at'   => $scheduledAt,
            ]);

            OrderDetail::create([
                'order_id'     => $order->id,
                'service_type' => 'booking',
                'reference_id' => $booking->id,
                'price'        => $service->base_price,
            ]);

            NOrderService::create([
                'order_id'           => $order->id,
                'service_id'         => $request->service_id,
                'additional_service' => $request->additional_service,
                'price'              => $service->base_price,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order berhasil dibuat',
                'data'    => $order->load([
                    'orderDetails.source.workshop',
                    'orderDetails.source.vehicle',
                    'orderDetails.source.user',
                    'orderServices.service',
                ])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat order',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}