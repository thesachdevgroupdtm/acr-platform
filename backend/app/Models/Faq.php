<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Phase 4.5d — Frequently-Asked-Questions row.
 *
 * Powers two surfaces:
 *   1. /api/v1/faqs       — public read for any future page that wants
 *                            the operator-managed FAQ list.
 *   2. SchemaTemplateEngine::faqPage() — emits an FAQPage JSON-LD
 *                            from active rows ordered by sort_order.
 */
class Faq extends Model
{
    use HasFactory;

    protected $fillable = [
        'question',
        'answer',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    /**
     * Canonical ordering for FAQ list output — admins control display
     * order via sort_order, with id as a deterministic tiebreaker.
     */
    public function scopeOrdered(Builder $q): Builder
    {
        return $q->orderBy('sort_order')->orderBy('id');
    }
}
