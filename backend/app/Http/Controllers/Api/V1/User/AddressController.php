<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\AddressResource;
use App\Models\Address;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * /api/v1/user/addresses (auth:sanctum) — Phase 2.2 CRUD.
 *
 * Per /PHASE2_CONTRACT.md §5.2. Scoped to the authenticated user;
 * never accepts user_id from the request. Route-model-bound rows are
 * 404'd (not 403'd) when owned by another user — we don't leak
 * existence.
 *
 * "Exactly one default per user" is enforced inside a transaction:
 * any save with is_default=true clears the flag on the user's other
 * addresses first. The first address ever created for a user is
 * automatically promoted to default regardless of input. On destroy,
 * if the deleted row was the default and others remain, the
 * most-recent surviving row is promoted.
 */
class AddressController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $rows = $request->user()
            ->addresses()
            ->orderByDesc('is_default')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'addresses' => AddressResource::collection($rows),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validatePayload($request, creating: true);
        $user      = $request->user();

        $address = DB::transaction(function () use ($user, $validated) {
            $hasExisting   = $user->addresses()->exists();
            $wantsDefault  = (bool) ($validated['is_default'] ?? false);
            $forceDefault  = !$hasExisting || $wantsDefault;

            $address = $user->addresses()->create([
                'label'      => $validated['label']    ?? 'Home',
                'line1'      => $validated['line1'],
                'line2'      => $validated['line2']    ?? null,
                'city'       => $validated['city'],
                'state'      => $validated['state'],
                'pincode'    => $validated['pincode'],
                'landmark'   => $validated['landmark'] ?? null,
                'is_default' => $forceDefault,
            ]);

            if ($forceDefault) {
                $this->demoteOthers($user->id, $address->id);
            }

            return $address;
        });

        return response()->json(['address' => new AddressResource($address)]);
    }

    public function update(Request $request, Address $address): JsonResponse
    {
        if ($address->user_id !== $request->user()->id) {
            abort(Response::HTTP_NOT_FOUND);
        }

        $validated = $this->validatePayload($request, creating: false);

        if (empty($validated)) {
            return response()->json(['message' => 'No fields to update'], 422);
        }

        DB::transaction(function () use ($address, $validated) {
            foreach (['label', 'line1', 'line2', 'city', 'state', 'pincode', 'landmark'] as $f) {
                if (array_key_exists($f, $validated)) {
                    $address->{$f} = $validated[$f];
                }
            }

            $promotingToDefault = array_key_exists('is_default', $validated)
                && $validated['is_default'] === true
                && !$address->is_default;

            if (array_key_exists('is_default', $validated)) {
                // Cannot demote the only default to false: invariant says
                // a user with ≥1 address always has exactly one default.
                if ($validated['is_default'] === false && $address->is_default) {
                    // Silently ignored — the flip happens by promoting another row.
                } else {
                    $address->is_default = $validated['is_default'];
                }
            }

            $address->save();

            if ($promotingToDefault) {
                $this->demoteOthers($address->user_id, $address->id);
            }
        });

        return response()->json(['address' => new AddressResource($address->refresh())]);
    }

    public function destroy(Request $request, Address $address): JsonResponse
    {
        if ($address->user_id !== $request->user()->id) {
            abort(Response::HTTP_NOT_FOUND);
        }

        DB::transaction(function () use ($address) {
            $wasDefault = (bool) $address->is_default;
            $userId     = $address->user_id;

            $address->delete();

            if ($wasDefault) {
                $promote = Address::query()
                    ->where('user_id', $userId)
                    ->orderByDesc('created_at')
                    ->first();
                if ($promote) {
                    $promote->is_default = true;
                    $promote->save();
                }
            }
        });

        return response()->json(['success' => true]);
    }

    /** Clear is_default on every other row for this user. */
    private function demoteOthers(int $userId, int $keepId): void
    {
        Address::query()
            ->where('user_id', $userId)
            ->where('id', '!=', $keepId)
            ->where('is_default', true)
            ->update(['is_default' => false]);
    }

    /**
     * Shared validation. On create the four address-line fields are
     * required; on update everything is optional (PATCH-style).
     */
    private function validatePayload(Request $request, bool $creating): array
    {
        $req      = fn (string ...$rules) => $creating ? array_merge(['required'], $rules) : array_merge(['sometimes'], $rules);
        $optional = fn (string ...$rules) => array_merge(['sometimes'], $rules);

        return $request->validate([
            'label'      => $optional('string', 'max:50'),
            'line1'      => $req('string', 'max:255'),
            'line2'      => $optional('nullable', 'string', 'max:255'),
            'city'       => $req('string', 'max:80'),
            'state'      => $req('string', 'max:80'),
            'pincode'    => $req('string', 'regex:/^\d{6}$/'),
            'landmark'   => $optional('nullable', 'string', 'max:255'),
            'is_default' => $optional('boolean'),
        ]);
    }
}
