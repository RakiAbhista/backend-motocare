<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Models\Workshop;
use Illuminate\Http\Request;

class WorkshopController extends Controller
{
    /**
     * List semua workshop (nama, lat, long)
     */
    public function index()
    {
        $workshops = Workshop::select('id', 'name', 'latitude', 'longitude')->get();

        return response()->json([
            'success' => true,
            'data'    => $workshops
        ], 200);
    }

    /**
     * List workshop terdekat dari lokasi user (urut terdekat ke terjauh)
     */
    public function nearest(Request $request)
    {
        $request->validate([
            'latitude'  => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $userLat = $request->latitude;
        $userLng = $request->longitude;

        $workshops = Workshop::select('id', 'name', 'latitude', 'longitude')
            ->get()
            ->map(function ($workshop) use ($userLat, $userLng) {
                $distance = $this->haversine(
                    $userLat, $userLng,
                    $workshop->latitude, $workshop->longitude
                );

                return [
                    'id'              => $workshop->id,
                    'name'            => $workshop->name,
                    'latitude'        => $workshop->latitude,
                    'longitude'       => $workshop->longitude,
                    'distance_meters' => round($distance),
                ];
            })
            ->sortBy('distance_meters')
            ->values();

        return response()->json([
            'success' => true,
            'data'    => $workshops
        ], 200);
    }

    /**
     * Haversine formula — hitung jarak dua koordinat dalam meter
     */
    private function haversine($lat1, $lng1, $lat2, $lng2): float
    {
        $earthRadius = 6371000; // meter

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2)
           + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
           * sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}