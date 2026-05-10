<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use Illuminate\Http\Request;

class VoucherController extends Controller
{
    /**
     * Display a listing of vouchers
     */
    public function index(Request $request)
    {
        $query = Voucher::query();

        // Search berdasarkan code
        if ($request->has('search')) {
            $search = $request->search;
            $query->where('code', 'like', "%$search%");
        }

        // Filter berdasarkan discount_type
        if ($request->has('discount_type')) {
            $query->where('discount_type', $request->discount_type);
        }

        // Filter berdasarkan status (active/expired)
        if ($request->has('status')) {
            if ($request->status === 'active') {
                $query->where('expiry_date', '>', now());
            } elseif ($request->status === 'expired') {
                $query->where('expiry_date', '<=', now());
            }
        }

        $vouchers = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'status' => 'success',
            'data' => $vouchers
        ], 200);
    }

    /**
     * Store a newly created voucher in database
     */
    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|unique:vouchers',
            'discount_value' => 'required|numeric|min:0',
            'discount_type' => 'required|in:fixed,percentage',
            'min_spend' => 'required|numeric|min:0',
            'expiry_date' => 'required|date|after:today',
            'usage_limit' => 'required|integer|min:1',
        ]);

        $voucher = Voucher::create([
            'code' => strtoupper($request->code),
            'discount_value' => $request->discount_value,
            'discount_type' => $request->discount_type,
            'min_spend' => $request->min_spend,
            'expiry_date' => $request->expiry_date,
            'usage_limit' => $request->usage_limit,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Voucher berhasil dibuat.',
            'data' => $voucher
        ], 201);
    }

    /**
     * Display the specified voucher
     */
    public function show(Voucher $voucher)
    {
        return response()->json([
            'status' => 'success',
            'data' => $voucher
        ], 200);
    }

    /**
     * Update the specified voucher in database
     */
    public function update(Request $request, Voucher $voucher)
    {
        $request->validate([
            'code' => 'sometimes|string|unique:vouchers,code,' . $voucher->id,
            'discount_value' => 'sometimes|numeric|min:0',
            'discount_type' => 'sometimes|in:fixed,percentage',
            'min_spend' => 'sometimes|numeric|min:0',
            'expiry_date' => 'sometimes|date|after:today',
            'usage_limit' => 'sometimes|integer|min:1',
        ]);

        if ($request->has('code')) {
            $voucher->code = strtoupper($request->code);
        }

        if ($request->has('discount_value')) {
            $voucher->discount_value = $request->discount_value;
        }

        if ($request->has('discount_type')) {
            $voucher->discount_type = $request->discount_type;
        }

        if ($request->has('min_spend')) {
            $voucher->min_spend = $request->min_spend;
        }

        if ($request->has('expiry_date')) {
            $voucher->expiry_date = $request->expiry_date;
        }

        if ($request->has('usage_limit')) {
            $voucher->usage_limit = $request->usage_limit;
        }

        $voucher->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Voucher berhasil diupdate.',
            'data' => $voucher
        ], 200);
    }

    /**
     * Remove the specified voucher from database
     */
    public function destroy(Voucher $voucher)
    {
        $voucher->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Voucher berhasil dihapus.'
        ], 200);
    }
}
