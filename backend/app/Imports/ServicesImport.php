<?php

namespace App\Imports;

use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Phase 4.3 — UPSERT services by slug.
 *
 * Expected columns:
 *   name           — required
 *   category_name  — required (must exist in service_categories)
 *   slug           — optional
 *   description    — optional
 *   is_active      — optional (default true)
 *   base_price     — optional decimal (only set if numeric)
 */
class ServicesImport extends BaseImport
{
    /** @var array<string, ServiceCategory> */
    protected array $catsByName = [];

    /** @var array<string, Service> */
    protected array $bySlug = [];

    public function __construct()
    {
        foreach (ServiceCategory::all() as $c) {
            $this->catsByName[strtolower($c->name)] = $c;
        }
        foreach (Service::all() as $s) {
            $this->bySlug[strtolower($s->slug)] = $s;
        }
    }

    protected function validateRow(Collection $row): array
    {
        $errors = [];
        $name = trim((string) ($row['name'] ?? ''));
        $cat  = trim((string) ($row['category_name'] ?? ''));
        if ($name === '') $errors[] = 'name is required';
        if ($cat === '')  $errors[] = 'category_name is required';
        elseif (! isset($this->catsByName[strtolower($cat)])) {
            $errors[] = "category_name '{$cat}' not found";
        }
        $bp = $row['base_price'] ?? null;
        if ($bp !== null && $bp !== '' && ! is_numeric($bp)) {
            $errors[] = "base_price must be numeric, got '{$bp}'";
        }
        return $errors;
    }

    protected function processRow(Collection $row): void
    {
        $name = trim((string) $row['name']);
        $cat  = $this->catsByName[strtolower(trim((string) $row['category_name']))];
        $slug = trim((string) ($row['slug'] ?? ''));
        $desc = $row['description'] ?? null;
        $active = $this->coerceBool($row['is_active'] ?? true);
        $bp = $row['base_price'] ?? null;

        $candidate = $slug !== '' ? Str::slug($slug) : Str::slug($name);
        $existing = $this->bySlug[strtolower($candidate)] ?? null;

        $attributes = [
            'name'        => $name,
            'category_id' => $cat->id,
            'description' => $desc,
            'is_active'   => $active,
        ];
        if ($bp !== null && $bp !== '' && is_numeric($bp)) {
            $attributes['base_price'] = (float) $bp;
        }

        if ($existing) {
            $existing->update($attributes);
        } else {
            $attributes['slug'] = $candidate;
            $row = Service::create($attributes);
            $this->bySlug[strtolower($candidate)] = $row;
        }
    }

    protected function coerceBool(mixed $v): bool
    {
        if (is_bool($v)) return $v;
        $s = strtolower(trim((string) $v));
        return in_array($s, ['1', 'true', 'yes', 'y'], true);
    }
}
