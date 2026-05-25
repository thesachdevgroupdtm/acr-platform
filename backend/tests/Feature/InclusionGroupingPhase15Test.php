<?php

use App\Console\Commands\AutogroupInclusions;
use App\Filament\Resources\ServiceResource\Pages\CreateService;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceInclusion;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Livewire\Livewire;

/**
 * Service-pages redesign — Phase 1.5 (inclusion grouping).
 * Schema + model + API + autogroup classifier + Filament form.
 */

/* ─────────────── Schema + model ─────────────── */

it('added a nullable group_name column to service_inclusions', function () {
    expect(Schema::hasColumn('service_inclusions', 'group_name'))->toBeTrue();

    // Nullable: a row created without group_name stays null.
    $cat = ServiceCategory::factory()->create();
    $svc = Service::factory()->create(['category_id' => $cat->id]);
    $inc = ServiceInclusion::create(['service_id' => $svc->id, 'label' => 'No Group Item', 'position' => 1]);
    expect($inc->fresh()->group_name)->toBeNull();
});

it('mass-assigns group_name on ServiceInclusion', function () {
    $cat = ServiceCategory::factory()->create();
    $svc = Service::factory()->create(['category_id' => $cat->id]);
    $inc = ServiceInclusion::create([
        'service_id' => $svc->id, 'label' => 'Engine Oil', 'group_name' => 'Essential', 'position' => 1,
    ]);
    expect($inc->fresh()->group_name)->toBe('Essential');
});

/* ─────────────── Classifier keyword map (D-1.5-4) ─────────────── */

it('classifies labels per the keyword map (Performance → Additional → Essential)', function () {
    // Performance
    foreach (['Spark Plug Replacement', 'Coolant Top-up', 'Fuel Injector Cleaning', 'Throttle Body Cleaning', 'Carbon Cleaning'] as $l) {
        expect(AutogroupInclusions::classify($l))->toBe('Performance', $l);
    }
    // Additional
    foreach (['Exterior Wash', 'Interior Vacuum', 'Teflon Coating', 'Ceramic Coating', 'Rubbing & Polishing', 'Wax Protection'] as $l) {
        expect(AutogroupInclusions::classify($l))->toBe('Additional', $l);
    }
    // Essential (default bucket)
    foreach (['Engine Oil Replacement', 'Oil Filter Replacement', 'Brake Inspection', 'Battery Testing', 'Wheel Alignment'] as $l) {
        expect(AutogroupInclusions::classify($l))->toBe('Essential', $l);
    }
});

/* ─────────────── API (detail) ─────────────── */

it('emits group_name in detail inclusions[] (string + null)', function () {
    $cat = ServiceCategory::factory()->create(['slug' => 'cat-' . uniqid(), 'is_active' => true]);
    $svc = Service::factory()->create(['category_id' => $cat->id, 'slug' => 'svc-' . uniqid(), 'is_active' => true]);
    ServiceInclusion::create(['service_id' => $svc->id, 'label' => 'Spark Plug', 'group_name' => 'Performance', 'position' => 1]);
    ServiceInclusion::create(['service_id' => $svc->id, 'label' => 'Ungrouped Item', 'position' => 2]); // group_name null

    $resp = $this->getJson("/api/v1/services/{$cat->slug}/{$svc->slug}");

    $resp->assertOk()
        ->assertJsonStructure(['service' => ['inclusions' => [['id', 'label', 'group_name', 'image', 'position']]]]);

    $incl = collect($resp->json('service.inclusions'));
    expect($incl->firstWhere('label', 'Spark Plug')['group_name'])->toBe('Performance');
    expect($incl->firstWhere('label', 'Ungrouped Item')['group_name'])->toBeNull();
});

/* ─────────────── autogroup command ─────────────── */

it('dry-run proposes groups but writes nothing', function () {
    $cat = ServiceCategory::factory()->create();
    $svc = Service::factory()->create(['category_id' => $cat->id]);
    ServiceInclusion::create(['service_id' => $svc->id, 'label' => 'Exterior Wash', 'position' => 1]);
    ServiceInclusion::create(['service_id' => $svc->id, 'label' => 'Engine Oil', 'position' => 2]);

    $this->artisan('inclusions:autogroup --dry-run')->assertSuccessful();

    // Still NULL — nothing persisted.
    expect(ServiceInclusion::whereNull('group_name')->count())->toBe(2);
});

it('real run sets only NULL rows, preserves operator overrides, and is idempotent', function () {
    $cat = ServiceCategory::factory()->create();
    $svc = Service::factory()->create(['category_id' => $cat->id]);

    $wash = ServiceInclusion::create(['service_id' => $svc->id, 'label' => 'Exterior Wash', 'position' => 1]);          // → Additional
    $oil  = ServiceInclusion::create(['service_id' => $svc->id, 'label' => 'Engine Oil Replacement', 'position' => 2]); // → Essential
    // Operator override: a "Spark Plug" the operator deliberately put in Essential.
    $override = ServiceInclusion::create(['service_id' => $svc->id, 'label' => 'Spark Plug', 'group_name' => 'Essential', 'position' => 3]);

    $this->artisan('inclusions:autogroup')->assertSuccessful();

    expect($wash->fresh()->group_name)->toBe('Additional');
    expect($oil->fresh()->group_name)->toBe('Essential');
    // Override untouched (NULL-only guard) even though classify() would say Performance.
    expect($override->fresh()->group_name)->toBe('Essential');
    expect(ServiceInclusion::whereNull('group_name')->count())->toBe(0);

    // Idempotent: a second run has no NULL rows left to change.
    $this->artisan('inclusions:autogroup')->assertSuccessful();
    expect($wash->fresh()->group_name)->toBe('Additional');
    expect($override->fresh()->group_name)->toBe('Essential');
});

/* ─────────────── Filament ─────────────── */

it('persists an inclusion group_name set on the Service admin form', function () {
    $admin = User::factory()->admin()->create();
    $cat   = ServiceCategory::factory()->create();
    $name  = 'Phase15 Grouped Service';

    $this->actingAs($admin);
    Livewire::test(CreateService::class)
        ->fillForm([
            'name'        => $name,
            'slug'        => Str::slug($name),
            'category_id' => $cat->id,
            'is_active'   => true,
            'inclusions'  => [
                ['label' => 'Engine Oil Replacement', 'group_name' => 'Essential'],
                ['label' => 'Spark Plug Replacement', 'group_name' => 'Performance'],
                ['label' => 'Exterior Wash'], // left ungrouped
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $svc = Service::where('name', $name)->first();
    expect($svc)->not->toBeNull();
    $byLabel = $svc->inclusions()->get()->keyBy('label');
    expect($byLabel['Engine Oil Replacement']->group_name)->toBe('Essential');
    expect($byLabel['Spark Plug Replacement']->group_name)->toBe('Performance');
    expect($byLabel['Exterior Wash']->group_name)->toBeNull();
});
