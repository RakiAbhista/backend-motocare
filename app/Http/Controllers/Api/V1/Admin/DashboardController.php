<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Order;
use App\Models\Service;
use App\Models\Emergency;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Get complete dashboard data (all in one)
     * Query params:
     * - type   : all | normal | emergency (default: all)
     * - period : 1-week | 1-month | 3-months | 6-months | 1-year (default: 1-week)
     */
    public function index(Request $request)
    {
        $type   = $request->get('type', 'all');
        $period = $request->get('period', '1-week');

        return response()->json([
            'status' => 'success',
            'data'   => [
                'statistics'        => $this->getStatistics(),
                'order_chart'       => $this->getOrderChart($type, $period),
                'top_services'      => $this->getTopServices(),
                'latest_activities' => $this->getLatestActivities(),
                'latest_orders'     => $this->getLatestOrders(),
            ],
        ], 200);
    }

    // -------------------------------------------------------------------------
    // Private helpers — setiap bagian dashboard dipisah agar mudah di-maintain
    // -------------------------------------------------------------------------

    /**
     * a. Statistics
     */
    private function getStatistics(): array
    {
        $today = today();

        // Hitung semua role sekaligus — 1 query (Sesuaikan dengan kapitalisasi di DB)
        $roleCounts = User::selectRaw("
                role,
                COUNT(*) as total
            ")
            ->whereIn('role', ['Customer', 'Mechanic', 'CS'])
            ->groupBy('role')
            ->pluck('total', 'role');

        // Total order hari ini — 1 query
        $ordersToday = Order::whereDate('created_at', $today)
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
            ")
            ->first();

        return [
            'total_customers'        => $roleCounts->get('Customer', 0),
            'total_mechanics'        => $roleCounts->get('Mechanic', 0),
            'total_customer_service' => $roleCounts->get('CS', 0),
            'total_orders_today'     => (int) ($ordersToday->total ?? 0),
            'total_completed_today'  => (int) ($ordersToday->completed ?? 0),
        ];
    }

    /**
     * b. Grafik order — filter by type & period (Fix PostgreSQL TO_CHAR)
     */
    private function getOrderChart(string $type, string $period): array
    {
        $startDate = match ($period) {
            '1-month'   => Carbon::now()->subMonth(),
            '3-months'  => Carbon::now()->subMonths(3),
            '6-months'  => Carbon::now()->subMonths(6),
            '1-year'    => Carbon::now()->subYear(),
            default     => Carbon::now()->subWeek(), // 1-week
        };

        $query = Order::whereBetween('created_at', [$startDate, Carbon::now()]);

        // Filter berdasarkan jenis order
        if ($type === 'normal') {
            $query->whereHas('orderDetails', fn ($q) =>
                $q->where('service_type', 'booking')
            );
        } elseif ($type === 'emergency') {
            $query->whereHas('orderDetails', fn ($q) =>
                $q->where('service_type', 'emergency')
            );
        }

        // Group by date — format PostgreSQL
        $dateFormat = match ($period) {
            '1-week', '1-month' => 'YYYY-MM-DD',
            '3-months'          => 'IYYY-IW',
            '6-months', '1-year'=> 'YYYY-MM',
            default             => 'YYYY-MM-DD',
        };

        // Fix titik-titik (...) menjadi fungsi COUNT(*)
        $chartData = $query
            ->selectRaw("TO_CHAR(created_at, '{$dateFormat}') as label, COUNT(*) as total")
            ->groupBy('label')
            ->orderBy('label', 'asc')
            ->get();

        return [
            'type'   => $type,
            'period' => $period,
            'labels' => $chartData->pluck('label'),
            'counts' => $chartData->pluck('total'),
        ];
    }

    /**
     * c. Top 5 service paling sering digunakan
     */
    private function getTopServices(): \Illuminate\Support\Collection
    {
        return Service::select('services.id', 'services.service_name', 'services.base_price')
            // Hitung ID dari tabel n_order_services
            ->selectRaw('COUNT(n_order_services.id) as usage_count')
            // Join ke tabel n_order_services berdasarkan service_id
            ->leftJoin('n_order_services', 'n_order_services.service_id', '=', 'services.id')
            ->groupBy('services.id', 'services.service_name', 'services.base_price')
            ->orderByDesc('usage_count')
            ->limit(5)
            ->get();
    }

    /**
     * d. Aktivitas terbaru (Fix kolom name menjadi name)
     */
    private function getLatestActivities(): \Illuminate\Support\Collection
    {
        // Customer baru
        $newCustomers = User::where('role', 'Customer')
            ->latest('created_at')
            ->limit(4)
            ->get(['id', 'name', 'email', 'created_at'])
            ->map(fn ($user) => [
                'type'        => 'customer_registered',
                'title'       => 'Pelanggan Baru Terdaftar',
                'description' => "{$user->name} ({$user->email})",
                'timestamp'   => $user->created_at,
            ]);

        // Order selesai
        $completedOrders = Order::where('status', 'completed')
            ->latest('updated_at')
            ->limit(4)
            ->get(['id', 'total_price', 'updated_at'])
            ->map(fn ($order) => [
                'type'        => 'order_completed',
                'title'       => 'Order Selesai',
                'description' => 'Order #' . $order->id . ' - Rp' . number_format($order->total_price, 0, ',', '.'),
                'timestamp'   => $order->updated_at,
            ]);

        // Emergency masuk
        $emergencies = Emergency::with('user:id,name')
            ->latest('requested_at')
            ->limit(4)
            ->get(['id', 'user_id', 'status', 'requested_at'])
            ->map(fn ($emergency) => [
                'type'        => 'emergency_received',
                'title'       => 'Emergency Baru',
                'description' => ($emergency->user->name ?? 'Unknown') . ' — Status: ' . $emergency->status,
                'timestamp'   => $emergency->requested_at,
            ]);

        return $newCustomers
            ->concat($completedOrders)
            ->concat($emergencies)
            ->sortByDesc('timestamp')
            ->take(4)
            ->values();
    }

    /**
     * e. Order terbaru (Fix N+1 dan kolom name)
     */
    private function getLatestOrders(): \Illuminate\Support\Collection
    {
        return Order::with([
                'mechanic.user:id,name',
                'orderDetails.booking.user:id,name',
                'orderDetails.emergency.user:id,name',
            ])
            ->latest('created_at')
            ->limit(4)
            ->get()
            ->map(function ($order) {
                $firstDetail  = $order->orderDetails->first();
                $customerName = 'Unknown';

                if ($firstDetail) {
                    $customerName = match ($firstDetail->service_type) {
                        'booking'   => $firstDetail->booking?->user?->name ?? 'Unknown',
                        'emergency' => $firstDetail->emergency?->user?->name ?? 'Unknown',
                        default     => 'Unknown',
                    };
                }

                return [
                    'id'            => $order->id,
                    'customer_name' => $customerName,
                    'status'        => $order->status,
                    'created_at'    => $order->created_at,
                ];
            });
    }
}