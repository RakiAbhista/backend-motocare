<?php

namespace App\Http\Controllers\Api\V1\Mechanic;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Mechanic;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Services\FcmService;

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
                'orderDetails.emergency.user',
                'orderDetails.emergency.vehicle',
            ])
            ->whereDate('created_at', $today)
            ->whereIn('status', ['pending', 'process'])
            ->where(function ($q) {
                // include booking orders
                $q->whereHas('orderDetails', function ($q2) {
                    $q2->where('service_type', 'booking');
                })
                // or include emergency orders only when is_towing = 'yes'
                ->orWhere(function ($q3) {
                    $q3->where('is_towing', 'yes')
                       ->whereHas('orderDetails', function ($q4) {
                           $q4->where('service_type', 'emergency');
                       });
                });
            })
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($order) {
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
                    'order_id'       => $order->id,
                    'status'         => $order->status,
                    'customer_name'  => $customer?->name,
                    'vehicle'        => $vehicle ? [
                        'brand'               => $vehicle->brand,
                        'model'               => $vehicle->model,
                        'manufacturing_year'  => $vehicle->manufacturing_year,
                        'plate_number'        => $vehicle->plate_number,
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
            'status' => 'required|in:available,unavailable',
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

    /**
     * Clear mechanic current location (set latitude and longitude to null)
     */
    public function clearLocation(Request $request)
    {
        $user = $request->user();

        $mechanic = Mechanic::where('user_id', $user->id)->first();
        if (! $mechanic) {
            return response()->json([
                'status' => 'error',
                'message' => 'Mekanik tidak terdaftar',
            ], 404);
        }

        $mechanic->update([
            'latitude' => null,
            'longitude' => null,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Lokasi mekanik dihapus',
            'data' => [
                'mechanic_id' => $mechanic->id,
                'latitude' => $mechanic->latitude,
                'longitude' => $mechanic->longitude,
            ],
        ], 200);
    }

    public function acceptOrder(Request $request, $orderId)
    {
        $request->validate([
            'order_id' => 'nullable|numeric',
        ]);

        
        $fcmService = new FcmService();
            
        $order = Order::with([
            'orderDetails.booking.user',
            'orderDetails.emergency.user',
        ])->findOrFail($orderId);

        $orderDetail = $order->orderDetails->whereIn('service_type', ['booking', 'emergency'])->first();
        $customerUserId = null;

        if ($orderDetail?->service_type === 'emergency') {
            $customerUserId = $orderDetail->emergency?->user_id;
        } elseif ($orderDetail?->service_type === 'booking') {
            $customerUserId = $orderDetail->booking?->user_id;
        }

        if ($customerUserId) {
            $fcmService->sendToUser(
                $customerUserId,
                '✅ Mekanik Meluncur!',
                'Mekanik telah menyetujui orderanmu dan sedang menuju ke lokasimu.'
            );
        }
        
        // Validasi status harus pending
        if ($order->status !== 'pending') {
            return response()->json([
                'status'  => 'error',
                'message' => 'Order harus berstatus pending untuk diterima.',
            ], 400);
        }

        // Dapatkan mechanic dari user yang login dan pastikan tersedia
        $user = $request->user();
        $mechanic = Mechanic::where('user_id', $user->id)->firstOrFail();

        if ($mechanic->status !== 'available') {
            return response()->json([
                'status' => 'error',
                'message' => 'Mekanik tidak tersedia untuk menerima order.',
            ], 422);
        }

        // Set mekanik menjadi unavailable dan update order menjadi process
        $mechanic->update(['status' => 'unavailable']);

        $order->update([
            'status' => 'process',
            'mechanic_id' => $mechanic->id,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Order berhasil diterima.',
            'data'    => [
                'order_id' => $order->id,
                'status'   => $order->status,
                'mechanic_id' => $order->mechanic_id,
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

        // Update status order menjadi payment
        $order->update(['status' => 'payment']);

        // Jika order memiliki mechanic, set status mekanik menjadi available
        if ($order->mechanic_id) {
            $mechanic = Mechanic::find($order->mechanic_id);
            if ($mechanic) {
                $mechanic->update(['status' => 'available']);
            }
        }

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