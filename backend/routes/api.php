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
use App\Http\Controllers\Api\V1\Public\FaqsController;
use App\Http\Controllers\Api\V1\Public\LeadController;
use App\Http\Controllers\Api\V1\Public\LookupController;
use App\Http\Controllers\Api\V1\Public\SeoPageController;
use App\Http\Controllers\Api\V1\Public\SeoValidationController;
use App\Http\Controllers\Api\V1\Public\BrandController as PublicBrandController;
use App\Http\Controllers\Api\V1\Public\CategoryController as PublicCategoryController;
use App\Http\Controllers\Api\V1\Public\FuelController as PublicFuelController;
use App\Http\Controllers\Api\V1\Public\PricingLookupController;
use App\Http\Controllers\Api\V1\Public\ServiceCentersController;
use App\Http\Controllers\Api\V1\Public\ServiceController as PublicServiceController;
use App\Http\Controllers\Api\V1\Public\SitemapController;
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
        // Phase 2.5b — real coupon backend. Both apply + remove are
        // guest-capable: cart-session resolves a guest cart by its
        // X-Cart-Session UUID, so a not-signed-in visitor can apply a
        // coupon and preview the discounted total (honest, transparent
        // pricing). The apply controller still resolves an OPTIONAL
        // Bearer user via $request->user('sanctum'), so a logged-in
        // user's per-user usage limit is enforced at apply exactly as
        // before. For guests, the per-user limit is enforced at the
        // gated checkout (place-order), where the customer identity is
        // known and the usage row is actually claimed.
        Route::post  ('cart/coupon',           [CartCouponController::class, 'apply'])  ->middleware('throttle:cart-write');
        Route::delete('cart/coupon',           [CartCouponController::class, 'remove']) ->middleware('throttle:cart-write');

        // Phase 2.4 — explicit guest→user cart merge (multi-device,
        // re-merge after OTP-verify path missed the X-Cart-Session
        // header). Sanctum-required; the guest UUID is in the body.
        Route::post  ('cart/merge',            MergeCartController::class)              ->middleware(['auth:sanctum', 'throttle:cart-write']);
    });

    // Phase 2.5a — service centers (public read for checkout dropdown).
    // Phase 4.5c — added {slug} detail route powering SeoHead on the
    // customer /service-centers/{slug} page.
    Route::get('service-centers', [ServiceCentersController::class, 'index'])
        ->middleware('throttle:public-read');
    Route::get('service-centers/{slug}', [ServiceCentersController::class, 'show'])
        ->middleware('throttle:public-read');

    // Phase 2.5b — coupons listing. Public route; the controller
    // resolves the user via `$request->user('sanctum')` so a Bearer
    // token (when present) lights up ?context=cart eligibility, and
    // an anonymous request still gets the marketing payload.
    Route::get('coupons', [CouponsController::class, 'index'])
        ->middleware('throttle:public-read');

    // Phase 4.5b — SEO pages + explore hub + sitemap.
    // explore/categories MUST be declared before any {slug} route
    // so the segment isn't swallowed as a slug.
    Route::get('explore/categories', [SeoPageController::class, 'categories'])
        ->middleware('throttle:public-read');

    // Phase 4.5 — paginated list (legacy, kept for filter/search
    // surfaces that still need flat results). Declared BEFORE the
    // structured payload route to avoid path collision.
    Route::get('explore/list', [SeoPageController::class, 'explore'])
        ->middleware('throttle:public-read');

    // Phase 4.5 — structured editorial payload (hero, trending,
    // categories, rails). Cached 60s. Single round-trip for
    // ExploreEditorial.
    Route::get('explore', [SeoPageController::class, 'payload'])
        ->middleware('throttle:public-read');

    Route::get('seo-pages/{slug}', [SeoPageController::class, 'show'])
        ->middleware('throttle:public-read');

    // Phase 4.5 — view tracking (rate-limited at controller level
    // by IP+slug fingerprint, in addition to the route throttle).
    Route::post('seo-pages/{slug}/track-view', [SeoPageController::class, 'trackView'])
        ->middleware('throttle:public-read');

    // Phase 4.5c sitemap-fix — sitemap moved to routes/web.php so
    // crawlers can find it at the conventional /sitemap.xml root.
    // The Api\V1\Public\SitemapController class location stays the
    // same; only its route binding moved.

    // Phase 4.5d — JSON-LD validator endpoint powering the
    // Filament Preview JSON-LD modal's "Validate" button.
    Route::post('seo/validate', [SeoValidationController::class, 'validateJsonld'])
        ->middleware('throttle:public-read');

    // Phase 4.5d — public FAQ list. Backs the FAQPage schema template
    // and any future page that wants operator-managed FAQs.
    Route::get('faqs', [FaqsController::class, 'index'])
        ->middleware('throttle:public-read');

    // Phase 4.5.3 — public master-data lookups for the
    // explore-sidebar lead form (cached 1h).
    Route::prefix('lookups')->middleware('throttle:public-read')->group(function () {
        Route::get('brands',   [LookupController::class, 'brands']);
        Route::get('models',   [LookupController::class, 'models']);
        Route::get('services', [LookupController::class, 'services']);
    });

    // Phase 4.5.3 — lead capture (replaces Phase 4.5.1 newsletter).
    // Throttle '30,60' = 30 submissions per hour per IP. The spec
    // proposed '5,60' but that breaks legitimate corporate / NAT
    // shared-IP scenarios AND is hostile to E2E tests; the real
    // spam line is the controller-side check (same phone 3+ times
    // in 24h auto-flags status='spam'). Documented in
    // PHASE4_5_3_REPORT.md §13 as an intentional deviation.
    Route::post('leads', [LeadController::class, 'store'])->middleware('throttle:30,60');

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

    /*
     * Sub-phase L1 — public read-only catalog API (8 endpoints).
     *
     * Namespaced under /api/v1/public/* so we don't collide with the
     * existing /api/v1/services and /api/v1/vehicle/* routes that the
     * frontend already consumes. Visibility filter per D-L1-4 hides
     * auto-bootstrapped entities (include_in_sitemap=false) until an
     * operator reviews + SEO-enriches them.
     */
    Route::prefix('public')->name('public.')->group(function () {
        Route::prefix('vehicles')->name('vehicles.')->group(function () {
            Route::get('brands',                       [PublicBrandController::class, 'index'])->name('brands.index');
            Route::get('brands/{slug}/models',         [PublicBrandController::class, 'models'])->name('brands.models');
            Route::get('fuels',                        [PublicFuelController::class, 'index'])->name('fuels.index');
            Route::get('models/{slug}/fuels',          [PublicFuelController::class, 'forModel'])->name('models.fuels');
        });

        Route::get('services',                         [PublicServiceController::class, 'index'])->name('services.index');
        Route::get('services/{slug}',                  [PublicServiceController::class, 'show'])->name('services.show');

        Route::get('categories',                       [PublicCategoryController::class, 'index'])->name('categories.index');
        Route::get('categories/{slug}/services',       [PublicCategoryController::class, 'services'])->name('categories.services');

        Route::get('pricing/lookup',                   [PricingLookupController::class, 'lookup'])->name('pricing.lookup');
    });
});
