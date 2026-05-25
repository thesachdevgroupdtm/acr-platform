<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Phase 4.5.3 — Lead capture from the explore-sidebar form.
 *
 * Status lifecycle managed by operator in Filament:
 *   new → contacted → converted   (success path)
 *   new → spam                    (auto-flagged or operator action)
 *
 * FK columns use nullOnDelete so removing a brand / model / service
 * in admin doesn't void the historical lead record.
 */
class Lead extends Model
{
    use HasFactory;

    public const STATUSES = ['new', 'contacted', 'converted', 'spam'];

    protected $fillable = [
        'name',
        'email',
        'phone',
        'brand_id',
        'model_id',
        'service_id',
        'source',
        'status',
        'notes',
        'ip_address',
    ];

    public function brand(): BelongsTo
    {
        return $this->belongsTo(CarBrand::class, 'brand_id');
    }

    public function model(): BelongsTo
    {
        return $this->belongsTo(CarModel::class, 'model_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public function scopeRecent(Builder $query): Builder
    {
        return $query->where('created_at', '>=', now()->subDays(30));
    }

    public function scopeStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }
}
