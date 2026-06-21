<?php

namespace App\Http\Controllers\Api\V1\Mechanic;

use App\Http\Controllers\Controller;
use App\Models\Mechanic;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $mechanic = Mechanic::where('user_id', $user->id)
            ->withCount([
                'orders' => fn($q) => $q->where('status', 'completed')
            ])
            ->firstOrFail();

        return response()->json([
            'status' => 'success',
            'data' => [
                'nama' => $user->name,
                'nomor_telepon' => $user->phone_number,
                'status_kerja' => $mechanic->status === 'available',
                'total_servis_selesai' => (int) $mechanic->orders_count,
            ]
        ], 200);
    }

    public function updateStatus(Request $request)
    {
        $data = $request->validate([
            'status_kerja' => 'required|boolean',
        ]);

        $mechanic = Mechanic::where('user_id', $request->user()->id)->firstOrFail();
        $mechanic->status = $data['status_kerja'] ? 'available' : 'offline';
        $mechanic->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Status kerja berhasil diperbarui.',
            'data' => [
                'status_kerja' => $data['status_kerja'],
            ]
        ], 200);
    }

    public function updatePhone(Request $request)
    {
        $data = $request->validate([
            'nomor_telepon' => 'required|string|max:20',
        ]);

        $user = $request->user();
        $user->phone_number = $data['nomor_telepon'];
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Nomor telepon berhasil diperbarui.',
            'data' => [
                'nomor_telepon' => $user->phone_number,
            ]
        ], 200);
    }

    public function workHistory(Request $request)
    {
        $mechanic = Mechanic::where('user_id', $request->user()->id)->firstOrFail();

        $orders = $mechanic->orders()
            ->where('status', 'completed')
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
                'total_price' => (float) $order->total_price,
                'completed_at' => $order->updated_at,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $history,
        ], 200);
    }
}
