<?php

use App\Http\Controllers\Api\V1\Admin\DashboardController;
use App\Http\Controllers\Api\V1\Admin\OrderController;
use App\Http\Controllers\Api\V1\Admin\ServiceController;
use App\Http\Controllers\Api\V1\Admin\UserController;
use App\Http\Controllers\Api\V1\Admin\VehicleController as AdminVehicleController;
use App\Http\Controllers\Api\V1\Admin\VoucherController;
use App\Http\Controllers\Api\V1\Admin\WorkshopController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CS\ProfileController as CSProfileController;
use App\Http\Controllers\Api\V1\CS\DashboardController as CSDashboardController;
use App\Http\Controllers\Api\V1\CS\OrderController as CSOrderController;
use App\Http\Controllers\Api\V1\CS\EmergencyController as CSEmergencyController;
use App\Http\Controllers\Api\V1\Customer\CustomerProfileController;
use App\Http\Controllers\Api\V1\Customer\HomeController;
use App\Http\Controllers\Api\V1\Customer\OrderController as CustomerOrderController;
use App\Http\Controllers\Api\V1\Customer\VehicleController as CustomerVehicleController;
use App\Http\Controllers\Api\V1\CustomerService\ServiceDetailController;
use App\Http\Controllers\Api\V1\Mechanic\DashboardController as MechanicDashboardController;
use App\Http\Controllers\Api\V1\Mechanic\EmergencyController;
use App\Http\Controllers\Api\V1\Mechanic\MechanicProfileController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // --- PUBLIC ROUTES (No Auth) ---
    Route::get('services', [PublicServiceController::class, 'index']);
    Route::get('workshops', [PublicWorkshopController::class, 'index']);
    Route::get('workshops/{id}', [PublicWorkshopController::class, 'show']);
    Route::get('workshops/nearby', [PublicWorkshopController::class, 'nearby']);

    // --- AUTHENTICATION ---
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
        Route::post('/reset-password', [AuthController::class, 'resetPassword']);

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::get('/me', function (Request $request) {
                return $request->user();
            });
        });
    });

    // --- PROTECTED ROUTES BY ROLE ---
    Route::middleware(['auth:sanctum'])->group(function () {
        
        // =====================================================
        // ADMIN ROUTES
        // =====================================================
        Route::middleware('role:admin')->prefix('admin')->group(function () {
            // Dashboard Management
            Route::get('dashboard', [DashboardController::class, 'index']);

            // Users Management
            Route::apiResource('users', UserController::class);
            
            // Vehicles Management (Read & Delete only)
            Route::get('vehicles', [AdminVehicleController::class, 'index']);
            Route::get('vehicles/{vehicle}', [AdminVehicleController::class, 'show']);
            Route::delete('vehicles/{vehicle}', [AdminVehicleController::class, 'destroy']);
            
            // Services Management
            Route::apiResource('services', ServiceController::class);
            
            // Workshops Management
            Route::apiResource('workshops', WorkshopController::class);
            
            // Vouchers Management
            Route::apiResource('vouchers', VoucherController::class);
            
            // Orders Management (Read & Delete only)
            Route::get('orders', [OrderController::class, 'index']);
            Route::get('orders/{order}', [OrderController::class, 'show']);
            Route::delete('orders/{order}', [OrderController::class, 'destroy']);
        });
       
        // Contoh Route Khusus CS
        Route::middleware('role:customer_service')->prefix('customer-service')->group(function () {
            
            // Dashboard ( Beranda )
            Route::get('dashboard', [CSDashboardController::class, 'index']);

            // Users Management
            Route::get('/profile', [CSProfileController::class, 'show']);
            Route::put('/profile', [CSProfileController::class, 'update']);

            // ORDER
            Route::post('orders/find-vehicle', [CSOrderController::class, 'findVehicle']);
            Route::get('orders', [CSOrderController::class, 'index']);
            Route::get('orders/{id}', [CSOrderController::class, 'show']);
            Route::post('orders', [CSOrderController::class, 'store']);
            Route::delete('orders/{order}', [CSOrderController::class, 'destroy']);
            
            // Emergency
            Route::get('/emergencies',              [CSEmergencyController::class, 'index']);
            Route::get('/emergencies/{id}',         [CSEmergencyController::class, 'show']);
            Route::put('/emergencies/{id}/assign',  [CSEmergencyController::class, 'assignMechanic']);
            Route::put('/emergencies/{id}/status',  [CSEmergencyController::class, 'updateStatus']);

            // Helper: list mekanik tersedia
            Route::get('/mechanics',                [CSEmergencyController::class, 'availableMechanics']);

        });

        // Contoh Route Khusus Mechanic
        Route::middleware('role:mechanic')->prefix('mechanic')->group(function () {
            // Dashboard
            Route::get('dashboard', [MechanicDashboardController::class, 'index']);
            Route::post('status', [MechanicDashboardController::class, 'updateStatus']);
            
            // Emergencies
            Route::get('emergencies', [EmergencyController::class, 'index']);
            Route::get('emergencies/{id}', [EmergencyController::class, 'show']);
            Route::post('emergencies/{id}/accept', [EmergencyController::class, 'accept']);
            Route::post('emergencies/{id}/invoice', [EmergencyController::class, 'createInvoice']);

            // Profile
            Route::get('profile', [MechanicProfileController::class, 'show']);
            Route::put('profile', [MechanicProfileController::class, 'update']);
        });

        // Contoh Route Khusus Customer
        Route::middleware('role:customer')->prefix('customer')->group(function () {
            
            // Endpoint Beranda Utama
            Route::get('/home', [HomeController::class, 'index']);

            // Endpoint Kendaraan Customer
            Route::get('/vehicles', [CustomerVehicleController::class, 'index']);
            Route::post('/vehicles', [CustomerVehicleController::class, 'store']);
            Route::put('/vehicles/{id}', [CustomerVehicleController::class, 'update']);
            Route::delete('/vehicles/{id}', [CustomerVehicleController::class, 'destroy']);

            // Endpoint Booking Service
            Route::get('/booking/vehicles', [BookingController::class, 'getVehicles']);
            Route::get('/booking/services', [BookingController::class, 'getServices']);
            Route::get('/booking/workshops', [BookingController::class, 'getWorkshops']);
            Route::post('/booking/summary', [BookingController::class, 'getSummary']);
            Route::post('/booking', [BookingController::class, 'store']);

            // Endpoint Riwayat Pesanan Customer
            Route::get('/orders', [CustomerOrderController::class, 'index']);
            Route::get('/orders/{id}', [CustomerOrderController::class, 'show']);
            Route::post('/orders/{id}/cancel', [CustomerOrderController::class, 'cancel']);

            // Endpoint Booking
            Route::get('/bookings', [BookingController::class, 'index']);
            Route::post('/bookings', [BookingController::class, 'store']);
            Route::get('/bookings/{id}', [BookingController::class, 'show']);
            Route::delete('/bookings/{id}', [BookingController::class, 'destroy']);

            // Endpoint Emergency
            Route::get('/emergencies', [CustomerEmergencyController::class, 'index']);
            Route::post('/emergencies', [CustomerEmergencyController::class, 'store']);
            Route::get('/emergencies/{id}', [CustomerEmergencyController::class, 'show']);
            Route::post('/emergencies/{id}/cancel', [CustomerEmergencyController::class, 'cancel']);

            // Endpoint Voucher (Read Only)
            Route::get('/vouchers', [CustomerVoucherController::class, 'index']);
            Route::post('/vouchers/validate', [CustomerVoucherController::class, 'validate']);

            // Endpoint Profil Customer
            Route::get('/profile', [CustomerProfileController::class, 'show']);
            Route::put('/profile', [CustomerProfileController::class, 'update']);
            Route::put('/profile/password', [CustomerProfileController::class, 'updatePassword']);
            
        });
    });

});