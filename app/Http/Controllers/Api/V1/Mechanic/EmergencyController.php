<?php

namespace App\Http\Controllers\Api\V1\Mechanic;

use App\Http\Controllers\Controller;
use App\Models\Emergency;
use App\Models\Order;
use App\Models\NOrderService;
use App\Models\Service;
use App\Models\Mechanic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class EmergencyController extends Controller
{
    /**
     * Function 1 — List semua order emergency yang di-assign ke mechanic login
     */
    public function index()
    {
        $mechanic = Mechanic::where('user_id', Auth::id())->first();
        if (!$mechanic) {
            return response()->json([
                'success' => true,
                'data'    => [],
            ]);
        }
        $mechanicId = $mechanic->id;

        $emergencies = Emergency::whereHas('order', function ($query) {
                $query->whereIn('status', ['pending', 'process', 'payment']);
            })
            ->with([
                'user:id,name,phone_number',
                'vehicle:id,brand,model,plate_number',
                'order',
            ])
            ->where('mechanic_id', $mechanicId)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($e) => $this->formatEmergency($e));

        return response()->json([
            'success' => true,
            'data'    => $emergencies,
        ]);
    }

    public function history()
    {
        $mechanic = Mechanic::where('user_id', Auth::id())->first();
        if (!$mechanic) {
            return response()->json([
                'success' => true,
                'data'    => [],
            ]);
        }
        $mechanicId = $mechanic->id;

        $emergencies = Emergency::whereHas('order', function ($query) {
                $query->whereIn('status', ['completed', 'canceled', 'cancelled']);
            })
            ->with([
                'user:id,name,phone_number',
                'vehicle:id,brand,model,plate_number',
                'order',
            ])
            ->where('mechanic_id', $mechanicId)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($e) => $this->formatEmergency($e));

        return response()->json([
            'success' => true,
            'data'    => $emergencies,
        ]);
    }

    /**
     * Function 2 — Terima panggilan: pending → dispatched
     */
    public function accept($emergencyId)
    {
        $mechanic = Mechanic::where('user_id', Auth::id())->first();
        if (!$mechanic) {
            return response()->json([
                'success' => false,
                'message' => 'Mekanik tidak terdaftar',
            ], 404);
        }
        $mechanicId = $mechanic->id;

        $emergency = Emergency::where('id', $emergencyId)
            ->where('mechanic_id', $mechanicId)
            ->where('status', 'pending')
            ->first();

        if (!$emergency) {
            return response()->json([
                'success' => false,
                'message' => 'Emergency tidak ditemukan atau status bukan pending',
            ], 404);
        }

        DB::transaction(function () use ($emergency, $mechanic) {
            $emergency->update(['status' => 'dispatched']);
        });

        return response()->json([
            'success' => true,
            'message' => 'Panggilan diterima, status berubah ke dispatched',
            'data'    => $this->formatEmergency($emergency->fresh()),
        ]);
    }

    /**
     * Function 3 — Detail order emergency
     */
    public function show($emergencyId)
    {
        $mechanic = Mechanic::where('user_id', Auth::id())->first();
        if (!$mechanic) {
            return response()->json([
                'success' => false,
                'message' => 'Mekanik tidak terdaftar',
            ], 404);
        }
        $mechanicId = $mechanic->id;

        $emergency = Emergency::with([
                'user:id,name,phone_number',
                'vehicle:id,brand,model,plate_number,vehicle_type',
                'order.orderServices.service',
            ])
            ->where('id', $emergencyId)
            ->where('mechanic_id', $mechanicId)
            ->first();

        if (!$emergency) {
            return response()->json([
                'success' => false,
                'message' => 'Emergency tidak ditemukan',
            ], 404);
        }

        // Build damage photo URL (Supabase public if configured, otherwise local storage)
        $damagePhotoUrl = null;
        if ($emergency->damage_photo) {
            $supabaseUrl = rtrim(env('SUPABASE_URL') ?? '', '/');
            $bucket = env('SUPABASE_STORAGE_BUCKET');
            if ($supabaseUrl && $bucket) {
                $damagePhotoUrl = $supabaseUrl . '/storage/v1/object/public/' . $bucket . '/' . $emergency->damage_photo;
            } else {
                $damagePhotoUrl = url('storage/' . $emergency->damage_photo);
            }
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'emergency_id'  => $emergency->id,
                'status'        => $emergency->status,
                'client'        => [
                    'name'  => $emergency->user->name ?? null,
                    'phone' => $emergency->user->phone_number ?? null,
                ],
                'vehicle'       => ($emergency->vehicle || $emergency->vehicle_brand) ? [
                    'brand'        => $emergency->vehicle ? $emergency->vehicle->brand : $emergency->vehicle_brand,
                    'model'        => $emergency->vehicle ? $emergency->vehicle->model : $emergency->vehicle_model,
                    'plate_number' => $emergency->vehicle ? $emergency->vehicle->plate_number : $emergency->plate_number,
                    'vehicle_type' => $emergency->vehicle ? $emergency->vehicle->vehicle_type : $emergency->vehicle_type,
                ] : null,
                'damage_photo'  => $damagePhotoUrl,
                'location'      => [
                    'latitude'  => $emergency->latitude,
                    'longitude' => $emergency->longitude,
                ],
                'order'         => $emergency->order ? [
                    'order_id'       => $emergency->order->id,
                    'status'         => $emergency->order->status,
                    'payment_status' => $emergency->order->payment_status,
                    'is_towing'      => $emergency->order->is_towing,
                    'total_price'    => $emergency->order->total_price,
                    'services'       => $emergency->order->orderServices->map(fn($os) => [
                        'id'                 => $os->id,
                        'service_id'         => $os->service_id,
                        'service_name'       => $os->service->service_name ?? $os->additional_service,
                        'price'              => $os->price,
                        'is_additional'      => is_null($os->service_id),
                    ]),
                ] : null,
            ],
        ]);
    }

    /**
     * Function 4 — Sudah sampai: order pending → process
     */
    public function arrived($emergencyId)
    {
        $mechanic = Mechanic::where('user_id', Auth::id())->first();
        if (!$mechanic) {
            return response()->json([
                'success' => false,
                'message' => 'Mekanik tidak terdaftar',
            ], 404);
        }
        $mechanicId = $mechanic->id;

        $emergency = Emergency::with('order')
            ->where('id', $emergencyId)
            ->where('mechanic_id', $mechanicId)
            ->where('status', 'dispatched')
            ->first();

        if (!$emergency || !$emergency->order) {
            return response()->json([
                'success' => false,
                'message' => 'Emergency tidak ditemukan, status bukan dispatched, atau order tidak ada',
            ], 404);
        }

        $emergency->order->update(['status' => 'process']);

        return response()->json([
            'success' => true,
            'message' => 'Mekanik sudah sampai, status order berubah ke process',
            'data'    => [
                'order_id'     => $emergency->order->id,
                'order_status' => 'process',
            ],
        ]);
    }

    /**
     * Function 5 — Ajukan towing: is_towing no → yes
     */
    public function requestTowing($emergencyId)
    {
        $mechanic = Mechanic::where('user_id', Auth::id())->first();
        if (!$mechanic) {
            return response()->json([
                'success' => false,
                'message' => 'Mekanik tidak terdaftar',
            ], 404);
        }
        $mechanicId = $mechanic->id;

        $emergency = Emergency::with('order')
            ->where('id', $emergencyId)
            ->where('mechanic_id', $mechanicId)
            ->first();

        if (!$emergency || !$emergency->order) {
            return response()->json([
                'success' => false,
                'message' => 'Emergency atau order tidak ditemukan',
            ], 404);
        }

        if ($emergency->order->is_towing === 'yes') {
            return response()->json([
                'success' => false,
                'message' => 'Towing sudah diajukan sebelumnya',
            ], 422);
        }

        $emergency->order->update(['is_towing' => 'yes']);

        return response()->json([
            'success' => true,
            'message' => 'Towing berhasil diajukan',
            'data'    => [
                'order_id'  => $emergency->order->id,
                'is_towing' => 'yes',
            ],
        ]);
    }

    /**
     * Function 6 — Tambah detail servis (pilih existing atau tambah manual)
     */
    public function addService(Request $request, $emergencyId)
    {
        $request->validate([
            'service_id'         => 'nullable|exists:services,id',
            'additional_service' => 'nullable|string|max:255',
            'price'              => 'required_without:service_id|nullable|numeric|min:0',
        ]);

        // Harus salah satu
        if (!$request->service_id && !$request->additional_service) {
            return response()->json([
                'success' => false,
                'message' => 'Pilih service yang ada atau isi nama additional service',
            ], 422);
        }

        $mechanic = Mechanic::where('user_id', Auth::id())->first();
        if (!$mechanic) {
            return response()->json([
                'success' => false,
                'message' => 'Mekanik tidak terdaftar',
            ], 404);
        }
        $mechanicId = $mechanic->id;

        $emergency = Emergency::with('order')
            ->where('id', $emergencyId)
            ->where('mechanic_id', $mechanicId)
            ->first();

        if (!$emergency || !$emergency->order) {
            return response()->json([
                'success' => false,
                'message' => 'Emergency atau order tidak ditemukan',
            ], 404);
        }

        DB::transaction(function () use ($request, $emergency) {
            $price = $request->price;

            // Jika pakai service_id, ambil base_price jika price tidak diisi
            if ($request->service_id && !$price) {
                $service = Service::find($request->service_id);
                $price   = $service->base_price;
            }

            NOrderService::create([
                'order_id'           => $emergency->order->id,
                'service_id'         => $request->service_id,
                'additional_service' => $request->additional_service,
                'price'              => $price,
            ]);

            // Update total_price di order
            $total = NOrderService::where('order_id', $emergency->order->id)->sum('price');
            $emergency->order->update(['total_price' => $total]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Servis berhasil ditambahkan',
            'data'    => [
                'order_id'    => $emergency->order->id,
                'total_price' => $emergency->order->fresh()->total_price,
                'services'    => NOrderService::with('service')
                    ->where('order_id', $emergency->order->id)
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
     * Function 6.5 — Remove service from order
     */
    public function removeService(Request $request, $emergencyId, $serviceId)
    {
        $mechanic = Mechanic::where('user_id', Auth::id())->first();
        if (!$mechanic) {
            return response()->json([
                'success' => false,
                'message' => 'Mekanik tidak terdaftar',
            ], 404);
        }
        $mechanicId = $mechanic->id;

        $emergency = Emergency::with('order')
            ->where('id', $emergencyId)
            ->where('mechanic_id', $mechanicId)
            ->first();

        if (!$emergency || !$emergency->order) {
            return response()->json([
                'success' => false,
                'message' => 'Emergency atau order tidak ditemukan',
            ], 404);
        }

        DB::transaction(function () use ($emergency, $serviceId) {
            NOrderService::where('id', $serviceId)->where('order_id', $emergency->order->id)->delete();

            // Update total_price di order
            $total = NOrderService::where('order_id', $emergency->order->id)->sum('price');
            $emergency->order->update(['total_price' => $total]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Servis berhasil dihapus',
            'data'    => [
                'order_id'    => $emergency->order->id,
                'total_price' => $emergency->order->fresh()->total_price,
            ],
        ]);
    }

    /**
     * Function 7 — Get list services (untuk dropdown)
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
     * Function 8 — Total harga berdasarkan n_order_services
     */
    public function getTotal($emergencyId)
    {
        $mechanic = Mechanic::where('user_id', Auth::id())->first();
        if (!$mechanic) {
            return response()->json([
                'success' => false,
                'message' => 'Mekanik tidak terdaftar',
            ], 404);
        }
        $mechanicId = $mechanic->id;

        $emergency = Emergency::with([
                'order.orderServices.service',
            ])
            ->where('id', $emergencyId)
            ->where('mechanic_id', $mechanicId)
            ->first();

        if (!$emergency || !$emergency->order) {
            return response()->json([
                'success' => false,
                'message' => 'Emergency atau order tidak ditemukan',
            ], 404);
        }

        $services = $emergency->order->orderServices->map(fn($os) => [
            'id'            => $os->id,
            'service_id'    => $os->service_id,
            'service_name'  => $os->service->service_name ?? $os->additional_service,
            'price'         => $os->price,
            'is_additional' => is_null($os->service_id),
        ]);

        $total = $services->sum('price');

        return response()->json([
            'success' => true,
            'data'    => [
                'order_id'    => $emergency->order->id,
                'services'    => $services,
                'total_price' => $total,
                'is_towing'   => $emergency->order->is_towing,
            ],
        ]);
    }

    /**
     * Function 9 — Lanjut pembayaran: order process → payment, emergency dispatched → resolved
     */
    public function proceedToPayment($emergencyId)
    {
        $mechanic = Mechanic::where('user_id', Auth::id())->first();
        if (!$mechanic) {
            return response()->json([
                'success' => false,
                'message' => 'Mekanik tidak terdaftar',
            ], 404);
        }
        $mechanicId = $mechanic->id;

        $emergency = Emergency::with('order')
            ->where('id', $emergencyId)
            ->where('mechanic_id', $mechanicId)
            ->first();

        if (!$emergency || !$emergency->order) {
            return response()->json([
                'success' => false,
                'message' => 'Emergency atau order tidak ditemukan',
            ], 404);
        }

        if ($emergency->order->status !== 'process') {
            return response()->json([
                'success' => false,
                'message' => 'Status order harus process untuk lanjut ke payment',
            ], 422);
        }

        DB::transaction(function () use ($emergency, $mechanic) {
            // Hitung ulang total sebelum payment
            $total = NOrderService::where('order_id', $emergency->order->id)->sum('price');

            $emergency->order->update([
                'status'      => 'payment',
                'total_price' => $total,
            ]);

            $emergency->update(['status' => 'resolved']);
            $mechanic->update(['status' => 'available']);
        });

        return response()->json([
            'success' => true,
            'message' => 'Lanjut ke pembayaran',
            'data'    => [
                'order_id'          => $emergency->order->id,
                'order_status'      => 'payment',
                'emergency_status'  => 'resolved',
                'total_price'       => $emergency->order->fresh()->total_price,
            ],
        ]);
    }

    /**
     * Function 10 — Selesaikan pembayaran: upload bukti, order payment → completed
     */
    public function completePayment(Request $request, $emergencyId)
    {
        $request->validate([
            'payment_proof' => 'required|image|mimes:jpeg,png,jpg|max:5120',
            'payment_type'  => 'required|string|in:cash,transfer',
        ]);

        $mechanic = Mechanic::where('user_id', Auth::id())->first();
        if (!$mechanic) {
            return response()->json([
                'success' => false,
                'message' => 'Mekanik tidak terdaftar',
            ], 404);
        }
        $mechanicId = $mechanic->id;

        $emergency = Emergency::with('order')
            ->where('id', $emergencyId)
            ->where('mechanic_id', $mechanicId)
            ->first();

        if (!$emergency || !$emergency->order) {
            return response()->json([
                'success' => false,
                'message' => 'Emergency atau order tidak ditemukan',
            ], 404);
        }

        if ($emergency->order->status !== 'payment') {
            return response()->json([
                'success' => false,
                'message' => 'Status order harus payment untuk diselesaikan',
            ], 422);
        }

        // Upload payment proof to Supabase under payment_proofs/{DD/MM/YYYY}/filename
        $photoPath = null;
        $photoUrl = null;

        if ($request->hasFile('payment_proof')) {
            $file = $request->file('payment_proof');

            $origName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $ext = $file->getClientOriginalExtension();
            $safeName = Str::slug($origName) ?: 'proof';
            $filename = time() . '_' . $safeName . '.' . $ext;

            $dateFolder = date('d/m/Y');
            $storagePath = 'payment_proofs/' . $dateFolder . '/' . $filename;

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

            $photoPath = $storagePath;
            $photoUrl = $supabaseUrl . '/storage/v1/object/public/' . $bucket . '/' . $photoPath;
        }

        $emergency->order->update([
            'status'         => 'completed',
            'payment_status' => 'settlement',
            'payment_type'   => $request->payment_type,
            'payment_url'    => $photoUrl ? $photoUrl : null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pembayaran selesai, order completed',
            'data'    => [
                'order_id'       => $emergency->order->id,
                'order_status'   => 'completed',
                'payment_status' => 'settlement',
                'payment_type'   => $request->payment_type,
                'payment_proof'  => $photoUrl ?? null,
                'total_price'    => $emergency->order->total_price,
            ],
        ]);
    }

    /**
     * Function 11 — Batalkan emergency (order fiktif): order → cancelled, emergency → cancelled
     */
    public function cancel($emergencyId)
    {
        $mechanic = Mechanic::where('user_id', Auth::id())->first();
        if (!$mechanic) {
            return response()->json([
                'success' => false,
                'message' => 'Mekanik tidak terdaftar',
            ], 404);
        }
        $mechanicId = $mechanic->id;

        $emergency = Emergency::with('order')
            ->where('id', $emergencyId)
            ->where('mechanic_id', $mechanicId)
            ->first();

        if (!$emergency || !$emergency->order) {
            return response()->json([
                'success' => false,
                'message' => 'Emergency atau order tidak ditemukan',
            ], 404);
        }

        // Hanya bisa cancel jika status order masih pending atau process
        if (!in_array($emergency->order->status, ['pending', 'process'])) {
            return response()->json([
                'success' => false,
                'message' => 'Order tidak dapat dibatalkan karena status sudah ' . $emergency->order->status,
            ], 422);
        }

        DB::transaction(function () use ($emergency, $mechanic) {
            // Hapus semua service terkait order ini
            NOrderService::where('order_id', $emergency->order->id)->delete();

            // Update status order
            $emergency->order->update([
                'status'      => 'cancelled',
                'total_price' => 0,
            ]);

            // Update status emergency
            $emergency->update(['status' => 'cancelled']);

            // Mekanik kembali available
            $mechanic->update(['status' => 'available']);
        });

        return response()->json([
            'success' => true,
            'message' => 'Order berhasil dibatalkan',
            'data'    => [
                'order_id'         => $emergency->order->id,
                'order_status'     => 'cancelled',
                'emergency_status' => 'cancelled',
            ],
        ]);
    }

    // ── Helper ───────────────────────────────────────────────────────────────

    private function formatEmergency(Emergency $e): array
    {
        return [
            'emergency_id' => $e->id,
            'status'       => $e->status,
            'client'       => [
                'name'  => $e->user->name  ?? null,
                'phone' => $e->user->phone_number ?? null,
            ],
            'vehicle'      => ($e->vehicle || $e->vehicle_brand) ? [
                'brand'        => $e->vehicle ? $e->vehicle->brand : $e->vehicle_brand,
                'model'        => $e->vehicle ? $e->vehicle->model : $e->vehicle_model,
                'plate_number' => $e->vehicle ? $e->vehicle->plate_number : $e->plate_number,
            ] : null,
            'location'     => [
                'latitude'  => $e->latitude,
                'longitude' => $e->longitude,
            ],
            'order_status'    => $e->order->status        ?? null,
            'payment_status'  => $e->order->payment_status ?? null,
            'total_price'     => $e->order->total_price    ?? null,
            'created_at'      => $e->created_at,
        ];
    }
}