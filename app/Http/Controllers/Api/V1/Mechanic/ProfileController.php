<?php

namespace App\Http\Controllers\Api\V1\Mechanic;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Mechanic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    /**
     * Show the profile of the authenticated mechanic.
     */
    public function show(Request $request)
    {
        $user = $request->user();
        $mechanic = Mechanic::where('user_id', $user->id)->first();

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $mechanic->id ?? null,
                'status' => $mechanic->status ?? 'available',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone_number' => $user->phone_number,
                    'points' => $user->points,
                    'role' => $user->role,
                ]
            ]
        ], 200);
    }

    /**
     * Update the profile of the authenticated mechanic.
     */
    public function update(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:20',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        $updateData = [
            'name' => $request->name,
            'phone_number' => $request->phone_number,
            'email' => $request->email,
        ];

        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        $user->update($updateData);

        $mechanic = Mechanic::where('user_id', $user->id)->first();

        return response()->json([
            'status' => 'success',
            'message' => 'Profil berhasil diperbarui.',
            'data' => [
                'id' => $mechanic->id ?? null,
                'status' => $mechanic->status ?? 'available',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone_number' => $user->phone_number,
                    'points' => $user->points,
                    'role' => $user->role,
                ]
            ]
        ], 200);
    }
}
