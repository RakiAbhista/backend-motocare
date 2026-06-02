<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Models\Workshop;
use Illuminate\Http\Request;

class WorkshopController extends Controller
{
    public function index()
    {
        $workshops = Workshop::all(['id', 'name', 'latitude', 'longitude']);
        return response()->json(['success' => true, 'data' => $workshops]);
    }

    public function show($id)
    {
        $workshop = Workshop::find($id);
        if (!$workshop) {
            return response()->json(['success' => false, 'message' => 'Bengkel tidak ditemukan'], 404);
        }
        return response()->json(['success' => true, 'data' => $workshop]);
    }

    public function nearby(Request $request)
    {
        $request->validate(['lat' => 'required|numeric', 'lng' => 'required|numeric']);

        $workshops = Workshop::selectRaw("*, (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance", [$request->lat, $request->lng, $request->lat])
            ->having('distance', '<', 10)
            ->orderBy('distance')
            ->get(['id', 'name', 'latitude', 'longitude']);

        return response()->json(['success' => true, 'data' => $workshops]);
    }
}
