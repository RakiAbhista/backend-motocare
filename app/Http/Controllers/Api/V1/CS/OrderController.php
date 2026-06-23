<?php

namespace App\Http\Controllers\Api\V1\CS;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\NOrderService;
use App\Models\Vehicle;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::with([
                'orderDetails.source.workshop',
                'orderDetails.source.vehicle',
                'orderDetails.source.user',
                'orderServices.service',
            ])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($order) {
                $detail = $order->orderDetails->first();
                $source = $detail?->source;
                $order->setRelation('workshop', $source?->workshop);
                $order->setRelation('vehicle', $source?->vehicle);
                $order->setRelation('user', $source?->user);
                $order->setRelation('services', $order->orderServices);
                unset($order->orderDetails, $order->orderServices);
                return $order;
            });

        return response()->json([
            'success' => true,
            'message' => 'Riwayat pesanan berhasil diambil',
            'data'    => $orders
        ], 200);
    }

    public function show($id)
    {
        $order = Order::where('id', $id)
            ->with([
                'orderDetails.source.workshop',
                'orderDetails.source.vehicle',
                'orderDetails.source.user',
                'orderServices.service',
            ])
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Pesanan tidak ditemukan'
            ], 404);
        }

        $detail = $order->orderDetails->first();
        $source = $detail?->source;
        $order->setRelation('workshop', $source?->workshop);
        $order->setRelation('vehicle', $source?->vehicle);
        $order->setRelation('user', $source?->user);
        $order->setRelation('services', $order->orderServices);
        unset($order->orderDetails, $order->orderServices);

        return response()->json([
            'success' => true,
            'message' => 'Detail pesanan berhasil diambil',
            'data'    => $order
        ], 200);
    }

    public function findVehicle(Request $request)
    {
        $request->validate([
            'plate_number' => 'required|string'
        ]);

        $vehicle = Vehicle::with('user')
            ->where('plate_number', $request->plate_number)
            ->first();

        if (!$vehicle) {
            return response()->json([
                'success' => false,
                'message' => 'Kendaraan tidak ditemukan',
                'data'    => null
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Kendaraan ditemukan',
            'data'    => $vehicle
        ], 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id'            => 'required|exists:users,id',
            'vehicle_id'         => 'required|exists:vehicles,id',
            'workshop_id'        => 'required|exists:workshops,id',
            'service_id'         => 'required|exists:services,id',
            'complaint'          => 'required|string',
            'damage_photo'       => 'nullable|string',
            'additional_service' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $lastOrder = Order::whereNotNull('scheduled_at')
                ->orderBy('scheduled_at', 'desc')
                ->first();

            $scheduledAt = $lastOrder && $lastOrder->scheduled_at
                ? \Carbon\Carbon::parse($lastOrder->scheduled_at)->addMinutes(45)
                : now();

            $service = Service::findOrFail($request->service_id);

            $booking = Booking::create([
                'user_id'      => $request->user_id,
                'vehicle_id'   => $request->vehicle_id,
                'workshop_id'  => $request->workshop_id,
                'service_id'   => $request->service_id,
                'complaint'    => $request->complaint,
                'damage_photo' => $request->damage_photo,
                'booking_date' => $scheduledAt,
            ]);

            $order = Order::create([
                'mechanic_id'    => null,
                'voucher_id'     => null,
                'status'         => 'process',
                'payment_status' => 'pending',
                'total_price'    => $service->base_price,
                'scheduled_at'   => $scheduledAt,
            ]);

            OrderDetail::create([
                'order_id'     => $order->id,
                'service_type' => 'booking',
                'reference_id' => $booking->id,
                'price'        => $service->base_price,
            ]);

            NOrderService::create([
                'order_id'           => $order->id,
                'service_id'         => $request->service_id,
                'additional_service' => $request->additional_service,
                'price'              => $service->base_price,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order berhasil dibuat',
                'data'    => $order->load([
                    'orderDetails.source.workshop',
                    'orderDetails.source.vehicle',
                    'orderDetails.source.user',
                    'orderServices.service',
                ])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat order',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get list of available services (untuk dropdown tambah service)
     */
    public function getServices()
    {
        $services = Service::select('id', 'service_name', 'base_price')
            ->orderBy('service_name')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $services,
        ]);
    }

    /**
     * Get total harga dan daftar service berdasarkan order
     */
    public function getTotal($id)
    {
        $order = Order::with('orderServices.service')->find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order tidak ditemukan',
            ], 404);
        }

        $services = $order->orderServices->map(fn($os) => [
            'id'            => $os->id,
            'service_id'    => $os->service_id,
            'service_name'  => $os->service->service_name ?? $os->additional_service,
            'price'         => $os->price,
            'is_additional' => is_null($os->service_id),
        ]);

        $total = $services->sum('price');
        // Generate 2-digit unique suffix for payment validation
        $uniqueSuffix = random_int(0, 99);
        $totalWithSuffix = $total + $uniqueSuffix;

        return response()->json([
            'success' => true,
            'data'    => [
                'order_id'         => $order->id,
                'services'         => $services,
                'unique_suffix'    => str_pad((string)$uniqueSuffix, 2, '0', STR_PAD_LEFT),
                'total_price'      => $totalWithSuffix,
                'is_towing'        => $order->is_towing,
            ],
        ]);
    }

    /**
     * Tambah service ke order (pilih existing atau manual)
     */
    public function addService(Request $request, $id)
    {
        $request->validate([
            'service_id'         => 'nullable|exists:services,id',
            'additional_service' => 'nullable|string|max:255',
            'price'              => 'required_without:service_id|nullable|numeric|min:0',
        ]);

        if (!$request->service_id && !$request->additional_service) {
            return response()->json([
                'success' => false,
                'message' => 'Pilih service yang ada atau isi nama additional service',
            ], 422);
        }

        $order = Order::find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order tidak ditemukan',
            ], 404);
        }

        DB::transaction(function () use ($request, $order) {
            $price = $request->price;

            if ($request->service_id && !$price) {
                $service = Service::find($request->service_id);
                $price   = $service->base_price;
            }

            NOrderService::create([
                'order_id'           => $order->id,
                'service_id'         => $request->service_id,
                'additional_service' => $request->additional_service,
                'price'              => $price,
            ]);

            // Update total_price di order
            $total = NOrderService::where('order_id', $order->id)->sum('price');
            $order->update(['total_price' => $total]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Servis berhasil ditambahkan',
            'data'    => [
                'order_id'    => $order->id,
                'total_price' => $order->fresh()->total_price,
                'services'    => NOrderService::with('service')
                    ->where('order_id', $order->id)
                    ->get()
                    ->map(fn($os) => [
                        'id'                 => $os->id,
                        'service_id'         => $os->service_id,
                        'service_name'       => $os->service->service_name ?? $os->additional_service,
                        'price'              => $os->price,
                        'is_additional'      => is_null($os->service_id),
                    ]),
            ],
        ]);
    }

    /**
     * Hapus service dari order
     */
    public function removeService($id, $serviceId)
    {
        $order = Order::find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order tidak ditemukan',
            ], 404);
        }

        DB::transaction(function () use ($order, $serviceId) {
            NOrderService::where('id', $serviceId)
                ->where('order_id', $order->id)
                ->delete();

            // Update total_price di order
            $total = NOrderService::where('order_id', $order->id)->sum('price');
            $order->update(['total_price' => $total]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Servis berhasil dihapus',
            'data'    => [
                'order_id'    => $order->id,
                'total_price' => $order->fresh()->total_price,
            ],
        ]);
    }

    /**
     * Selesaikan pembayaran: upload bukti, order → completed
     */
    public function completePayment(Request $request, $id)
    {
        $request->validate([
            'payment_proof' => 'required|image|mimes:jpeg,png,jpg|max:5120',
            'payment_type'  => 'required|string|in:cash,transfer',
        ]);

        $order = Order::find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order tidak ditemukan',
            ], 404);
        }

        if ($order->status !== 'payment') {
            return response()->json([
                'success' => false,
                'message' => 'Status order harus payment untuk diselesaikan',
            ], 422);
        }

        // Upload payment proof to Supabase under payment_proofs/{dd-mm-yyyy}/{orderid}_{filename}
        $photoUrl = null;

        if ($request->hasFile('payment_proof')) {
            $file = $request->file('payment_proof');

            $origName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $ext = $file->getClientOriginalExtension();
            $safeName = Str::slug($origName) ?: 'proof';
            $filename = time() . '_' . $safeName . '.' . $ext;

            $dateFolder = date('d-m-Y');
            $storagePath = 'payment_proofs/' . $dateFolder . '/' . $order->id . '_' . $filename;

            $supabaseUrl = rtrim(env('SUPABASE_URL') ?? '', '/');
            $bucket = env('SUPABASE_STORAGE_BUCKET');
            $serviceKey = env('SUPABASE_SERVICE_KEY');

            if (! $supabaseUrl || ! $bucket || ! $serviceKey) {
                return response()->json([
                    'success' => false,
                    'message' => 'Supabase storage not configured',
                ], 500);
            }

            $uploadUrl = $supabaseUrl . '/storage/v1/object/' . $bucket . '/' . $storagePath;
            $fileContents = file_get_contents($file->getRealPath());
            $mime = $file->getMimeType() ?? 'application/octet-stream';

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $serviceKey,
                'apikey' => $serviceKey,
                'Content-Type' => $mime,
            ])->withBody($fileContents, $mime)
              ->put($uploadUrl);

            if (! $response->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal mengupload payment proof: ' . $response->body(),
                ], 500);
            }

            $photoUrl = $supabaseUrl . '/storage/v1/object/public/' . $bucket . '/' . $storagePath;
        }

        $order->update([
            'status'         => 'completed',
            'payment_status' => 'settlement',
            'payment_type'   => $request->payment_type,
            'payment_url'    => $photoUrl,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pembayaran selesai, order completed',
            'data'    => [
                'order_id'       => $order->id,
                'order_status'   => 'completed',
                'payment_status' => 'settlement',
                'payment_type'   => $request->payment_type,
                'payment_proof'  => $photoUrl ?? null,
                'total_price'    => $order->total_price,
            ],
        ]);
    }
}