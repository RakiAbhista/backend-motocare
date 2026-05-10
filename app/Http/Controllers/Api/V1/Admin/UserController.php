<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display a listing of users
     */
    public function index(Request $request)
    {
        $query = User::query();

        // Filter berdasarkan role
        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        // Search berdasarkan name atau email
        if ($request->has('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%$search%")
                  ->orWhere('email', 'like', "%$search%")
                  ->orWhere('phone_number', 'like', "%$search%");
        }

        $users = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'status' => 'success',
            'data' => $users
        ], 200);
    }

    /**
     * Store a newly created user in database
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|unique:users',
            'phone_number' => 'required|string|max:20',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:customer,mechanic,customer_service,admin',
            'points' => 'sometimes|integer|min:0',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'points' => $request->points ?? 0,
            'email_verified_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'User berhasil dibuat.',
            'data' => $user
        ], 201);
    }

    /**
     * Display the specified user
     */
    public function show(User $user)
    {
        return response()->json([
            'status' => 'success',
            'data' => $user
        ], 200);
    }

    /**
     * Update the specified user in database
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|' . Rule::unique('users')->ignore($user->id),
            'phone_number' => 'sometimes|string|max:20',
            'password' => 'sometimes|string|min:8|confirmed',
            'role' => 'sometimes|in:customer,mechanic,customer_service,admin',
            'points' => 'sometimes|integer|min:0',
        ]);

        if ($request->has('name')) {
            $user->name = $request->name;
        }
        
        if ($request->has('email')) {
            $user->email = $request->email;
        }
        
        if ($request->has('phone_number')) {
            $user->phone_number = $request->phone_number;
        }
        
        if ($request->has('password')) {
            $user->password = Hash::make($request->password);
        }
        
        if ($request->has('role')) {
            $user->role = $request->role;
        }
        
        if ($request->has('points')) {
            $user->points = $request->points;
        }

        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'User berhasil diupdate.',
            'data' => $user
        ], 200);
    }

    /**
     * Remove the specified user from database
     */
    public function destroy(User $user)
    {
        $user->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'User berhasil dihapus.'
        ], 200);
    }
}
