<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PageResource;
use App\Models\Page;
use Illuminate\Http\JsonResponse;

class PageController extends Controller
{
    /**
     * GET /api/v1/pages/{slug}
     */
    public function show(string $slug): JsonResponse
    {
        $page = Page::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->first();

        if ($page) {
            // Eager-load active sections under the `sections` accessor that
            // PageResource expects.
            $page->setRelation('sections', $page->activeSections()->get());
        }

        if (!$page) {
            return response()->json([
                'success' => false,
                'message' => "Page '{$slug}' not found.",
            ], 404);
        }

        return response()->json([
            'success' => true,
            'page'    => new PageResource($page),
            'seo'     => [
                'title'       => $page->seo_title ?? $page->title,
                'description' => $page->seo_description,
                'keywords'    => $page->seo_keywords,
            ],
        ]);
    }
}
