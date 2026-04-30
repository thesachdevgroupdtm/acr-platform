<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Front\HomeController;
use App\Http\Controllers\Front\ServiceController;
use App\Http\Controllers\Front\ServiceCenterConroller;
use App\Http\Controllers\Front\offerController;
use App\Http\Controllers\Front\FaqController;
use App\Http\Controllers\Front\CmsPagesController;
use App\Http\Controllers\Front\ProductController;
use App\Http\Controllers\Front\ContactController;
use App\Http\Controllers\Front\OtpController;
use App\Http\Controllers\Front\SearchController;
use App\Http\Controllers\Front\CartController;
use App\Http\Controllers\Front\CheckoutController;
use App\Http\Controllers\Front\OrderController;
use App\Http\Controllers\Front\UserController;
use App\Http\Controllers\Front\Auth\LoginController;
use App\Http\Controllers\Front\Auth\RegisterController;

/*
|--------------------------------------------------------------------------
| API Routes (consumed by the React frontend)
|--------------------------------------------------------------------------
|
| Each route below points at a *Api sibling method on an EXISTING
| Front\* controller. The legacy web flow (routes/web.php → Blade views)
| is unaffected.
|
| Auth: Sanctum personal access tokens (Bearer). Send:
|   Authorization: Bearer {token}
|
*/

Route::prefix('v1')->group(function () {

    /* ── Public read-only ───────────────────────────────────────── */
    Route::get('home',                                  [HomeController::class, 'indexApi']);

    Route::get('service-categories',                    [ServiceController::class, 'categoriesApi']);
    Route::get('services',                              [ServiceController::class, 'servicesApi']);
    Route::get('services/{categorySlug}',               [ServiceController::class, 'categoryDetailApi']);
    Route::get('services/{categorySlug}/{serviceSlug}', [ServiceController::class, 'serviceDetailApi']);

    Route::get('service-centers',                       [ServiceCenterConroller::class, 'indexApi']);
    Route::get('service-centers/{id}',                  [ServiceCenterConroller::class, 'showApi'])->whereNumber('id');

    Route::get('offers',                                [offerController::class, 'indexApi']);
    Route::get('faqs',                                  [FaqController::class, 'indexApi']);

    Route::get('products',                              [ProductController::class, 'accessoriesApi']);
    Route::get('products/{slug}',                       [ProductController::class, 'detailApi']);

    Route::get('cms/about-us',                          [CmsPagesController::class, 'aboutUsApi']);
    Route::get('cms/page/{slug}',                       [CmsPagesController::class, 'pageApi']);
    Route::get('cms/company/{slug}',                    [CmsPagesController::class, 'companyPageApi']);

    Route::get('contact',                               [ContactController::class, 'indexApi']);

    /* ── Vehicle picker + global search ─────────────────────────── */
    Route::get('search/brands',                         [SearchController::class, 'brandsApi']);
    Route::get('search/models',                         [SearchController::class, 'modelsApi']);
    Route::get('search/fuels',                          [SearchController::class, 'fuelsApi']);
    Route::get('search/vehicle-summary',                [SearchController::class, 'vehicleSummaryApi']);
    Route::get('search',                                [SearchController::class, 'searchApi']);

    /* ── Public form posts (rate-limited) ───────────────────────── */
    Route::middleware('throttle:10,1')->group(function () {
        Route::post('contact',                          [ContactController::class, 'submitApi']);
        Route::post('appointment',                      [ContactController::class, 'appointmentApi']);
    });

    /* ── OTP (rate-limited) ─────────────────────────────────────── */
    Route::middleware('throttle:5,1')->group(function () {
        Route::post('otp/send',                         [OtpController::class, 'sendApi']);
        Route::post('otp/verify',                       [OtpController::class, 'verifyApi']);
        Route::post('otp/resend',                       [OtpController::class, 'resendApi']);
    });

    /* ── Auth (open) ────────────────────────────────────────────── */
    Route::middleware('throttle:10,1')->group(function () {
        Route::post('auth/register',                    [RegisterController::class, 'registerApi']);
        Route::post('auth/login',                       [LoginController::class, 'loginApi']);
        Route::post('auth/forgot-password',             [LoginController::class, 'forgotApi']);
        Route::post('auth/reset-password',              [LoginController::class, 'resetApi']);
    });

    /* ── Authenticated (Sanctum bearer token) ───────────────────── */
    Route::middleware('auth:sanctum')->group(function () {

        // Auth
        Route::get('auth/me',                           [LoginController::class, 'meApi']);
        Route::post('auth/logout',                      [LoginController::class, 'logoutApi']);
        Route::put('auth/password',                     [LoginController::class, 'changePasswordApi']);

        // Service booking helpers
        Route::post('services/book-now',                [ServiceController::class, 'bookNowApi']);

        // Cart
        Route::get('cart',                              [CartController::class, 'indexApi']);
        Route::post('cart',                             [CartController::class, 'addApi']);
        Route::put('cart',                              [CartController::class, 'updateApi']);
        Route::delete('cart',                           [CartController::class, 'removeApi']);
        Route::get('cart/count',                        [CartController::class, 'countApi']);
        Route::post('cart/sync',                        [CartController::class, 'syncApi']);

        // Checkout
        Route::get('checkout/summary',                  [CheckoutController::class, 'summaryApi']);
        Route::post('checkout/available-slots',         [CheckoutController::class, 'availableSlotsApi']);
        Route::post('checkout/offline',                 [CheckoutController::class, 'createOfflineApi']);
        Route::post('checkout/online',                  [CheckoutController::class, 'createOnlineApi']);

        // Orders / bookings
        Route::get('orders',                            [OrderController::class, 'listApi']);
        Route::get('orders/{id}',                       [OrderController::class, 'showApi'])->whereNumber('id');
        Route::post('orders/{id}/cancel',               [OrderController::class, 'cancelApi'])->whereNumber('id');
        Route::post('orders/{id}/reschedule',           [OrderController::class, 'rescheduleApi'])->whereNumber('id');

        // User profile + addresses
        Route::get('user/profile',                      [UserController::class, 'profileApi']);
        Route::get('user/addresses',                    [UserController::class, 'addressListApi']);
        Route::post('user/addresses',                   [UserController::class, 'addressStoreApi']);
        Route::put('user/addresses/{id}',               [UserController::class, 'addressUpdateApi'])->whereNumber('id');
        Route::delete('user/addresses/{id}',            [UserController::class, 'addressDeleteApi'])->whereNumber('id');
    });
});

// Sanctum default user route — kept for compatibility
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
