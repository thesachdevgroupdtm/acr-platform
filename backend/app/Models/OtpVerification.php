<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Phase 2.1 — OTP verification ledger.
 *
 * - `otp_code` is stored as sha256 hash; verify by hashing the
 *   incoming code and comparing.
 * - `otp_code` is `$hidden` so it never appears in JSON responses
 *   even if a model accidentally gets serialised.
 * - For the `OTP_DEV_BYPASS` audit trail (Decision D-C), the
 *   sentinel string `'BYPASS'` is stored in `otp_code` instead of
 *   a hash, so audits can distinguish bypass-verified rows from
 *   real OTP-verified rows.
 *
 * Per /PHASE2_CONTRACT.md §3.
 */
class OtpVerification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'channel',
        'destination',
        'otp_code',
        'expires_at',
        'verified_at',
        'attempts',
        'ip',
    ];

    protected $hidden = [
        'otp_code',
    ];

    protected $casts = [
        'expires_at'  => 'datetime',
        'verified_at' => 'datetime',
        'attempts'    => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
