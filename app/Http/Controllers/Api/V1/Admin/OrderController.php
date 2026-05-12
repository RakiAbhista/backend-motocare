<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * Display a listing of orders
     */
    public function index(Request $request)
    {
        $query = Order::with([
            'mechanic.user',
            'voucher',
            'orderDetails.booking.user',
            'orderDetails.emergency.user'
        ]);

        // Filter berdasarkan status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter berdasarkan payment_status
        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        // Filter berdasarkan mechanic
        if ($request->has('mechanic_id')) {
            $query->where('mechanic_id', $request->mechanic_id);
        }

        // Search berdasarkan transaction_id
        if ($request->has('search')) {
            $search = $request->search;
            $query->where('transaction_id', 'like', "%$search%");
        }

        // Sort berdasarkan date
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $orders = $query->paginate($request->get('per_page', 15));

        // Format untuk frontend - map order details ke customer dan workshop
        $orders->getCollection()->transform(function ($order) {
            $firstDetail = $order->orderDetails->first();
            $customer = null;
            $workshop = null;

            if ($firstDetail) {
                if ($firstDetail->booking) {
                    $customer = $firstDetail->booking->user;
                } elseif ($firstDetail->emergency) {
                    $customer = $firstDetail->emergency->user;
                }
            }

            if ($order->mechanic) {
                $workshop = $order->mechanic->user;
            }

            return [
                'id' => $order->id,
                'customer' => $customer,
                'workshop' => $workshop,
                'mechanic' => $order->mechanic,
                'voucher' => $order->voucher,
                'total_price' => $order->total_price,
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'payment_type' => $order->payment_type,
                'transaction_id' => $order->transaction_id,
                'payment_url' => $order->payment_url,
                'scheduled_at' => $order->scheduled_at,
                'created_at' => $order->created_at,
                'updated_at' => $order->updated_at,
                'orderDetails' => $order->orderDetails,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $orders
        ], 200);
    }

    /**
     * Display the specified order
     */
    public function show(Order $order)
    {
        $order->load(['mechanic', 'voucher', 'orderDetails']);

        return response()->json([
            'status' => 'success',
            'data' => $order
        ], 200);
    }

    /**
     * Remove the specified order from database
     */
    public function destroy(Order $order)
    {
        // Hapus order details terlebih dahulu (cascade delete sudah diatur di migration)
        $order->orderDetails()->delete();
        
        $order->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Order berhasil dihapus.'
        ], 200);
    }
}
