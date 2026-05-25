<?php

namespace App\Console\Commands;

use App\Models\CarBrand;
use App\Models\CarModel;
use App\Models\FuelType;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Wipe-and-replace the car catalog (brands, models, fuel types) from a
 * 4-column sheet: [Brand, Model, Fuel Type, Segment]. Each row defines
 * one fuel-type that the given (brand, model) supports, and the segment
 * is denormalised to the model row.
 *
 * WARNING — destructive. car_brands and car_models cascade-delete into
 * service_prices, so all pricing data tied to the old IDs is wiped too.
 * Operator was warned before running.
 */
class ImportCarList extends Command
{
    protected $signature = 'cars:import
                            {file : Absolute or storage-relative path to the .xlsx}
                            {--commit : Actually wipe + reimport (default is dry-run)}';

    protected $description = 'Wipe brands/models/fuels and re-seed them from a [Brand, Model, Fuel, Segment] xlsx.';

    public function handle(): int
    {
        $file = $this->resolveFile($this->argument('file'));
        if ($file === null) {
            return self::FAILURE;
        }

        $rows = $this->readSheet($file);
        if (empty($rows)) {
            $this->error('Sheet is empty or could not be parsed.');
            return self::FAILURE;
        }

        // Extract unique brand / (brand,model,segment) / fuel sets +
        // the (brand,model)→fuels relationship.
        $brands             = [];                       // brand
        $models             = [];                       // key = "brand|model" => ['brand','model','segment']
        $fuels              = [];                       // fuel
        $modelFuels         = [];                       // "brand|model" => [fuel,…]
        foreach ($rows as $r) {
            $brand   = trim((string) ($r[0] ?? ''));
            $model   = trim((string) ($r[1] ?? ''));
            $fuel    = trim((string) ($r[2] ?? ''));
            $segment = trim((string) ($r[3] ?? ''));
            if ($brand === '' || $model === '' || $fuel === '') {
                continue;
            }
            $key = $brand . '|' . $model;
            $brands[strtolower($brand)]      ??= $brand;
            $fuels[strtolower($fuel)]        ??= $fuel;
            $models[strtolower($key)]        ??= [
                'brand'   => $brand,
                'model'   => $model,
                'segment' => $segment !== '' ? $segment : null,
            ];
            $modelFuels[strtolower($key)][strtolower($fuel)] = $fuel;
        }

        $this->table(['Entity', 'Unique count'], [
            ['Brands',       count($brands)],
            ['Models',       count($models)],
            ['Fuel types',   count($fuels)],
            ['Model→Fuel',   array_sum(array_map('count', $modelFuels))],
        ]);

        if (! $this->option('commit')) {
            $this->warn('Dry-run only — re-run with --commit to wipe + import.');
            return self::SUCCESS;
        }

        $this->info('Wiping existing catalog (service_prices will cascade)...');

        DB::transaction(function () use ($brands, $models, $fuels, $modelFuels) {
            // Order: pivot first (so it doesn't reference dropped models),
            // then service_prices is auto-cascaded by FKs below.
            DB::table('car_model_fuel_types')->delete();
            // car_models cascadeOnDelete from car_brands → no need to
            // delete car_models separately. Same for service_prices.
            DB::table('car_brands')->delete();
            DB::table('fuel_types')->delete();

            // Brands.
            $brandIds = [];
            foreach ($brands as $name) {
                $brand = CarBrand::create([
                    'name'      => $name,
                    'slug'      => $this->uniqueSlug(CarBrand::class, $name),
                    'is_active' => true,
                ]);
                $brandIds[strtolower($name)] = $brand->id;
            }

            // Fuels.
            $fuelIds = [];
            foreach ($fuels as $name) {
                $fuel = FuelType::create([
                    'name'      => $name,
                    'slug'      => $this->uniqueSlug(FuelType::class, $name),
                    'is_active' => true,
                ]);
                $fuelIds[strtolower($name)] = $fuel->id;
            }

            // Models (with segment).
            $modelIds = [];
            foreach ($models as $key => $row) {
                $brandId = $brandIds[strtolower($row['brand'])] ?? null;
                if ($brandId === null) {
                    continue;
                }
                $model = CarModel::create([
                    'brand_id'  => $brandId,
                    'name'      => $row['model'],
                    'slug'      => $this->uniqueModelSlug($brandId, $row['model']),
                    'segment'   => $row['segment'],
                    'is_active' => true,
                ]);
                $modelIds[$key] = $model->id;
            }

            // Pivot.
            $pivotRows = [];
            foreach ($modelFuels as $key => $fuelMap) {
                $modelId = $modelIds[$key] ?? null;
                if ($modelId === null) {
                    continue;
                }
                foreach ($fuelMap as $fuelName) {
                    $fuelId = $fuelIds[strtolower($fuelName)] ?? null;
                    if ($fuelId === null) {
                        continue;
                    }
                    $pivotRows[] = [
                        'car_model_id' => $modelId,
                        'fuel_type_id' => $fuelId,
                        'created_at'   => now(),
                        'updated_at'   => now(),
                    ];
                }
            }
            foreach (array_chunk($pivotRows, 500) as $chunk) {
                DB::table('car_model_fuel_types')->insert($chunk);
            }
        });

        $this->info('Done.');
        $this->table(['Entity', 'Row count after import'], [
            ['car_brands',           CarBrand::count()],
            ['car_models',           CarModel::count()],
            ['fuel_types',           FuelType::count()],
            ['car_model_fuel_types', DB::table('car_model_fuel_types')->count()],
            ['service_prices',       DB::table('service_prices')->count()],
        ]);

        return self::SUCCESS;
    }

    /** @return array<int, array<int, mixed>> */
    private function readSheet(string $path): array
    {
        $reader = new class implements \Maatwebsite\Excel\Concerns\ToArray {
            public function array(array $array) {}
        };
        $sheets = Excel::toArray($reader, $path);
        $rows   = $sheets[0] ?? [];
        // Skip the header row (first row that looks like "Brand|Model|Fuel|Segment").
        if (! empty($rows)) {
            $first = array_map(fn ($v) => strtolower(trim((string) $v)), (array) $rows[0]);
            if (in_array('brand', $first, true) || in_array('model', $first, true)) {
                array_shift($rows);
            }
        }
        return $rows;
    }

    private function resolveFile(string $arg): ?string
    {
        if (is_file($arg)) {
            return $arg;
        }
        $candidate = storage_path('app/' . ltrim($arg, '/\\'));
        if (is_file($candidate)) {
            return $candidate;
        }
        $this->error("File not found: {$arg}");
        return null;
    }

    private function uniqueSlug(string $modelClass, string $name): string
    {
        $base = Str::slug($name);
        if ($base === '') {
            $base = 'auto-' . Str::lower(Str::random(6));
        }
        $slug = $base;
        $i    = 1;
        while ($modelClass::where('slug', $slug)->exists()) {
            $i++;
            $slug = $base . '-' . $i;
        }
        return $slug;
    }

    private function uniqueModelSlug(int $brandId, string $name): string
    {
        $base = Str::slug($name);
        if ($base === '') {
            $base = 'auto-' . Str::lower(Str::random(6));
        }
        // Unique constraint is (brand_id, slug) so scope by brand.
        $slug = $base;
        $i    = 1;
        while (CarModel::where('brand_id', $brandId)->where('slug', $slug)->exists()) {
            $i++;
            $slug = $base . '-' . $i;
        }
        return $slug;
    }
}
