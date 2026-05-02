<?php

namespace App\Http\Middleware;

use App\Models\Cart;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Phase 2.3 — cart resolution per /PHASE2_CONTRACT.md §8.
 *
 * Resolves the active cart for the request and attaches it to
 * $request->attributes->get('cart'). One of two paths:
 *
 *   1. Bearer token (sanctum) → user-scoped cart, expires in 90 days.
 *   2. X-Cart-Session header carrying a valid UUID → guest cart,
 *      expires in 30 days.
 *
 * Either path firstOrCreates a cart with status='active' so the
 * controller never has to deal with a missing-cart case.
 *
 * If neither identifier is present, returns 400 — clients must
 * supply one. The /cart/merge endpoint (Phase 2.4) will handle the
 * guest-to-user transition.
 */
class CartSession
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard('sanctum')->check()) {
            $user = Auth::guard('sanctum')->user();
            $cart = Cart::firstOrCreate(
                ['user_id' => $user->id, 'status' => 'active'],
                ['expires_at' => now()->addDays(90), 'currency' => 'INR']
            );
        } elseif ($uuid = $request->header('X-Cart-Session')) {
            if (!Str::isUuid($uuid)) {
                return response()->json(
                    ['message' => 'Invalid X-Cart-Session header'],
                    Response::HTTP_BAD_REQUEST
                );
            }
            $cart = Cart::firstOrCreate(
                ['session_uuid' => $uuid, 'status' => 'active'],
                ['expires_at' => now()->addDays(30), 'currency' => 'INR']
            );
        } else {
            return response()->json(
                ['message' => 'Cart session required (auth or X-Cart-Session)'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $request->attributes->set('cart', $cart);

        return $next($request);
    }
}
