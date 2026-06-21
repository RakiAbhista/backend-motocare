<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\NOrderService;
use App\Models\Service;
use App\Models\Vehicle;
use App\Models\Workshop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class BookingController extends Controller
{
    /**
     * Get user's vehicles for booking
     */
    public function getVehicles()
    {
        $vehicles = Vehicle::where('user_id', Auth::id())
            ->select('id', 'brand', 'model', 'manufacturing_year', 'plate_number', 'vehicle_type')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $vehicles
        ], 200);
    }

    /**
     * Get all services
     */
    public function getServices()
    {
        $services = Service::select('id', 'service_name', 'base_price')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $services
        ], 200);
    }

    /**
     * Get nearest workshops
     */
    public function getWorkshops(Request $request)
    {
        $workshops = Workshop::select('id', 'name', 'latitude', 'longitude')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $workshops
        ], 200);
    }

    /**
     * Create booking and order
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'workshop_id' => 'required|exists:workshops,id',
            'service_ids' => 'required|array|min:1',
            'service_ids.*' => 'exists:services,id',
            'complaint' => 'required|string|max:500',
            'damage_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120', // 5MB
            'booking_date' => 'required|date|after:today',
            'is_towing' => 'boolean'
        ]);

        // Set default value for is_towing
        $validated['is_towing'] = $validated['is_towing'] ?? false;

        try {
            return DB::transaction(function () use ($validated, $request) {
                $userId = Auth::id();

                // Verify vehicle belongs to user
                $vehicle = Vehicle::where('id', $validated['vehicle_id'])
                    ->where('user_id', $userId)
                    ->firstOrFail();

                // Handle photo upload to Supabase storage (damage_photo/booking/{filename})
                $photoPath = null;
                $photoUrl = null;
                if ($request->hasFile('damage_photo')) {
                    $file = $request->file('damage_photo');

                    $origName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    $ext = $file->getClientOriginalExtension();
                    $safeName = Str::slug($origName) ?: 'photo';
                    $filename = time() . '_' . $safeName . '.' . $ext;
                    $storagePath = 'damage_photo/booking/' . $filename;

                    $supabaseUrl = rtrim(env('SUPABASE_URL'), '/');
                    $bucket = env('SUPABASE_STORAGE_BUCKET');
                    $serviceKey = env('SUPABASE_SERVICE_KEY');

                    if (! $supabaseUrl || ! $bucket || ! $serviceKey) {
                        throw new \Exception('Supabase storage not configured.');
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
                        throw new \Exception('Gagal mengupload file ke Supabase: ' . $response->body());
                    }

                    $photoPath = $storagePath;
                    $photoUrl = $supabaseUrl . '/storage/v1/object/public/' . $bucket . '/' . $photoPath;
                }

                // Create booking
                $booking = Booking::create([
                    'user_id' => $userId,
                    'vehicle_id' => $validated['vehicle_id'],
                    'workshop_id' => $validated['workshop_id'],
                    'complaint' => $validated['complaint'],
                    'damage_photo' => $photoPath,
                    'booking_date' => $validated['booking_date']
                ]);

                // Calculate total price from services
                $services = Service::whereIn('id', $validated['service_ids'])->get();
                $totalPrice = $services->sum('base_price');

                // Create order
                $order = Order::create([
                    'mechanic_id' => null,
                    'total_price' => $totalPrice,
                    'is_towing' => $validated['is_towing'] ? 'yes' : 'no',
                    'status' => 'pending',
                    'payment_status' => 'pending',
                    'payment_url' => null
                ]);

                // Create order detail with polymorphic relationship
                OrderDetail::create([
                    'order_id'     => $order->id,
                    'service_type' => 'booking',   // sesuai $fillable
                    'reference_id' => $booking->id,      // sesuai $fillable
                    'price'        => $totalPrice
                ]);

                // Create n_order_services for each selected service
                foreach ($validated['service_ids'] as $serviceId) {
                    $service = Service::find($serviceId);
                    NOrderService::create([
                        'order_id' => $order->id,
                        'service_id' => $serviceId,
                        'price' => $service->base_price
                    ]);
                }

                // Load relationships for response
                $order->load([
                    'orderDetails',
                    'orderServices.service'
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Booking berhasil dibuat',
                    'data' => [
                        'order_id' => $order->id,
                        'booking_id' => $booking->id,
                        'status' => $order->status,
                        'vehicle' => [
                            'brand' => $vehicle->brand,
                            'model' => $vehicle->model,
                            'manufacturing_year' => $vehicle->manufacturing_year,
                            'plate_number' => $vehicle->plate_number
                        ],
                        'workshop' => $order->workshop,
                        'services' => $order->orderServices->map(fn($os) => [
                            'id' => $os->service->id,
                            'service_name' => $os->service->service_name,
                            'base_price' => $os->service->base_price
                        ]),
                        'complaint' => $booking->complaint,
                        'damage_photo' => $photoUrl ?? null,
                        'total_price' => $order->total_price,
                        'is_towing' => $order->is_towing,
                        'booking_date' => $booking->booking_date
                    ]
                ], 201);
            });
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Vehicle not found or you don\'t have access'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create booking: ' . $e->getMessage(),
                'trace'   => $e->getTraceAsString()
            ], 500);
        }
    }

    /**
     * Get booking summary before confirmation
     */
    public function getSummary(Request $request)
    {
        $validated = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'workshop_id' => 'required|exists:workshops,id',
            'service_ids' => 'required|array|min:1',
            'service_ids.*' => 'exists:services,id',
            'is_towing' => 'nullable|boolean'
        ]);

        // Set default value for is_towing if not provided
        if (!isset($validated['is_towing'])) {
            $validated['is_towing'] = false;
        }

        $userId = Auth::id();

        // Get vehicle
        $vehicle = Vehicle::where('id', $validated['vehicle_id'])
            ->where('user_id', $userId)
            ->select('id', 'brand', 'model', 'manufacturing_year', 'plate_number', 'vehicle_type')
            ->firstOrFail();

        // Get workshop
        $workshop = Workshop::findOrFail($validated['workshop_id']);

        // Get services
        $services = Service::whereIn('id', $validated['service_ids'])
            ->select('id', 'service_name', 'base_price')
            ->get();

        $totalPrice = $services->sum('base_price');

        return response()->json([
            'success' => true,
            'data' => [
                'vehicle' => $vehicle,
                'workshop' => $workshop,
                'services' => $services,
                'total_price' => $totalPrice,
                'is_towing' => $validated['is_towing'],
                'service_count' => $services->count()
            ]
        ], 200);
    }
}
