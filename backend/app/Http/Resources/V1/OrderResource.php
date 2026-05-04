<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Phase 2.5a — order resource per /PHASE2_CONTRACT.md §4.4.
 *
 * Caller should eager-load: items.service, items.brand, items.carModel,
 * items.fuel, serviceCenter, payments. Missing eager loads degrade
 * gracefully — items still render from snapshots.
 *
 * is_high_risk is intentionally OMITTED from the user-facing response
 * (D-2.5a-8). Phase 4 admin tooling reads it directly from the DB.
 */
class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'order_number'   => $this->order_number,
            'status'         => $this->status,
            'payment_status' => $this->payment_status,

            'name_snapshot'  => $this->name_snapshot,
            'phone_snapshot' => $this->phone_snapshot,
            'email_snapshot' => $this->email_snapshot,
            'address'        => $this->address,
            'notes'          => $this->notes,

            'vehicle_snapshot' => $this->vehicle_snapshot ?? [],
            'preferred_date'   => optional($this->preferred_date)->format('Y-m-d'),
            'preferred_time'   => $this->preferred_time,

            'service_center' => $this->whenLoaded(
                'serviceCenter',
                fn () => $this->serviceCenter ? new ServiceCenterResource($this->serviceCenter) : null,
                null
            ),

            'items'    => OrderItemResource::collection($this->whenLoaded('items')),
            'payments' => PaymentTransactionResource::collection($this->whenLoaded('payments')),

            'totals' => [
                'subtotal' => round((float) $this->subtotal, 2),
                'discount' => round((float) $this->discount, 2),
                'tax'      => round((float) $this->tax, 2),
                'total'    => round((float) $this->total, 2),
            ],

            'timestamps' => [
                'placed_at'        => optional($this->placed_at)->toISOString(),
                'confirmed_at'     => optional($this->confirmed_at)->toISOString(),
                'in_service_at'    => optional($this->in_service_at)->toISOString(),
                'completed_at'     => optional($this->completed_at)->toISOString(),
                'cancelled_at'     => optional($this->cancelled_at)->toISOString(),
                'cancelled_reason' => $this->cancelled_reason,
                'created_at'       => optional($this->created_at)->toISOString(),
                'updated_at'       => optional($this->updated_at)->toISOString(),
            ],
        ];
    }
}
