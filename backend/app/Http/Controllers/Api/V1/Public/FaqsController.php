<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Http\JsonResponse;

/**
 * Phase 4.5d — public read of the operator-managed FAQ list.
 *
 *   GET /api/v1/faqs
 *
 * Powers any future page that wants to render FAQs without a
 * hardcoded fallback (currently `HomeFAQ.tsx` still uses its
 * hardcoded HOME_FAQS array; swapping it is intentionally
 * deferred per PHASE4_5D_AUDIT.md).
 */
class FaqsController extends Controller
{
    public function index(): JsonResponse
    {
        $faqs = Faq::query()->active()->ordered()->get();

        return response()->json([
            'faqs' => $faqs->map(fn (Faq $f) => [
                'id'         => $f->id,
                'question'   => $f->question,
                'answer'     => $f->answer,
                'sort_order' => $f->sort_order,
            ])->toArray(),
        ]);
    }
}
