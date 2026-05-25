<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CarBrand;
use App\Models\CarModel;
use App\Models\FuelType;
use App\Models\Service;
use App\Models\ServicePrice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * CSV import endpoints — admin-only (gated by import.token middleware).
 *
 * Each method reads a CSV file from the `file` upload field, validates the
 * header row matches the expected columns, then upserts each row by its
 * natural key. Duplicates UPDATE; missing FKs cause the row to be skipped
 * (and reported in the response).
 *
 * Response shape (every endpoint):
 *   { success, imported, updated, skipped, errors: [{row, reason, data}] }
 */
class ImportController extends Controller
{
    /** Cap how many rows we process per request to bound memory + time. */
    private const MAX_ROWS = 50000;

    /**
     * POST /api/v1/import/car-brands
     * CSV columns: name, slug (slug is optional — derived from name if empty)
     */
    public function carBrands(Request $request): JsonResponse
    {
        return $this->process($request, ['name'], function (array $row, int $line, array &$out) {
            $name = trim($row['name'] ?? '');
            $slug = trim($row['slug'] ?? '') ?: Str::slug($name);

            if ($name === '' || $slug === '') {
                $out['errors'][] = ['row' => $line, 'reason' => 'name and slug required', 'data' => $row];
                $out['skipped']++;
                return;
            }

            $existing = CarBrand::where('slug', $slug)->first();
            $brand    = CarBrand::updateOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'is_active' => true]
            );
            $existing ? $out['updated']++ : $out['imported']++;
        });
    }

    /**
     * POST /api/v1/import/car-models
     * CSV columns: brand_slug, name, slug (slug optional)
     */
    public function carModels(Request $request): JsonResponse
    {
        return $this->process($request, ['brand_slug', 'name'], function (array $row, int $line, array &$out) {
            $brandSlug = trim($row['brand_slug'] ?? '');
            $name      = trim($row['name'] ?? '');
            $slug      = trim($row['slug'] ?? '') ?: Str::slug($name);

            if ($brandSlug === '' || $name === '' || $slug === '') {
                $out['errors'][] = ['row' => $line, 'reason' => 'brand_slug, name, slug required', 'data' => $row];
                $out['skipped']++;
                return;
            }

            $brand = CarBrand::where('slug', $brandSlug)->first();
            if (!$brand) {
                $out['errors'][] = ['row' => $line, 'reason' => "unknown brand_slug '{$brandSlug}'", 'data' => $row];
                $out['skipped']++;
                return;
            }

            $existing = CarModel::where('brand_id', $brand->id)->where('slug', $slug)->first();
            CarModel::updateOrCreate(
                ['brand_id' => $brand->id, 'slug' => $slug],
                ['name' => $name, 'is_active' => true]
            );
            $existing ? $out['updated']++ : $out['imported']++;
        });
    }

    /**
     * POST /api/v1/import/fuel-types
     * CSV columns: name, slug (slug optional)
     */
    public function fuelTypes(Request $request): JsonResponse
    {
        return $this->process($request, ['name'], function (array $row, int $line, array &$out) {
            $name = trim($row['name'] ?? '');
            $slug = trim($row['slug'] ?? '') ?: Str::slug($name);

            if ($name === '' || $slug === '') {
                $out['errors'][] = ['row' => $line, 'reason' => 'name and slug required', 'data' => $row];
                $out['skipped']++;
                return;
            }

            $existing = FuelType::where('slug', $slug)->first();
            FuelType::updateOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'is_active' => true]
            );
            $existing ? $out['updated']++ : $out['imported']++;
        });
    }

    /**
     * POST /api/v1/import/service-prices
     * CSV columns: service_slug, category_slug, brand_slug, model_slug, fuel_slug, price
     */
    public function servicePrices(Request $request): JsonResponse
    {
        return $this->process(
            $request,
            ['service_slug', 'category_slug', 'brand_slug', 'model_slug', 'fuel_slug', 'price'],
            function (array $row, int $line, array &$out) {
                $service = Service::query()
                    ->where('slug', trim($row['service_slug'] ?? ''))
                    ->whereHas('category', function ($q) use ($row) {
                        $q->where('slug', trim($row['category_slug'] ?? ''));
                    })
                    ->first();

                if (!$service) {
                    $out['errors'][] = ['row' => $line, 'reason' => 'service not found', 'data' => $row];
                    $out['skipped']++;
                    return;
                }

                $brand = CarBrand::where('slug', trim($row['brand_slug'] ?? ''))->first();
                $model = $brand
                    ? CarModel::where('brand_id', $brand->id)
                        ->where('slug', trim($row['model_slug'] ?? ''))
                        ->first()
                    : null;
                $fuel  = FuelType::where('slug', trim($row['fuel_slug'] ?? ''))->first();

                if (!$brand || !$model || !$fuel) {
                    $out['errors'][] = [
                        'row'    => $line,
                        'reason' => 'unknown brand/model/fuel slug',
                        'data'   => $row,
                    ];
                    $out['skipped']++;
                    return;
                }

                $price = (string) ($row['price'] ?? '');
                if (!is_numeric($price) || (float) $price < 0) {
                    $out['errors'][] = ['row' => $line, 'reason' => 'price must be a non-negative number', 'data' => $row];
                    $out['skipped']++;
                    return;
                }

                $existing = ServicePrice::query()
                    ->where('service_id', $service->id)
                    ->where('brand_id', $brand->id)
                    ->where('model_id', $model->id)
                    ->where('fuel_type_id', $fuel->id)
                    ->first();

                ServicePrice::updateOrCreate(
                    [
                        'service_id'   => $service->id,
                        'brand_id'     => $brand->id,
                        'model_id'     => $model->id,
                        'fuel_type_id' => $fuel->id,
                    ],
                    ['price' => (float) $price]
                );
                $existing ? $out['updated']++ : $out['imported']++;
            }
        );
    }

    /**
     * Shared CSV parsing pipeline.
     *
     * @param array<int, string> $requiredHeaders
     * @param callable(array, int, array): void $rowHandler
     */
    private function process(Request $request, array $requiredHeaders, callable $rowHandler): JsonResponse
    {
        $maxKb = (int) config('import.max_kb', 10240);

        $validator = Validator::make($request->all(), [
            'file' => ['required', 'file', 'max:' . $maxKb, 'mimes:csv,txt'],
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first('file'),
                'errors'  => $validator->errors(),
            ], 422);
        }

        $path = $request->file('file')->getRealPath();
        $fh   = @fopen($path, 'r');
        if (!$fh) {
            return response()->json([
                'success' => false,
                'message' => 'Could not open uploaded file.',
            ], 500);
        }

        $headers = fgetcsv($fh);
        if (!$headers) {
            fclose($fh);
            return response()->json([
                'success' => false,
                'message' => 'CSV is empty.',
            ], 422);
        }
        $headers = array_map(fn ($h) => strtolower(trim((string) $h)), $headers);
        $missing = array_diff($requiredHeaders, $headers);
        if (!empty($missing)) {
            fclose($fh);
            return response()->json([
                'success'         => false,
                'message'         => 'CSV is missing required columns.',
                'missing_columns' => array_values($missing),
                'expected'        => $requiredHeaders,
            ], 422);
        }

        $out = [
            'imported' => 0,
            'updated'  => 0,
            'skipped'  => 0,
            'errors'   => [],
        ];

        $line = 1; // header was line 1
        DB::beginTransaction();
        try {
            while (($raw = fgetcsv($fh)) !== false) {
                $line++;
                if ($line - 1 > self::MAX_ROWS) {
                    $out['errors'][] = [
                        'row'    => $line,
                        'reason' => 'row cap of ' . self::MAX_ROWS . ' exceeded; remaining rows skipped',
                    ];
                    break;
                }

                // Pad/truncate row to header width and zip.
                $raw = array_pad($raw, count($headers), null);
                $raw = array_slice($raw, 0, count($headers));
                $row = array_combine($headers, $raw) ?: [];

                $rowHandler($row, $line, $out);
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            fclose($fh);
            return response()->json([
                'success' => false,
                'message' => 'Import aborted: ' . $e->getMessage(),
            ], 500);
        }
        fclose($fh);

        return response()->json([
            'success'  => true,
            'imported' => $out['imported'],
            'updated'  => $out['updated'],
            'skipped'  => $out['skipped'],
            'errors'   => $out['errors'],
        ]);
    }
}
