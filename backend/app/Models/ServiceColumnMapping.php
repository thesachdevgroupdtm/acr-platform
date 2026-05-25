<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Phase 4.3 — persistent excel-column → service_id mapping. Layer 2
 * of the four-layer column resolution strategy (D-4.3-2).
 *
 * service_id is nullable so the operator can also store explicit
 * "ignore this column" decisions (active row with service_id=null).
 */
class ServiceColumnMapping extends Model
{
    use HasFactory;

    protected $fillable = [
        'excel_column',
        'service_id',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }
}
