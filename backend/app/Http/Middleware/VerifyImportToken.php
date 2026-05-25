<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bearer-token gate for CSV import endpoints.
 *
 * Compare via hash_equals to avoid timing leaks. The expected token is
 * read from config('import.token'), populated by .env IMPORT_API_TOKEN.
 */
class VerifyImportToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('import.token');
        if ($expected === '' || $expected === 'dev-import-token-change-me' && app()->environment('production')) {
            return response()->json([
                'success' => false,
                'message' => 'Import token not configured for this environment.',
            ], 500);
        }

        $header = (string) $request->bearerToken();
        if ($header === '' || !hash_equals($expected, $header)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or missing import token.',
            ], 401);
        }

        return $next($request);
    }
}
