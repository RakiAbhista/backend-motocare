<?php

namespace App\Http\Controllers\Api\V1\Mechanic;

use App\Http\Controllers\Controller;
use App\Models\Emergency;
use App\Models\Order;
use App\Models\Mechanic;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Get mechanic dashboard summary data.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Retrieve or create the mechanic record linked to this user
        $mechanic = Mechanic::with('user')->firstOrCreate(
            ['user_id' => $user->id],
            ['status' => 'available']
        );

        // 1. Calculate stats: total completed jobs (both emergency & standard orders)
        // Standard orders or emergency orders handled by this mechanic that are completed
        $totalCompletedJobs = Order::where('mechanic_id', $mechanic->id)
            ->where('status', 'completed')
            ->count();

        // 2. Look for active emergency request assigned to this mechanic (pending or dispatched)
        $activeEmergency = Emergency::with(['user', 'vehicle', 'workshop'])
            ->where('mechanic_id', $mechanic->id)
            ->whereIn('status', ['pending', 'dispatched'])
            ->orderBy('requested_at', 'desc')
            ->first();

        // 3. Look for active normal order assigned to this mechanic (in process)
        $activeOrder = Order::with(['orderDetails', 'nOrderServices'])
            ->where('mechanic_id', $mechanic->id)
            ->where('status', 'process')
            ->orderBy('created_at', 'desc')
            ->first();

        // Combine into one active job structure if exists
        $activeJob = null;
        if ($activeEmergency) {
            $activeJob = [
                'type' => 'emergency',
                'details' => $activeEmergency
            ];
        } elseif ($activeOrder) {
            $activeJob = [
                'type' => 'order',
                'details' => $activeOrder
            ];
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'mechanic' => [
                    'id' => $mechanic->id,
                    'status' => $mechanic->status,
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone_number' => $user->phone_number,
                        'points' => $user->points,
                    ]
                ],
                'stats' => [
                    'total_completed_jobs' => $totalCompletedJobs,
                ],
                'active_job' => $activeJob
            ]
        ], 200);
    }

    /**
     * Toggle or update mechanic availability status.
     */
    public function updateStatus(Request $request)
    {
        $request->validate([
            'status' => 'required|in:available,busy',
        ]);

        $user = $request->user();
        
        $mechanic = Mechanic::where('user_id', $user->id)->first();
        
        if (!$mechanic) {
            $mechanic = Mechanic::create([
                'user_id' => $user->id,
                'status' => $request->status,
            ]);
        } else {
            $mechanic->update([
                'status' => $request->status,
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Status berhasil diperbarui.',
            'data' => [
                'status' => $mechanic->status,
            ]
        ], 200);
    }
}
