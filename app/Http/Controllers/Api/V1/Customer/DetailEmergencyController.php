<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Emergency;
use App\Models\Mechanic;
use Illuminate\Support\Facades\Auth;

class DetailEmergencyController extends Controller
{
    /**
     * Detail emergency + posisi mekanik (untuk customer)
     */
    public function detail($emergencyId)
    {
        $userId = Auth::id();

        $emergency = Emergency::with(['mechanic.user'])
            ->where('id', $emergencyId)
            ->where('user_id', $userId) // ← pastikan emergency ini milik customer yang login
            ->first();

        if (!$emergency) {
            return response()->json([
                'success' => false,
                'message' => 'Emergency tidak ditemukan atau bukan milik Anda',
            ], 404);
        }

        $mechanic = $emergency->mechanic;

        return response()->json([
            'success' => true,
            'data' => [
                'emergency_status' => $emergency->status,
                'mechanic_name'     => $mechanic?->user->name ?? null,
                'mechanic_phone'    => $mechanic?->user->phone_number ?? null,
                'mechanic_location' => $mechanic ? [
                    'latitude'  => $mechanic->latitude,
                    'longitude' => $mechanic->longitude,
                ] : null,
                'customer_location' => [
                    'latitude'  => $emergency->latitude,
                    'longitude' => $emergency->longitude,
                ],
            ],
        ]);
    }

    /**
     * Update lokasi mekanik (dipanggil oleh app mekanik tiap interval)
     */
    public function updateMechanicLocation(Request $request, $mechanicId)
    {
        $request->validate([
            'latitude'  => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $user = $request->user();

        $mechanic = Mechanic::where('id', $mechanicId)
            ->where('user_id', $user->id) // ← wajib: mechanic hanya bisa update lokasinya sendiri
            ->first();

        if (!$mechanic) {
            return response()->json([
                'success' => false,
                'message' => 'Mechanic tidak ditemukan atau Anda tidak berhak mengubah lokasi ini',
            ], 403);
        }

        $mechanic->update([
            'latitude'  => $request->latitude,
            'longitude' => $request->longitude,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Lokasi mekanik diperbarui',
            'data' => [
                'mechanic_id' => $mechanic->id,
                'latitude'    => $mechanic->latitude,
                'longitude'   => $mechanic->longitude,
                'updated_at'  => $mechanic->updated_at,
            ],
        ]);
    }
}