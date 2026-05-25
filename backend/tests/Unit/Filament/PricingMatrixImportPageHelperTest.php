<?php

use App\Filament\Pages\Exceptions\InvalidUploadStateException;
use App\Filament\Pages\PricingMatrixImportPage;
use Illuminate\Support\Facades\Storage;

/**
 * Phase 4.3.4 — pure unit coverage for the private helper that
 * normalizes Filament FileUpload state to an absolute path.
 *
 * The helper is intentionally private (single source of truth for
 * analyze() + commit() in PricingMatrixImportPage) so we invoke via
 * reflection. No DB: Storage::fake('local') gives us a real on-disk
 * fake so is_file() existence checks exercise the same code path as
 * production without polluting the real storage/app/imports/.
 *
 * Maps directly to the seven Filament FileUpload return shapes
 * documented in the Phase 4.3.4 brief (D-4.3.4-3).
 */

function pmipInvokeResolve(PricingMatrixImportPage $page): string
{
    $m = new ReflectionMethod($page, 'resolveUploadedFilePath');
    $m->setAccessible(true);
    return $m->invoke($page);
}

function pmipFreshPage(array $uploadData = []): PricingMatrixImportPage
{
    $page = new PricingMatrixImportPage();
    $page->uploadData = $uploadData;
    return $page;
}

beforeEach(function () {
    Storage::fake('local');
});

it('CASE 1 — null file throws InvalidUploadStateException', function () {
    $page = pmipFreshPage(['file' => null]);

    expect(fn () => pmipInvokeResolve($page))
        ->toThrow(InvalidUploadStateException::class, 'No file uploaded');
});

it('CASE 2 — empty array throws InvalidUploadStateException', function () {
    $page = pmipFreshPage(['file' => []]);

    expect(fn () => pmipInvokeResolve($page))
        ->toThrow(InvalidUploadStateException::class, 'No file uploaded');
});

it('CASE 3 — direct string path resolves to absolute when file exists', function () {
    Storage::disk('local')->put('imports/case3.xlsx', 'x');
    $page = pmipFreshPage(['file' => 'imports/case3.xlsx']);

    $absolute = pmipInvokeResolve($page);

    expect($absolute)->toBe(Storage::disk('local')->path('imports/case3.xlsx'));
    expect(is_file($absolute))->toBeTrue();
});

it('CASE 4 — single-element hash-keyed array (Filament v3 default) resolves to absolute', function () {
    Storage::disk('local')->put('imports/case4.xlsx', 'x');
    $page = pmipFreshPage(['file' => ['abc123hash' => 'imports/case4.xlsx']]);

    $absolute = pmipInvokeResolve($page);

    expect($absolute)->toBe(Storage::disk('local')->path('imports/case4.xlsx'));
    expect(is_file($absolute))->toBeTrue();
});

it('CASE 5 — multi-element array uses first entry (operator dragged multiple)', function () {
    Storage::disk('local')->put('imports/case5a.xlsx', 'x');
    Storage::disk('local')->put('imports/case5b.xlsx', 'y');
    $page = pmipFreshPage(['file' => [
        'k1' => 'imports/case5a.xlsx',
        'k2' => 'imports/case5b.xlsx',
    ]]);

    $absolute = pmipInvokeResolve($page);

    // Helper takes the FIRST value (reset()) and logs a Phase4.3.4 warning.
    expect($absolute)->toBe(Storage::disk('local')->path('imports/case5a.xlsx'));
});

it('CASE 7 — unrecognized scalar type (integer) throws with type info', function () {
    $page = pmipFreshPage(['file' => 42]);

    expect(fn () => pmipInvokeResolve($page))
        ->toThrow(InvalidUploadStateException::class, 'Unrecognized upload state type: integer');
});

it('throws when string path resolves to a file that is not on disk', function () {
    $page = pmipFreshPage(['file' => 'imports/never-existed.xlsx']);

    expect(fn () => pmipInvokeResolve($page))
        ->toThrow(InvalidUploadStateException::class, 'File not found at resolved path');
});
