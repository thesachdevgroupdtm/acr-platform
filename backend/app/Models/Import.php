<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Phase 4.3 — audit log row for one Excel import attempt.
 */
class Import extends Model
{
    use HasFactory;

    public const TYPE_BRANDS          = 'brands';
    public const TYPE_MODELS          = 'models';
    public const TYPE_FUEL_TYPES      = 'fuel_types';
    public const TYPE_SERVICES        = 'services';
    public const TYPE_PRICING_MATRIX  = 'pricing_matrix';

    public const STATUS_VALIDATING    = 'validating';
    public const STATUS_PREVIEW_READY = 'preview_ready';
    public const STATUS_COMMITTING    = 'committing';
    public const STATUS_COMPLETED     = 'completed';
    public const STATUS_FAILED        = 'failed';

    protected $fillable = [
        'user_id',
        'import_type',
        'file_name',
        'file_size',
        'file_path',
        'status',
        'rows_total',
        'rows_valid',
        'rows_invalid',
        'rows_skipped',
        'error_summary',
        'committed_at',
    ];

    protected $casts = [
        'error_summary' => 'array',
        'committed_at'  => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
