<?php

use App\Http\Controllers\Api\V1\Admin\DashboardController;
use App\Http\Controllers\Api\V1\Admin\OrderController;
use App\Http\Controllers\Api\V1\Admin\ServiceController;
use App\Http\Controllers\Api\V1\Admin\UserController;
use App\Http\Controllers\Api\V1\Admin\VehicleController;
use App\Http\Controllers\Api\V1\Admin\VoucherController;
use App\Http\Controllers\Api\V1\Admin\WorkshopController;
use App\Http\Controllers\Api\V1\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

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
            Route::get('vehicles', [VehicleController::class, 'index']);
            Route::get('vehicles/{vehicle}', [VehicleController::class, 'show']);
            Route::delete('vehicles/{vehicle}', [VehicleController::class, 'destroy']);
            
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
        });

        // Contoh Route Khusus Mechanic
        Route::middleware('role:mechanic')->prefix('mechanic')->group(function () {
        });

        // Contoh Route Khusus Customer
        Route::middleware('role:customer')->prefix('customer')->group(function () {
        });
    });

});