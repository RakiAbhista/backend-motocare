<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Workshop;
use Illuminate\Http\Request;

class WorkshopController extends Controller
{
    /**
     * Display a listing of workshops
     */
    public function index(Request $request)
    {
        $query = Workshop::query();

        // Search berdasarkan name
        if ($request->has('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%$search%");
        }

        $workshops = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'status' => 'success',
            'data' => $workshops
        ], 200);
    }

    /**
     * Store a newly created workshop in database
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $workshop = Workshop::create([
            'name' => $request->name,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Workshop berhasil dibuat.',
            'data' => $workshop
        ], 201);
    }

    /**
     * Display the specified workshop
     */
    public function show(Workshop $workshop)
    {
        return response()->json([
            'status' => 'success',
            'data' => $workshop
        ], 200);
    }

    /**
     * Update the specified workshop in database
     */
    public function update(Request $request, Workshop $workshop)
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'latitude' => 'sometimes|numeric|between:-90,90',
            'longitude' => 'sometimes|numeric|between:-180,180',
        ]);

        if ($request->has('name')) {
            $workshop->name = $request->name;
        }

        if ($request->has('latitude')) {
            $workshop->latitude = $request->latitude;
        }

        if ($request->has('longitude')) {
            $workshop->longitude = $request->longitude;
        }

        $workshop->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Workshop berhasil diupdate.',
            'data' => $workshop
        ], 200);
    }

    /**
     * Remove the specified workshop from database
     */
    public function destroy(Workshop $workshop)
    {
        $workshop->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Workshop berhasil dihapus.'
        ], 200);
    }
}
