<?php

namespace App\Exports;

use App\Models\CarBrand;
use App\Models\CarModel;
use App\Models\FuelType;
use App\Models\Service;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * Phase 4.3 — emit the current pricing data back as the matrix
 * layout the operator can edit + re-upload.
 *
 * Layout:
 *   Headers: Car_id, Make, Model, Fuel_Type, Segment, then one
 *   column per active service (alphabetical by name).
 *   Data:    one row per (brand, model, fuel_type) combo with a
 *   service_prices row for that vehicle. Cells use the price if
 *   present, "NA" otherwise.
 */
class PricingMatrixExport implements FromCollection, WithHeadings
{
    use Exportable;

    /** @var array<int, Service> */
    protected array $services;

    public function __construct()
    {
        $this->services = Service::where('is_active', true)
            ->orderBy('name')
            ->get()
            ->all();
    }

    public function headings(): array
    {
        return array_merge(
            ['Car_id', 'Make', 'Model', 'Fuel_Type', 'Segment'],
            array_map(fn (Service $s) => $s->name, $this->services),
        );
    }

    public function collection(): Collection
    {
        // Pre-load price rows once and index by composite key.
        $priceByKey = [];
        foreach (DB::table('service_prices')
            ->select('service_id', 'brand_id', 'model_id', 'fuel_type_id', 'price')
            ->cursor() as $r) {
            $priceByKey["{$r->service_id}|{$r->brand_id}|{$r->model_id}|{$r->fuel_type_id}"] = (float) $r->price;
        }

        // For every (brand, model, fuel) combination with at least
        // one price row, emit a matrix row.
        $combos = DB::table('service_prices')
            ->select('brand_id', 'model_id', 'fuel_type_id')
            ->distinct()
            ->orderBy('brand_id')
            ->orderBy('model_id')
            ->orderBy('fuel_type_id')
            ->get();

        $brands = CarBrand::all()->keyBy('id');
        $models = CarModel::all()->keyBy('id');
        $fuels  = FuelType::all()->keyBy('id');

        $rows = collect();
        $carId = 1;
        foreach ($combos as $c) {
            $brand = $brands[$c->brand_id] ?? null;
            $model = $models[$c->model_id] ?? null;
            $fuel  = $fuels[$c->fuel_type_id] ?? null;
            if (! $brand || ! $model || ! $fuel) {
                continue;
            }

            $row = [
                $carId++,
                $brand->name,
                $model->name,
                $fuel->name,
                '',   // segment — not yet a column on CarModel
            ];
            foreach ($this->services as $svc) {
                $key = "{$svc->id}|{$c->brand_id}|{$c->model_id}|{$c->fuel_type_id}";
                $row[] = $priceByKey[$key] ?? 'NA';
            }
            $rows->push($row);
        }

        return $rows;
    }
}
