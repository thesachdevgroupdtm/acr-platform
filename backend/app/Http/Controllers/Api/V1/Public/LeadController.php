<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Models\CarModel;
use App\Models\Lead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Phase 4.5.3 — public lead capture endpoint for the
 * explore-sidebar form (replaces the deleted Newsletter controller).
 *
 * Validation:
 *   name      → required, min 2
 *   email     → optional, valid format
 *   phone     → required, Indian 10-digit (^[6-9]\d{9}$)
 *   brand_id  → optional, exists in car_brands
 *   model_id  → optional, exists in car_models AND must belong to brand
 *   service_id → optional, exists in services
 *
 * Spam protection: 4th+ submission from same phone in last 24h is
 * auto-flagged status='spam'. Endpoint still returns 200 — never tell
 * the bot it was caught.
 */
class LeadController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'       => ['required', 'string', 'min:2', 'max:120'],
            'email'      => ['nullable', 'email:rfc'],
            'phone'      => ['required', 'string', 'regex:/^[6-9]\d{9}$/'],
            'brand_id'   => ['nullable', 'integer', Rule::exists('car_brands', 'id')],
            'model_id'   => ['nullable', 'integer', Rule::exists('car_models', 'id')],
            'service_id' => ['nullable', 'integer', Rule::exists('services', 'id')],
        ], [
            'phone.regex' => 'The phone must be a valid 10-digit Indian mobile number.',
        ]);

        // Brand/model consistency: if both are set, model.brand_id
        // must equal the submitted brand_id.
        if (!empty($data['brand_id']) && !empty($data['model_id'])) {
            $model = CarModel::query()->find($data['model_id']);
            if (!$model || (int) $model->brand_id !== (int) $data['brand_id']) {
                throw ValidationException::withMessages([
                    'model_id' => 'The selected model does not belong to the chosen brand.',
                ]);
            }
        }

        // Spam check: count of leads from this phone in last 24h.
        $recentCount = Lead::query()
            ->where('phone', $data['phone'])
            ->where('created_at', '>=', now()->subHours(24))
            ->count();

        $status = $recentCount >= 3 ? 'spam' : 'new';

        $lead = Lead::create([
            'name'       => $data['name'],
            'email'      => $data['email'] ?? null,
            'phone'      => $data['phone'],
            'brand_id'   => $data['brand_id'] ?? null,
            'model_id'   => $data['model_id'] ?? null,
            'service_id' => $data['service_id'] ?? null,
            'source'     => 'explore_sidebar',
            'status'     => $status,
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'ok'      => true,
            'lead_id' => $lead->id,
        ]);
    }
}
