<?php

namespace App\Http\Controllers\Api\V1\CS;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CSProfileController extends Controller
{
    /**
     * Display authenticated user profile
     */
    public function profile(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'status' => 'success',
            'data' => [
                'name' => $user->name,
                'role' => $user->role,
                'email' => $user->email,
                'phone_number' => $user->phone_number,

            ]
        ], 200);
    }

    /**
     * Update user phone number
     */
    public function updatePhoneNumber(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string|min:10|max:15',
        ]);

        $user = $request->user();
        $user->update(['phone_number' => $request->phone_number]);

        return response()->json([
            'status' => 'success',
            'message' => 'Nomor telepon berhasil diperbarui.',
            'data' => [
                'phone_number' => $user->phone_number,
            ]
        ], 200);
    }
}