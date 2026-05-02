<?php

use App\Http\Controllers\Api\V1\Auth\LeadCaptureController;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\SendOtpController;
use App\Http\Controllers\Api\V1\Auth\VerifyOtpController;
use App\Http\Controllers\Api\V1\HomeController;
use App\Http\Controllers\Api\V1\ImportController;
use App\Http\Controllers\Api\V1\PageController;
use App\Http\Controllers\Api\V1\PricingController;
use App\Http\Controllers\Api\V1\ServiceController;
use App\Http\Controllers\Api\V1\User\ProfileController;
use App\Http\Controllers\Api\V1\VehicleController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — v1
|--------------------------------------------------------------------------
| Consumed by the React frontend at http://localhost:3000.
*/

Route::prefix('v1')->group(function () {

    // Home payload
    Route::get('home', [HomeController::class, 'index']);

    // Service categories + services
    Route::get('services',                                  [ServiceController::class, 'index']);
    Route::get('services/{slug}',                           [ServiceController::class, 'show']);
    Route::get('services/{categorySlug}/{serviceSlug}',     [ServiceController::class, 'detail']);

    // Vehicle picker
    Route::get('vehicle/brands',                            [VehicleController::class, 'brands']);
    Route::get('vehicle/models',                            [VehicleController::class, 'models']);
    Route::get('vehicle/fuels',                             [VehicleController::class, 'fuels']);

    // Frontend currently calls /search/* — alias to the same handlers.
    Route::get('search/brands',                             [VehicleController::class, 'brands']);
    Route::get('search/models',                             [VehicleController::class, 'models']);
    Route::get('search/fuels',                              [VehicleController::class, 'fuels']);

    // Pricing
    Route::post('pricing',                                  [PricingController::class, 'quote']);

    // CMS pages
    Route::get('pages/{slug}',                              [PageController::class, 'show']);

    // CSV import (admin-only, bearer-token gated)
    Route::middleware('import.token')->prefix('import')->group(function () {
        Route::post('car-brands',     [ImportController::class, 'carBrands']);
        Route::post('car-models',     [ImportController::class, 'carModels']);
        Route::post('fuel-types',     [ImportController::class, 'fuelTypes']);
        Route::post('service-prices', [ImportController::class, 'servicePrices']);
    });

    // Phase 2.1 — Auth + OTP (per /PHASE2_CONTRACT.md §5.1).
    Route::post('auth/lead-capture', LeadCaptureController::class)->middleware('throttle:auth-public');
    Route::post('auth/send-otp',     SendOtpController::class)->middleware('throttle:auth-public');
    Route::post('auth/verify-otp',   VerifyOtpController::class)->middleware('throttle:auth-verify');
    Route::post('auth/login',        LoginController::class)->middleware('throttle:auth-public');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('auth/logout',  LogoutController::class)->middleware('throttle:user-write');
        Route::get('user/profile',  [ProfileController::class, 'show'])->middleware('throttle:user-read');
        Route::put('user/profile',  [ProfileController::class, 'update'])->middleware('throttle:user-write');
    });
});
