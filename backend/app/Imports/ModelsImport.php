<?php

namespace App\Imports;

use App\Models\CarBrand;
use App\Models\CarModel;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Phase 4.3 — UPSERT models keyed by (brand_id, slug).
 *
 * Expected columns:
 *   name        — required
 *   brand_name  — required, must exist in car_brands
 *   slug        — optional (auto-generated on create)
 *   is_active   — optional (default true)
 */
class ModelsImport extends BaseImport
{
    /** @var array<string, CarBrand>  lowercase-name → brand */
    protected array $brandsByName = [];

    /** @var array<string, CarModel>  "{brand_id}|{slug}" → model */
    protected array $byKey = [];

    public function __construct()
    {
        foreach (CarBrand::all() as $b) {
            $this->brandsByName[strtolower($b->name)] = $b;
        }
        foreach (CarModel::all() as $m) {
            $this->byKey[$m->brand_id . '|' . strtolower($m->slug)] = $m;
        }
    }

    protected function validateRow(Collection $row): array
    {
        $errors = [];
        $name  = trim((string) ($row['name'] ?? ''));
        $brand = trim((string) ($row['brand_name'] ?? ''));

        if ($name === '')  $errors[] = 'name is required';
        if ($brand === '') $errors[] = 'brand_name is required';
        elseif (! isset($this->brandsByName[strtolower($brand)])) {
            $errors[] = "brand_name '{$brand}' not found in car_brands";
        }

        return $errors;
    }

    protected function processRow(Collection $row): void
    {
        $name  = trim((string) $row['name']);
        $brand = $this->brandsByName[strtolower(trim((string) $row['brand_name']))];
        $slug  = trim((string) ($row['slug'] ?? ''));
        $active = $this->coerceBool($row['is_active'] ?? true);

        $candidateSlug = $slug !== '' ? Str::slug($slug) : Str::slug($name);
        $key = $brand->id . '|' . strtolower($candidateSlug);
        $existing = $this->byKey[$key] ?? null;

        if ($existing) {
            $existing->update([
                'name'      => $name,
                'is_active' => $active,
            ]);
        } else {
            $row = CarModel::create([
                'brand_id'  => $brand->id,
                'name'      => $name,
                'slug'      => $candidateSlug,
                'is_active' => $active,
            ]);
            $this->byKey[$key] = $row;
        }
    }

    protected function coerceBool(mixed $v): bool
    {
        if (is_bool($v)) return $v;
        $s = strtolower(trim((string) $v));
        return in_array($s, ['1', 'true', 'yes', 'y'], true);
    }
}
