<?php

use App\Console\Commands\ImportServiceContent as IMP;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceInclusion;

/**
 * service-content:import — safe, slug-keyed, additive + NULL-only import
 * of old acr2025 service content. Pure helpers tested directly; an
 * end-to-end run exercises the real dumps against a seeded service set.
 */

/* ───────────── pure helpers ───────────── */

it('resolves slugs: exact, near map, and the rear-shock skip', function () {
    expect(IMP::resolveTargetSlug('battery-charging'))->toBe('battery-charging');      // exact
    expect(IMP::resolveTargetSlug('boot-paint'))->toBe('boot-point');                  // near
    expect(IMP::resolveTargetSlug('front-brake-disc-replacement'))->toBe('front-brake-disc');
    expect(IMP::resolveTargetSlug('rear-shock-absorber-replacement'))->toBeNull();     // skip
});

it('pattern-filters warranty (real text passes, symptom text rejected)', function () {
    expect(IMP::warrantyPasses('Warranty 1000 kms or 1 month'))->toBeTrue();
    expect(IMP::warrantyPasses('Car Does Not Starts'))->toBeFalse();
    expect(IMP::warrantyPasses('Doorstep Service Available'))->toBeFalse();
    expect(IMP::warrantyPasses(null))->toBeFalse();
});

it('pattern-filters recommended (real text passes, symptom rejected)', function () {
    expect(IMP::recommendedPasses('After every 10,000 kms or 1 year'))->toBeTrue();
    expect(IMP::recommendedPasses('Electrical System Does Not Work'))->toBeFalse();
});

it('seeds interval only from a km cadence string', function () {
    expect(IMP::intervalFrom('After every 5,000 kms or 3 Months'))->toBe('After every 5,000 kms or 3 Months');
    expect(IMP::intervalFrom('Doorstep Service Available'))->toBeNull();
    expect(IMP::intervalFrom('Recommended'))->toBeNull(); // matches recommended filter but has no "every N km"
});

it('maps time unit Hour→hours, Day→days', function () {
    expect(IMP::mapTimeUnit('Hour'))->toBe('hours');
    expect(IMP::mapTimeUnit('Day'))->toBe('days');
    expect(IMP::mapTimeUnit(null))->toBeNull();
});

it('parses a MySQL INSERT VALUES block (NULL + escaped quote)', function () {
    $sql = "INSERT INTO `t` (`a`,`b`,`c`) VALUES (1, 'O\\'Brien', NULL),(2, 'plain', 'x');";
    $rows = IMP::parseInsertValues($sql);
    expect($rows)->toHaveCount(2);
    expect($rows[0][1])->toBe("O'Brien");
    expect($rows[0][2])->toBeNull();
    expect($rows[1][0])->toBe('2');
});

/* ───────────── end-to-end against the real dumps ───────────── */

it('imports inclusions + time + filtered warranty/recommended/interval, and is idempotent', function () {
    $pkgRows  = IMP::parseInsertValues(file_get_contents(storage_path('app/imports/sceduled_packages.sql')));
    $specRows = IMP::parseInsertValues(file_get_contents(storage_path('app/imports/package_specification.sql')));

    // Seed a service for every resolved target so there are no "unmatched" misses.
    $idToTarget = [];
    $targets = [];
    foreach ($pkgRows as $r) {
        $t = IMP::resolveTargetSlug((string) $r[5]);
        $idToTarget[$r[0]] = $t;
        if ($t !== null) $targets[$t] = true;
    }
    $cat = ServiceCategory::factory()->create();
    foreach (array_keys($targets) as $slug) {
        Service::factory()->create([
            'category_id' => $cat->id, 'slug' => $slug, 'is_active' => true,
            'time_takes' => null, 'time_unit' => null,
            'warrenty_info' => null, 'recommended_info' => null, 'interval_info' => null,
        ]);
    }

    // Expected inclusion totals (all specs except the skipped rear-shock package).
    $expectedIncl = 0; $svcWithIncl = [];
    foreach ($specRows as $r) {
        $t = $idToTarget[$r[1]] ?? null;
        if ($t !== null) { $expectedIncl++; $svcWithIncl[$t] = true; }
    }

    $this->artisan('service-content:import')->assertSuccessful();

    expect(ServiceInclusion::count())->toBe($expectedIncl);
    expect(Service::has('inclusions')->count())->toBe(count($svcWithIncl));
    expect(ServiceInclusion::whereNull('group_name')->count())->toBe($expectedIncl); // autogroup runs later

    // rear-shock skipped — never created, never attached.
    expect(Service::where('slug', 'rear-shock-absorber-replacement')->exists())->toBeFalse();

    // battery-charging: 6 inclusions, ordered, position 1 first; warranty was a
    // SYMPTOM ("Car Does Not Starts") → rejected → stays NULL. time set.
    $battery = Service::where('slug', 'battery-charging')->first();
    $incl = $battery->inclusions()->get();
    expect($incl->count())->toBe(6);
    expect($incl->first()->position)->toBe(1);
    expect($incl->first()->label)->toBe('Available at Doorstep');
    expect($battery->fresh()->warrenty_info)->toBeNull();
    expect($battery->fresh()->time_takes)->toBe('24');
    expect($battery->fresh()->time_unit)->toBe('hours');

    // full-ac-service: real warranty + recommended pass; interval seeded.
    $ac = Service::where('slug', 'full-ac-service')->first()->fresh();
    expect($ac->warrenty_info)->toMatch('/warranty|km|month/i');
    expect($ac->recommended_info)->toMatch('/after every/i');
    expect($ac->interval_info)->toMatch('/every\s+[\d,]+\s*kms?/i');

    // ---- idempotency: second run changes nothing ----
    $before = ServiceInclusion::count();
    $this->artisan('service-content:import')->assertSuccessful();
    expect(ServiceInclusion::count())->toBe($before);                 // empty-guard
    expect($battery->fresh()->warrenty_info)->toBeNull();             // still NULL
    expect($ac->fresh()->time_takes)->toBe(Service::where('slug', 'full-ac-service')->first()->time_takes); // unchanged
});

it('dry-run writes nothing', function () {
    $pkgRows = IMP::parseInsertValues(file_get_contents(storage_path('app/imports/sceduled_packages.sql')));
    $cat = ServiceCategory::factory()->create();
    foreach ($pkgRows as $r) {
        $t = IMP::resolveTargetSlug((string) $r[5]);
        if ($t !== null) {
            Service::firstOrCreate(['slug' => $t], ['category_id' => $cat->id, 'name' => $t, 'is_active' => true]);
        }
    }

    $this->artisan('service-content:import --dry-run')->assertSuccessful();

    expect(ServiceInclusion::count())->toBe(0);
    expect(Service::whereNotNull('time_takes')->count())->toBe(0);
    expect(Service::whereNotNull('warrenty_info')->count())->toBe(0);
});
