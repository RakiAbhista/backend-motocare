<?php

namespace App\Http\Controllers\Api\V1\Mechanic;

use App\Http\Controllers\Controller;
use App\Models\Emergency;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\NOrderService;
use App\Models\Mechanic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmergencyController extends Controller
{
    /**
     * List all emergencies assigned to this mechanic.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $mechanic = Mechanic::where('user_id', $user->id)->first();

        if (!$mechanic) {
            return response()->json([
                'status' => 'success',
                'data' => []
            ], 200);
        }

        $query = Emergency::with(['user', 'vehicle', 'workshop'])
            ->where('mechanic_id', $mechanic->id);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $emergencies = $query->orderBy('requested_at', 'desc')->get();

        return response()->json([
            'status' => 'success',
            'data' => $emergencies
        ], 200);
    }

    /**
     * Show detail of a specific emergency assigned to this mechanic.
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        $mechanic = Mechanic::where('user_id', $user->id)->first();

        if (!$mechanic) {
            return response()->json([
                'status' => 'error',
                'message' => 'Mekanik tidak terdaftar.'
            ], 404);
        }

        $emergency = Emergency::with(['user', 'vehicle', 'workshop'])
            ->where('mechanic_id', $mechanic->id)
            ->where('id', $id)
            ->first();

        if (!$emergency) {
            return response()->json([
                'status' => 'error',
                'message' => 'Emergency request tidak ditemukan atau tidak ditugaskan kepada Anda.'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $emergency
        ], 200);
    }

    /**
     * Accept/Dispatch the emergency request.
     * Changes emergency status to 'dispatched' and mechanic status to 'busy'.
     */
    public function accept(Request $request, $id)
    {
        $user = $request->user();
        $mechanic = Mechanic::where('user_id', $user->id)->first();

        if (!$mechanic) {
            return response()->json([
                'status' => 'error',
                'message' => 'Mekanik tidak terdaftar.'
            ], 404);
        }

        $emergency = Emergency::where('mechanic_id', $mechanic->id)
            ->where('id', $id)
            ->first();

        if (!$emergency) {
            return response()->json([
                'status' => 'error',
                'message' => 'Emergency request tidak ditemukan atau tidak ditugaskan kepada Anda.'
            ], 404);
        }

        if ($emergency->status !== 'pending') {
            return response()->json([
                'status' => 'error',
                'message' => 'Hanya emergency request dengan status pending yang dapat diterima.'
            ], 400);
        }

        DB::transaction(function () use ($emergency, $mechanic) {
            // Update emergency status to dispatched (mechanic on the way)
            $emergency->update(['status' => 'dispatched']);
            
            // Update mechanic availability status to busy
            $mechanic->update(['status' => 'busy']);
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Emergency request berhasil diterima. Silakan menuju ke lokasi konsumen.',
            'data' => [
                'emergency_status' => 'dispatched',
                'mechanic_status' => 'busy'
            ]
        ], 200);
    }

    /**
     * Complete emergency penanganan by generating an invoice.
     * Handles both Towing and Non-Towing flows.
     */
    public function createInvoice(Request $request, $id)
    {
        $user = $request->user();
        $mechanic = Mechanic::where('user_id', $user->id)->first();

        if (!$mechanic) {
            return response()->json([
                'status' => 'error',
                'message' => 'Mekanik tidak terdaftar.'
            ], 404);
        }

        $emergency = Emergency::where('mechanic_id', $mechanic->id)
            ->where('id', $id)
            ->first();

        if (!$emergency) {
            return response()->json([
                'status' => 'error',
                'message' => 'Emergency request tidak ditemukan atau tidak ditugaskan kepada Anda.'
            ], 404);
        }

        if ($emergency->status === 'resolved') {
            return response()->json([
                'status' => 'error',
                'message' => 'Emergency request ini sudah selesai ditangani.'
            ], 400);
        }

        $request->validate([
            'is_towing' => 'required|boolean',
            'services' => 'array',
            'services.*.service_id' => 'nullable|exists:services,id',
            'services.*.additional_service' => 'nullable|string|max:255',
            'services.*.price' => 'required|numeric|min:0',
            'voucher_id' => 'nullable|exists:vouchers,id',
            'payment_type' => 'nullable|string|max:50',
        ]);

        $order = DB::transaction(function () use ($request, $emergency, $mechanic) {
            $isTowing = filter_var($request->is_towing, FILTER_VALIDATE_BOOLEAN);

            // Determine order statuses
            // Towing: Pembayaran di CS (status: payment, payment_status: pending)
            // Non-Towing: Pembayaran di tempat lewat mekanik (status: completed, payment_status: settlement)
            $status = $isTowing ? 'payment' : 'completed';
            $paymentStatus = $isTowing ? 'pending' : 'settlement';
            $paymentType = $isTowing ? null : ($request->payment_type ?? 'cash');

            // Calculate total price
            $totalPrice = 0;
            $servicesData = $request->input('services', []);
            foreach ($servicesData as $svc) {
                $totalPrice += floatval($svc['price'] ?? 0);
            }

            // 1. Create the Order record
            $order = Order::create([
                'mechanic_id' => $mechanic->id,
                'voucher_id' => $request->voucher_id ?? null,
                'status' => $status,
                'payment_status' => $paymentStatus,
                'total_price' => $totalPrice,
                'payment_type' => $paymentType,
                'scheduled_at' => null,
            ]);

            // 2. Create the Order Detail (Polymorphic link)
            OrderDetail::create([
                'order_id' => $order->id,
                'service_type' => 'emergency',
                'reference_id' => $emergency->id,
                'price' => $totalPrice,
            ]);

            // 3. Create NOrderServices for services rendered
            foreach ($servicesData as $svc) {
                NOrderService::create([
                    'order_id' => $order->id,
                    'service_id' => $svc['service_id'] ?? null,
                    'additional_service' => $svc['additional_service'] ?? null,
                    'price' => floatval($svc['price'] ?? 0),
                ]);
            }

            // 4. Update emergency status to resolved
            $emergency->update([
                'status' => 'resolved'
            ]);

            // 5. Update mechanic status to available (free to take next orders)
            $mechanic->update([
                'status' => 'available'
            ]);

            return $order;
        });

        // Load relations for response
        $order->load(['orderDetails', 'nOrderServices']);

        return response()->json([
            'status' => 'success',
            'message' => $request->is_towing 
                ? 'Invoice berhasil dibuat. Layanan towing diaktifkan, silakan hubungi Customer Service untuk pembayaran.' 
                : 'Invoice berhasil dibuat dan dibayar secara langsung di tempat.',
            'data' => [
                'order' => $order,
                'emergency_status' => 'resolved',
                'mechanic_status' => 'available'
            ]
        ], 200);
    }
}
