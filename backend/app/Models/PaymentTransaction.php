<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Phase 2.5a — payment_transactions per /PHASE2_CONTRACT.md §3.
 *
 * Phase 2.5a writes one row per order at placement time with
 * method='cash_at_center', status='pending', amount=order.total.
 * Real gateway integration (Razorpay / UPI) lands in Phase 4+ via
 * the gateway_txn_id / gateway_response columns.
 */
class PaymentTransaction extends Model
{
    use HasFactory;

    public const METHOD_CASH_AT_CENTER = 'cash_at_center';
    public const METHOD_UPI            = 'upi';
    public const METHOD_CARD           = 'card';
    public const METHOD_WALLET         = 'wallet';
    public const METHOD_OTHER          = 'other';

    public const STATUS_PENDING   = 'pending';
    public const STATUS_SUCCEEDED = 'succeeded';
    public const STATUS_FAILED    = 'failed';
    public const STATUS_REFUNDED  = 'refunded';

    protected $fillable = [
        'order_id',
        'method',
        'status',
        'amount',
        'gateway_txn_id',
        'gateway_response',
        'paid_at',
        'refunded_at',
        'refunded_amount',
    ];

    protected $casts = [
        'amount'           => 'decimal:2',
        'refunded_amount'  => 'decimal:2',
        'gateway_response' => 'array',
        'paid_at'          => 'datetime',
        'refunded_at'      => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
