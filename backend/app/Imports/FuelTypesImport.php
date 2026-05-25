<?php

namespace App\Imports;

use App\Models\FuelType;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Phase 4.3 — UPSERT fuel types by slug.
 *
 * Expected columns:
 *   name      — required
 *   slug      — optional (auto from name)
 *   is_active — optional (default true)
 *
 * Note: spec D-4.3-9 mentions a `sort_order` column on FuelType;
 * the existing table doesn't have one, so it's accepted but
 * ignored. Field can be added in a later schema migration.
 */
class FuelTypesImport extends BaseImport
{
    /** @var array<string, FuelType>  slug-lowercase → fuel */
    protected array $bySlug = [];

    public function __construct()
    {
        foreach (FuelType::all() as $f) {
            $this->bySlug[strtolower($f->slug)] = $f;
        }
    }

    protected function validateRow(Collection $row): array
    {
        $errors = [];
        $name = trim((string) ($row['name'] ?? ''));
        if ($name === '') $errors[] = 'name is required';
        return $errors;
    }

    protected function processRow(Collection $row): void
    {
        $name = trim((string) $row['name']);
        $slug = trim((string) ($row['slug'] ?? ''));
        $active = $this->coerceBool($row['is_active'] ?? true);

        $candidate = $slug !== '' ? Str::slug($slug) : Str::slug($name);
        $existing = $this->bySlug[strtolower($candidate)] ?? null;

        if ($existing) {
            $existing->update([
                'name'      => $name,
                'is_active' => $active,
            ]);
        } else {
            $row = FuelType::create([
                'name'      => $name,
                'slug'      => $candidate,
                'is_active' => $active,
            ]);
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
