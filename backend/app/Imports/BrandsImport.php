<?php

namespace App\Imports;

use App\Models\CarBrand;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Phase 4.3 — UPSERT brands by slug (or generated slug from name).
 *
 * Expected Excel columns (heading row, case-insensitive):
 *   name      — required, max 100 chars
 *   slug      — optional; auto-generated from name on create
 *   is_active — optional; "1"/"0"/"true"/"false"/"yes"/"no" (default true)
 *
 * Slug safety (D-4.3-14): existing brands' slugs are NEVER
 * regenerated. Only inserts pick up Str::slug(name) when slug is
 * blank.
 */
class BrandsImport extends BaseImport
{
    /** @var array<string, CarBrand>  lowercase-name → row */
    protected array $bySlug = [];

    public function __construct()
    {
        foreach (CarBrand::all() as $b) {
            $this->bySlug[strtolower($b->slug)] = $b;
        }
    }

    protected function validateRow(Collection $row): array
    {
        $errors = [];
        $name = trim((string) ($row['name'] ?? ''));
        if ($name === '') {
            $errors[] = 'name is required';
        } elseif (mb_strlen($name) > 100) {
            $errors[] = 'name exceeds 100 chars';
        }
        return $errors;
    }

    protected function processRow(Collection $row): void
    {
        $name = trim((string) $row['name']);
        $slug = trim((string) ($row['slug'] ?? ''));
        $active = $this->coerceBool($row['is_active'] ?? true);

        $matchSlug = $slug !== '' ? Str::slug($slug) : null;
        $existing = $matchSlug ? ($this->bySlug[strtolower($matchSlug)] ?? null) : null;

        // Also try matching by Str::slug(name) when slug blank.
        if (! $existing && $slug === '') {
            $existing = $this->bySlug[strtolower(Str::slug($name))] ?? null;
        }

        if ($existing) {
            // UPDATE — never touch slug per D-4.3-14.
            $existing->update([
                'name'      => $name,
                'is_active' => $active,
            ]);
        } else {
            $finalSlug = $matchSlug ?: Str::slug($name);
            $row = CarBrand::create([
                'name'      => $name,
                'slug'      => $finalSlug,
                'is_active' => $active,
            ]);
            $this->bySlug[strtolower($finalSlug)] = $row;
        }
    }

    protected function coerceBool(mixed $v): bool
    {
        if (is_bool($v)) return $v;
        $s = strtolower(trim((string) $v));
        return in_array($s, ['1', 'true', 'yes', 'y'], true);
    }
}
