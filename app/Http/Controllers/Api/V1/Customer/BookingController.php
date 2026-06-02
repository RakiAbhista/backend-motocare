<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BookingController extends Controller
{
    public function index()
    {
        $bookings = Booking::with(['vehicle', 'workshop', 'service'])
            ->where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['success' => true, 'data' => $bookings]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'workshop_id' => 'required|exists:workshops,id',
            'service_id' => 'required|exists:services,id',
            'complaint' => 'nullable|string',
            'damage_photo' => 'nullable|image|max:2048',
            'booking_date' => 'required|date',
        ]);

        $vehicle = Vehicle::where('id', $request->vehicle_id)->where('user_id', Auth::id())->first();
        if (!$vehicle) {
            return response()->json(['success' => false, 'message' => 'Kendaraan tidak ditemukan'], 404);
        }

        $data = $request->only(['vehicle_id', 'workshop_id', 'service_id', 'complaint', 'booking_date']);
        $data['user_id'] = Auth::id();

        if ($request->hasFile('damage_photo')) {
            $data['damage_photo'] = $request->file('damage_photo')->store('bookings', 'public');
        }

        $booking = Booking::create($data);
        $booking->load(['vehicle', 'workshop', 'service']);

        return response()->json(['success' => true, 'message' => 'Booking berhasil dibuat', 'data' => $booking], 201);
    }

    public function show($id)
    {
        $booking = Booking::with(['vehicle', 'workshop', 'service'])
            ->where('id', $id)->where('user_id', Auth::id())->first();

        if (!$booking) {
            return response()->json(['success' => false, 'message' => 'Booking tidak ditemukan'], 404);
        }

        return response()->json(['success' => true, 'data' => $booking]);
    }

    public function destroy($id)
    {
        $booking = Booking::where('id', $id)->where('user_id', Auth::id())->first();

        if (!$booking) {
            return response()->json(['success' => false, 'message' => 'Booking tidak ditemukan'], 404);
        }

        $booking->delete();

        return response()->json(['success' => true, 'message' => 'Booking dibatalkan']);
    }
}
