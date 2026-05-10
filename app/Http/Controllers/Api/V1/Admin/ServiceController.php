<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    /**
     * Display a listing of services
     */
    public function index(Request $request)
    {
        $query = Service::query();

        // Search berdasarkan service_name
        if ($request->has('search')) {
            $search = $request->search;
            $query->where('service_name', 'like', "%$search%");
        }

        // Sort berdasarkan price
        if ($request->has('sort_price')) {
            $direction = $request->sort_price === 'desc' ? 'desc' : 'asc';
            $query->orderBy('base_price', $direction);
        }

        $services = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'status' => 'success',
            'data' => $services
        ], 200);
    }

    /**
     * Store a newly created service in database
     */
    public function store(Request $request)
    {
        $request->validate([
            'service_name' => 'required|string|max:255',
            'base_price' => 'required|numeric|min:0',
        ]);

        $service = Service::create([
            'service_name' => $request->service_name,
            'base_price' => $request->base_price,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Service berhasil dibuat.',
            'data' => $service
        ], 201);
    }

    /**
     * Display the specified service
     */
    public function show(Service $service)
    {
        return response()->json([
            'status' => 'success',
            'data' => $service
        ], 200);
    }

    /**
     * Update the specified service in database
     */
    public function update(Request $request, Service $service)
    {
        $request->validate([
            'service_name' => 'sometimes|string|max:255',
            'base_price' => 'sometimes|numeric|min:0',
        ]);

        if ($request->has('service_name')) {
            $service->service_name = $request->service_name;
        }

        if ($request->has('base_price')) {
            $service->base_price = $request->base_price;
        }

        $service->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Service berhasil diupdate.',
            'data' => $service
        ], 200);
    }

    /**
     * Remove the specified service from database
     */
    public function destroy(Service $service)
    {
        $service->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Service berhasil dihapus.'
        ], 200);
    }
}
