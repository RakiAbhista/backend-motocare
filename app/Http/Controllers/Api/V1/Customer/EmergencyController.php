<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Models\Emergency;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmergencyController extends Controller
{
    public function index()
    {
        $emergencies = Emergency::with(['vehicle', 'workshop', 'mechanic.user'])
            ->where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['success' => true, 'data' => $emergencies]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'workshop_id' => 'nullable|exists:workshops,id',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'damage_photo' => 'nullable|image|max:2048',
        ]);

        $vehicle = Vehicle::where('id', $request->vehicle_id)->where('user_id', Auth::id())->first();
        if (!$vehicle) {
            return response()->json(['success' => false, 'message' => 'Kendaraan tidak ditemukan'], 404);
        }

        $data = [
            'user_id' => Auth::id(),
            'vehicle_id' => $request->vehicle_id,
            'workshop_id' => $request->workshop_id,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'status' => 'pending',
            'requested_at' => now(),
        ];

        if ($request->hasFile('damage_photo')) {
            $data['damage_photo'] = $request->file('damage_photo')->store('emergencies', 'public');
        }

        $emergency = Emergency::create($data);
        $emergency->load(['vehicle', 'workshop']);

        return response()->json(['success' => true, 'message' => 'Permintaan darurat terkirim', 'data' => $emergency], 201);
    }

    public function show($id)
    {
        $emergency = Emergency::with(['vehicle', 'workshop', 'mechanic.user'])
            ->where('id', $id)->where('user_id', Auth::id())->first();

        if (!$emergency) {
            return response()->json(['success' => false, 'message' => 'Emergency tidak ditemukan'], 404);
        }

        return response()->json(['success' => true, 'data' => $emergency]);
    }

    public function cancel($id)
    {
        $emergency = Emergency::where('id', $id)->where('user_id', Auth::id())->first();

        if (!$emergency) {
            return response()->json(['success' => false, 'message' => 'Emergency tidak ditemukan'], 404);
        }

        if (!in_array($emergency->status, ['pending'])) {
            return response()->json(['success' => false, 'message' => 'Emergency sudah tidak bisa dibatalkan'], 400);
        }

        $emergency->update(['status' => 'resolved']);

        return response()->json(['success' => true, 'message' => 'Permintaan darurat dibatalkan']);
    }
}
