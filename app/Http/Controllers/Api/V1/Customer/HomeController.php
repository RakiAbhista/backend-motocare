<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Emergency;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Booking;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    /**
     * Mengambil seluruh data kebutuhan halaman Beranda Customer
     */
    public function index(Request $request)
    {
        // Mendapatkan ID user yang sedang login (Fallback ke ID 1 jika untuk keperluan testing unauthenticated)
        $userId = Auth::id() ?? 1;
        $user = User::find($userId);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak ditemukan'
            ], 404);
        }

        // 1. Ringkasan Profil (Poin & Jumlah Voucher)
        $userSummary = [
            'name'  => $user->name,
            'points' => $user->points ?? 0,
            'active_vouchers_count' => 0,
        ];

        // 2. Banner Promo (Data statis aman yang mengarah ke aset lokal/url luar)
        $banners = [
            [
                'id' => 1,
                'title' => 'Promo Merdeka: Hemat Servis Ganti Oli',
                'image_url' => 'https://images.unsplash.com/photo-1486006920555-c77dce18193b?q=80&w=500',
            ],
            [
                'id' => 2,
                'title' => 'Diskon Towing Darurat 20%',
                'image_url' => 'https://images.unsplash.com/photo-1580273916550-e323be2ae537?q=80&w=500',
            ]
        ];

        // 3. Daftar Kendaraan Milik Customer (Mengambil dari tabel asli)
        $vehicles = Vehicle::where('user_id', $userId)
            ->get(['id', 'brand', 'model', 'plate_number', 'vehicle_type', 'manufacturing_year', 'registration_doc']);

        // 4. Status Panggilan Darurat Aktif (Tracker)
        $activeEmergency = null;
        $emergency = Emergency::with('workshop')
            ->where('user_id', $userId)
            ->whereIn('status', ['pending', 'dispatched']) // Mengambil yang belum resolved
            ->latest()
            ->first();

        if ($emergency) {
            $activeEmergency = [
                'id' => '#EMG-' . str_pad($emergency->id, 4, '0', STR_PAD_LEFT),
                'emergency_type' => 'mekanik', // Sesuai tipe daruratnya
                'responder_name' => $emergency->workshop->name ?? 'Mencari Bengkel...',
                'status' => $emergency->status, // 'pending' = Peninjauan, 'dispatched' = Menuju Lokasi
            ];
        }

        // 5. Active Order Status (pending, process, payment)
        $activeOrder = null;

        $order = Order::whereIn('status', ['pending', 'process', 'payment'])
            ->whereHas('orderDetails', function ($query) use ($userId) {
                $query->where('service_type', 'booking')
                    ->whereHasMorph('source', [Booking::class], function ($q) use ($userId) {
                        $q->where('user_id', $userId);
                    });
            })
            ->with([
                'orderDetails' => function ($query) {
                    $query->where('service_type', 'booking');
                },
                'orderDetails.source',
                'orderServices.service'
            ])
            ->latest()
            ->first();

        if ($order) {
            $booking = $order->orderDetails->first()?->source;
            $activeOrder = [
                'order_id'     => $order->id,
                'booking_id'   => $booking?->id,
                'status'       => $order->status,
                'total_price'  => $order->total_price,
                'is_towing'    => $order->is_towing,
                'booking_date' => $booking?->booking_date,
                'services'     => $order->orderServices->map(fn($os) => [
                    'id'           => $os->service->id,
                    'service_name' => $os->service->service_name,
                    'base_price'   => $os->service->base_price,
                ]),
            ];
        }

        // Response Terpusat siap dikonsumsi Flutter
        return response()->json([
            'success' => true,
            'message' => 'Data beranda customer berhasil dimuat.',
            'data' => [
                'user_summary'     => $userSummary,
                'banners'          => $banners,
                'vehicles'         => $vehicles,
                'active_emergency' => $activeEmergency,
                'active_order'     => $activeOrder,
            ]
        ], 200);
    }
}