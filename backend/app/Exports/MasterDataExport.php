<?php

namespace App\Exports;

use App\Models\CarBrand;
use App\Models\CarModel;
use App\Models\FuelType;
use App\Models\Service;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * Phase 4.3 — single export class parameterised by data type.
 *
 *   new MasterDataExport('brands')         // → brands export
 *   new MasterDataExport('models')         // → models export
 *   new MasterDataExport('fuel_types')     // → fuel-types export
 *   new MasterDataExport('services')       // → services export
 *
 * Set `$templateOnly = true` to emit headers-only (a blank
 * template the operator fills in).
 */
class MasterDataExport implements FromCollection, WithHeadings
{
    use Exportable;

    public function __construct(
        protected string $type,
        protected bool $templateOnly = false,
    ) {}

    public function headings(): array
    {
        return match ($this->type) {
            'brands'     => ['name', 'slug', 'is_active'],
            'models'     => ['name', 'brand_name', 'slug', 'is_active'],
            'fuel_types' => ['name', 'slug', 'is_active'],
            'services'   => ['name', 'category_name', 'slug', 'description', 'base_price', 'is_active'],
            default      => throw new \InvalidArgumentException("Unknown export type: {$this->type}"),
        };
    }

    public function collection(): Collection
    {
        if ($this->templateOnly) {
            return collect();
        }

        return match ($this->type) {
            'brands' => CarBrand::orderBy('name')->get()->map(fn ($r) => [
                $r->name, $r->slug, $r->is_active ? 1 : 0,
            ]),
            'models' => CarModel::with('brand')->orderBy('name')->get()->map(fn ($r) => [
                $r->name, $r->brand?->name, $r->slug, $r->is_active ? 1 : 0,
            ]),
            'fuel_types' => FuelType::orderBy('name')->get()->map(fn ($r) => [
                $r->name, $r->slug, $r->is_active ? 1 : 0,
            ]),
            'services' => Service::with('category')->orderBy('name')->get()->map(fn ($r) => [
                $r->name, $r->category?->name, $r->slug, $r->description,
                $r->base_price, $r->is_active ? 1 : 0,
            ]),
            default => collect(),
        };
    }
}
