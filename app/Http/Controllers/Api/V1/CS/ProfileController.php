<?php

namespace App\Http\Controllers\Api\V1\CS;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /**
     * Display authenticated user profile
     */
    public function show(Request $request)
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
}