<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Emergency;
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
            'active_vouchers_count' => 2, // Mock statis sementara sebelum tabel relasi voucher user dibuat oleh tim auth
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
            ->get(['id', 'brand', 'model', 'plate_number', 'vehicle_type']);

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

        // Response Terpusat siap dikonsumsi Flutter
        return response()->json([
            'success' => true,
            'message' => 'Data beranda customer berhasil dimuat.',
            'data' => [
                'user_summary'     => $userSummary,
                'banners'          => $banners,
                'vehicles'         => $vehicles,
                'active_emergency' => $activeEmergency,
            ]
        ], 200);
    }
}