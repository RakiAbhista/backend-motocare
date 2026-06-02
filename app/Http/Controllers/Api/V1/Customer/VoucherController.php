<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use Illuminate\Http\Request;

class VoucherController extends Controller
{
    public function index()
    {
        $vouchers = Voucher::where('expiry_date', '>', now())
            ->where(function ($q) {
                $q->whereNull('usage_limit')->orWhere('usage_limit', '>', 0);
            })
            ->get();

        return response()->json(['success' => true, 'data' => $vouchers]);
    }

    public function validate(Request $request)
    {
        $request->validate(['code' => 'required|string']);

        $voucher = Voucher::where('code', $request->code)->first();

        if (!$voucher) {
            return response()->json(['success' => false, 'message' => 'Kode voucher tidak valid'], 404);
        }

        if ($voucher->expiry_date < now()) {
            return response()->json(['success' => false, 'message' => 'Voucher sudah kedaluwarsa'], 400);
        }

        if ($voucher->usage_limit !== null && $voucher->usage_limit <= 0) {
            return response()->json(['success' => false, 'message' => 'Kuota voucher habis'], 400);
        }

        return response()->json(['success' => true, 'data' => $voucher]);
    }
}
