<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Services\SeoValidationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Phase 4.5d — JSON-LD validation endpoint.
 *
 *   POST /api/v1/seo/validate
 *   { "jsonld": "{\"@context\":\"https://schema.org\",...}" }
 *
 * Always returns HTTP 200 with a structured body so the admin
 * Preview JSON-LD modal can render errors / warnings / info
 * inline. Invalid JSON returns valid=false with the parser
 * error in `errors[]`, not an HTTP 422.
 */
class SeoValidationController extends Controller
{
    /**
     * Method named `validateJsonld` (not `validate`) so it doesn't
     * collide with Illuminate\Foundation\Validation\ValidatesRequests::validate()
     * that the base Controller class inherits.
     */
    public function validateJsonld(Request $request, SeoValidationService $validator): JsonResponse
    {
        $request->validate([
            'jsonld' => 'required|string',
        ]);

        $result = $validator->validate($request->input('jsonld'));

        return response()->json($result);
    }
}
