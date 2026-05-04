<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Phase 2.5a — payment transaction resource per /PHASE2_CONTRACT.md §4.4.
 *
 * In 2.5a only method='cash_at_center' rows exist. The gateway_*
 * fields are deliberately exposed (null) so the contract is stable
 * for the gateway integration shipping in Phase 4+.
 */
class PaymentTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'method'          => $this->method,
            'status'          => $this->status,
            'amount'          => round((float) $this->amount, 2),
            'gateway_txn_id'  => $this->gateway_txn_id,
            'paid_at'         => optional($this->paid_at)->toISOString(),
            'refunded_at'     => optional($this->refunded_at)->toISOString(),
            'refunded_amount' => $this->refunded_amount !== null
                ? round((float) $this->refunded_amount, 2)
                : null,
            'created_at'      => optional($this->created_at)->toISOString(),
        ];
    }
}
