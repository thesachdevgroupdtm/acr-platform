<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EnquirySecurityMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Additional IP-based rate limiting (more strict than route throttle)
        if ($this->isRateLimited($request)) {
            Log::warning('Rate limit exceeded', ['ip' => $request->ip()]);
            return response()->json([
                'success' => false,
                'message' => 'Too many requests. Please try again later.'
            ], 429);
        }

        // Block known spam IPs (you can maintain a blacklist)
        if ($this->isBlockedIP($request)) {
            Log::warning('Blocked IP attempted access', ['ip' => $request->ip()]);
            return response()->json([
                'success' => false,
                'message' => 'Access denied.'
            ], 403);
        }

        return $next($request);
    }

    private function isRateLimited(Request $request): bool
    {
        $ip = $request->ip();
        $timeWindow = now()->subMinutes(5); // 5-minute window
        
        // Count submissions from this IP in the last 5 minutes
        $recentSubmissions = DB::table('enquires')
            ->where('ip_address', $ip)
            ->where('created_at', '>=', $timeWindow)
            ->count();

        // Allow maximum 3 submissions per IP in 5 minutes
        return $recentSubmissions >= 3;
    }

    private function isBlockedIP(Request $request): bool
    {
        $blockedIPs = [
            // Add known spam IPs here
            // '192.168.1.100',
            // '10.0.0.50',
        ];

        return in_array($request->ip(), $blockedIPs);
    }
}
