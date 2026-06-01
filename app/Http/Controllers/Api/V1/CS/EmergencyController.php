<?php

namespace App\Http\Controllers\Api\V1\CS;

use App\Http\Controllers\Controller;
use App\Models\Emergency;
use App\Models\Mechanic;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmergencyController extends Controller
{
    /**
     * GET /api/v1/cs/emergencies
     * List semua emergency
     */
    public function index()
    {
        $emergencies = Emergency::with([
            'user',
            'vehicle',
            'mechanic'
        ])
        ->latest()
        ->get();

        $formattedEmergency = $emergencies->map(function ($emergency) {
            return [
                'id'             => $emergency->id,
                'customer_name'  => $emergency->user?->name,
                'vehicle_brand'  => $emergency->vehicle?->brand,
                'vehicle_model'  => $emergency->vehicle?->model,
                'plate_number'   => $emergency->vehicle?->plate_number,
                'mechanic'       => $emergency->mechanic ? [
                    'id'   => $emergency->mechanic->id,
                    'name' => $emergency->mechanic->name,
                ] : null,
                'status'         => $emergency->status,
                'created_at'     => $emergency->created_at,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data'   => $formattedEmergency
        ], 200);
    }

    /**
     * GET /api/v1/cs/emergencies/{id}
     * Detail satu emergency
     */
    public function show($id)
    {
        $emergency = Emergency::with(['user', 'vehicle', 'mechanic'])->find($id);

        if (!$emergency) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Emergency tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data'   => [
                'id'             => $emergency->id,
                'customer_name'  => $emergency->user?->name,
                'vehicle_brand'  => $emergency->vehicle?->brand,
                'vehicle_model'  => $emergency->vehicle?->model,
                'plate_number'   => $emergency->vehicle?->plate_number,
                'location'       => $emergency->location,
                'description'    => $emergency->description,
                'mechanic'       => $emergency->mechanic ? [
                    'id'    => $emergency->mechanic->id,
                    'name'  => $emergency->mechanic->name,
                    'phone' => $emergency->mechanic->phone ?? null,
                ] : null,
                'status'         => $emergency->status,
                'created_at'     => $emergency->created_at,
                'updated_at'     => $emergency->updated_at,
            ]
        ], 200);
    }

    /**
     * PUT /api/v1/cs/emergencies/{id}/assign
     * CS assign mekanik ke emergency → status otomatis jadi "dispatch"
     */
    public function assignMechanic(Request $request, $id)
    {
        $emergency = Emergency::find($id);

        if (!$emergency) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Emergency tidak ditemukan'
            ], 404);
        }

        if ($emergency->status !== 'pending') {
            return response()->json([
                'status'  => 'error',
                'message' => 'Emergency ini sudah diproses (status: ' . $emergency->status . ')'
            ], 422);
        }

        $request->validate([
            'mechanic_id' => ['required', 'exists:mechanics,id']
        ]);

        $mechanic = Mechanic::find($request->mechanic_id);

        $emergency->update([
            'mechanic_id' => $mechanic->id,
            'status'      => 'dispatched',
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Mekanik berhasil di-assign, status berubah ke dispatched',
            'data'    => [
                'emergency_id'  => $emergency->id,
                'mechanic_id'   => $mechanic->id,
                'mechanic_name' => $mechanic->name,
                'status'        => $emergency->status,
            ]
        ], 200);
    }

    /**
     * PUT /api/v1/cs/emergencies/{id}/status
     * Update status emergency (dispatch → resolved)
     */
    public function updateStatus(Request $request, $id)
    {
        $emergency = Emergency::find($id);

        if (!$emergency) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Emergency tidak ditemukan'
            ], 404);
        }

        $request->validate([
            'status' => ['required', Rule::in(['dispatched', 'resolved'])]
        ]);

        $allowedTransitions = [
            'pending'    => ['dispatched'],
            'dispatched' => ['resolved'],
        ];

        $currentStatus = $emergency->status;
        $newStatus     = $request->status;

        if (!isset($allowedTransitions[$currentStatus]) ||
            !in_array($newStatus, $allowedTransitions[$currentStatus])) {
            return response()->json([
                'status'  => 'error',
                'message' => "Tidak bisa mengubah status dari '{$currentStatus}' ke '{$newStatus}'"
            ], 422);
        }

        $emergency->update(['status' => $newStatus]);

        return response()->json([
            'status'  => 'success',
            'message' => "Status emergency berhasil diubah ke '{$newStatus}'",
            'data'    => [
                'emergency_id' => $emergency->id,
                'status'       => $emergency->status,
            ]
        ], 200);
    }

    /**
     * GET /api/v1/cs/mechanics
     * List mekanik yang tersedia (helper untuk CS pilih mekanik)
     */
    public function availableMechanics()
    {
        $mechanics = Mechanic::with('user')
            ->orderBy('id')
            ->get()
            ->map(function ($mechanic) {
                return [
                    'id'     => $mechanic->id,
                    'name'   => $mechanic->user?->name,
                    'email'  => $mechanic->user?->email,
                    'status' => $mechanic->status,
                ];
            });

        return response()->json([
            'status' => 'success',
            'data'   => $mechanics
        ], 200);
    }
}