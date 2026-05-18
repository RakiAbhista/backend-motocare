<?php

namespace App\Http\Controllers\Api\V1\CS;

use App\Http\Controllers\Controller;
use App\Models\Emergency;
use Illuminate\Http\Request;

class EmergencyController extends Controller
{
    public function index()
    {
        $emergencies = Emergency::with([
            'user',
            'vehicle'
        ])
        ->latest()
        ->get();

        $formattedEmergency = $emergencies->map(function ($emergency) {

            return [
                'id' => $emergency->id,

                'customer_name' => $emergency->user?->name,

                'vehicle_brand' => $emergency->vehicle?->brand,

                'vehicle_model' => $emergency->vehicle?->model,

                'plate_number' => $emergency->vehicle?->plate_number,

                'status' => $emergency->status,

                'created_at' => $emergency->created_at,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $formattedEmergency
        ], 200);
    }
}