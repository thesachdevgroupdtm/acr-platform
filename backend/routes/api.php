<?php

use App\Http\Controllers\Api\V1\Auth\LeadCaptureController;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\SendOtpController;
use App\Http\Controllers\Api\V1\Auth\VerifyOtpController;
use App\Http\Controllers\Api\V1\Cart\CartController;
use App\Http\Controllers\Api\V1\Cart\CartCouponController;
use App\Http\Controllers\Api\V1\Cart\MergeCartController;
use App\Http\Controllers\Api\V1\Checkout\CheckoutController;
use App\Http\Controllers\Api\V1\HomeController;
use App\Http\Controllers\Api\V1\ImportController;
use App\Http\Controllers\Api\V1\PageController;
use App\Http\Controllers\Api\V1\PricingController;
use App\Http\Controllers\Api\V1\Public\CouponsController;
use App\Http\Controllers\Api\V1\Public\ServiceCentersController;
use App\Http\Controllers\Api\V1\ServiceController;
use App\Http\Controllers\Api\V1\User\AddressController;
use App\Http\Controllers\Api\V1\User\OrderController;
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

        // Phase 2.2 — addresses (per /PHASE2_CONTRACT.md §5.2).
        Route::get   ('user/addresses',             [AddressController::class, 'index'])  ->middleware('throttle:user-read');
        Route::post  ('user/addresses',             [AddressController::class, 'store'])  ->middleware('throttle:user-write');
        Route::put   ('user/addresses/{address}',   [AddressController::class, 'update']) ->middleware('throttle:user-write');
        Route::delete('user/addresses/{address}',   [AddressController::class, 'destroy'])->middleware('throttle:user-write');
    });

    // Phase 2.3 — server-authoritative cart (per /PHASE2_CONTRACT.md §5.3).
    // The cart-session middleware accepts EITHER a sanctum token OR an
    // X-Cart-Session UUID header. Phase 2.4 added /cart/merge.
    Route::middleware('cart-session')->group(function () {
        Route::get   ('cart',                  [CartController::class, 'show'])         ->middleware('throttle:user-read');
        Route::post  ('cart/items',            [CartController::class, 'addItem'])      ->middleware('throttle:cart-write');
        Route::put   ('cart/items/{item}',     [CartController::class, 'updateItem'])   ->middleware('throttle:cart-write');
        Route::delete('cart/items/{item}',     [CartController::class, 'removeItem'])   ->middleware('throttle:cart-write');
        // Phase 2.5b — real coupon backend. Apply requires auth so the
        // service can enforce usage_per_user; the auth:sanctum middleware
        // is layered on top of cart-session here. Remove can be guest
        // (a guest cart can still hold an applied coupon).
        Route::post  ('cart/coupon',           [CartCouponController::class, 'apply'])  ->middleware(['auth:sanctum', 'throttle:cart-write']);
        Route::delete('cart/coupon',           [CartCouponController::class, 'remove']) ->middleware('throttle:cart-write');

        // Phase 2.4 — explicit guest→user cart merge (multi-device,
        // re-merge after OTP-verify path missed the X-Cart-Session
        // header). Sanctum-required; the guest UUID is in the body.
        Route::post  ('cart/merge',            MergeCartController::class)              ->middleware(['auth:sanctum', 'throttle:cart-write']);
    });

    // Phase 2.5a — service centers (public read for checkout dropdown).
    Route::get('service-centers', [ServiceCentersController::class, 'index'])
        ->middleware('throttle:public-read');

    // Phase 2.5b — coupons listing. Public route; the controller
    // resolves the user via `$request->user('sanctum')` so a Bearer
    // token (when present) lights up ?context=cart eligibility, and
    // an anonymous request still gets the marketing payload.
    Route::get('coupons', [CouponsController::class, 'index'])
        ->middleware('throttle:public-read');

    // Phase 2.5a — checkout pipeline (auth + cart-session so the
    // user's active cart is auto-attached by middleware).
    Route::middleware(['auth:sanctum', 'cart-session'])->group(function () {
        Route::post('checkout/quote',       [CheckoutController::class, 'quote'])
            ->middleware('throttle:user-write');
        Route::post('checkout/place-order', [CheckoutController::class, 'placeOrder'])
            ->middleware('throttle:user-write');
    });

    // Phase 2.5a — orders (auth required; cart-session not needed).
    Route::middleware('auth:sanctum')->group(function () {
        Route::get ('user/orders',                  [OrderController::class, 'index'])
            ->middleware('throttle:user-read');
        Route::get ('user/orders/{order}',          [OrderController::class, 'show'])
            ->middleware('throttle:user-read');
        Route::post('user/orders/{order}/cancel',   [OrderController::class, 'cancel'])
            ->middleware('throttle:user-write');
    });
});
