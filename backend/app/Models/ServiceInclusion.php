<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Service-pages redesign Phase 1 (D-P1-1) — a single "what's included"
 * line item under a Service (e.g. "Engine Oil Replacement"), with an
 * optional thumbnail and a `position` for ordering.
 *
 * CleansOldImage keeps thumbnail overwrites tidy (same overwrite-cleanup
 * behaviour as Service / ServiceCategory).
 */
class ServiceInclusion extends Model
{
    use HasFactory;
    use \App\Models\Concerns\CleansOldImage;

    protected $fillable = [
        'service_id',
        'label',
        'group_name',
        'image',
        'position',
    ];

    protected $casts = [
        'position' => 'integer',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id');
    }
}
