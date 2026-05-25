<?php

use App\Filament\Resources\ServiceCategoryResource\Pages\CreateServiceCategory;
use App\Filament\Resources\ServiceResource\Pages\CreateService;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceInclusion;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Livewire\Livewire;

/**
 * Service-pages redesign — Phase 1 (backend data + admin).
 *
 * Covers the additive schema (service_inclusions + services.interval_info),
 * the Service↔inclusions relation, the API surface (interval_info +
 * inclusions[] + full image URLs), and that the new fields are fillable in
 * the Filament admin. No frontend / layout work (Phase 2).
 */

/* ─────────────── Schema (D-P1-1 / D-P1-2) ─────────────── */

it('created the service_inclusions table with the expected columns', function () {
    expect(Schema::hasTable('service_inclusions'))->toBeTrue();
    foreach (['id', 'service_id', 'label', 'image', 'position', 'created_at', 'updated_at'] as $col) {
        expect(Schema::hasColumn('service_inclusions', $col))->toBeTrue("missing column {$col}");
    }
});

it('added services.interval_info as an additive nullable column', function () {
    expect(Schema::hasColumn('services', 'interval_info'))->toBeTrue();
});

/* ─────────────── Model relation (D-P1-1) ─────────────── */

it('exposes Service hasMany inclusions ordered by position', function () {
    $cat = ServiceCategory::factory()->create();
    $svc = Service::factory()->create(['category_id' => $cat->id]);

    // Insert out of order — the relation must return them by position.
    ServiceInclusion::create(['service_id' => $svc->id, 'label' => 'Third',  'position' => 3]);
    ServiceInclusion::create(['service_id' => $svc->id, 'label' => 'First',  'position' => 1]);
    ServiceInclusion::create(['service_id' => $svc->id, 'label' => 'Second', 'position' => 2]);

    $labels = $svc->inclusions()->pluck('label')->all();
    expect($labels)->toBe(['First', 'Second', 'Third']);
});

it('cascade-deletes inclusions when the parent service is deleted', function () {
    $cat = ServiceCategory::factory()->create();
    $svc = Service::factory()->create(['category_id' => $cat->id]);
    ServiceInclusion::create(['service_id' => $svc->id, 'label' => 'X', 'position' => 1]);

    expect(ServiceInclusion::where('service_id', $svc->id)->count())->toBe(1);
    $svc->delete();
    expect(ServiceInclusion::where('service_id', $svc->id)->count())->toBe(0);
});

/* ─────────────── API detail (D-P1-5 / D-P1-6) ─────────────── */

it('returns interval_info + ordered inclusions[] with full image URLs on the detail endpoint', function () {
    $cat = ServiceCategory::factory()->create(['slug' => 'cat-' . uniqid(), 'is_active' => true]);
    $svc = Service::factory()->create([
        'category_id'   => $cat->id,
        'slug'          => 'svc-' . uniqid(),
        'is_active'     => true,
        'image'         => 'entity-images/services/sample.webp',
        'interval_info' => 'Every 5000 km or 3 months',
    ]);
    ServiceInclusion::create(['service_id' => $svc->id, 'label' => 'B Item', 'position' => 2, 'image' => 'entity-images/service-inclusions/b.webp']);
    ServiceInclusion::create(['service_id' => $svc->id, 'label' => 'A Item', 'position' => 1]);

    $resp = $this->getJson("/api/v1/services/{$cat->slug}/{$svc->slug}");

    $resp->assertOk()
        ->assertJsonPath('service.interval_info', 'Every 5000 km or 3 months')
        ->assertJsonStructure([
            'service' => ['interval_info', 'inclusions' => [['id', 'label', 'image', 'position']]],
        ]);

    // Inclusions ordered by position.
    $labels = collect($resp->json('service.inclusions'))->pluck('label')->all();
    expect($labels)->toBe(['A Item', 'B Item']);

    // Service image + inclusion image are full /storage URLs, not raw paths.
    expect($resp->json('service.image'))->toContain('/storage/entity-images/services/sample.webp');
    $bImage = collect($resp->json('service.inclusions'))->firstWhere('label', 'B Item')['image'];
    expect($bImage)->toContain('/storage/entity-images/service-inclusions/b.webp');
    // Inclusion without an image stays null.
    $aImage = collect($resp->json('service.inclusions'))->firstWhere('label', 'A Item')['image'];
    expect($aImage)->toBeNull();
});

it('keeps a service with no image / no inclusions working (nulls + empty list)', function () {
    $cat = ServiceCategory::factory()->create(['slug' => 'cat-' . uniqid(), 'is_active' => true]);
    $svc = Service::factory()->create([
        'category_id' => $cat->id,
        'slug'        => 'svc-' . uniqid(),
        'is_active'   => true,
        'image'       => null,
    ]);

    $resp = $this->getJson("/api/v1/services/{$cat->slug}/{$svc->slug}");

    $resp->assertOk()
        ->assertJsonPath('service.image', null)
        ->assertJsonPath('service.interval_info', null)
        ->assertJsonPath('service.inclusions', []);
});

/* ─────────────── API list + category (D-P1-5 / D-P1-6) ─────────────── */

it('returns interval_info + full image URL on the lean list endpoint (no inclusions key)', function () {
    $cat = ServiceCategory::factory()->create(['slug' => 'cat-' . uniqid(), 'is_active' => true]);
    $svc = Service::factory()->create([
        'category_id'   => $cat->id,
        'slug'          => 'svc-' . uniqid(),
        'is_active'     => true,
        'image'         => 'entity-images/services/list.webp',
        'interval_info' => 'Every 10000 km',
    ]);

    $resp = $this->getJson('/api/v1/services');
    $resp->assertOk();

    $found = collect($resp->json('categories'))
        ->firstWhere('id', $cat->id)['services'];
    $row = collect($found)->firstWhere('id', $svc->id);

    expect($row['interval_info'])->toBe('Every 10000 km');
    expect($row['image'])->toContain('/storage/entity-images/services/list.webp');
    // List stays lean — inclusions are NOT included here (D-P1-5).
    expect($row)->not->toHaveKey('inclusions');
});

it('resolves category image + icon_image to full URLs', function () {
    $cat = ServiceCategory::factory()->create([
        'slug'       => 'cat-' . uniqid(),
        'is_active'  => true,
        'image'      => 'entity-images/categories/c.webp',
        'icon_image' => 'entity-images/categories/c-icon.webp',
    ]);
    $svc = Service::factory()->create(['category_id' => $cat->id, 'slug' => 'svc-' . uniqid(), 'is_active' => true]);

    $resp = $this->getJson("/api/v1/services/{$cat->slug}/{$svc->slug}");
    $resp->assertOk();

    expect($resp->json('category.image'))->toContain('/storage/entity-images/categories/c.webp');
    expect($resp->json('category.icon_image'))->toContain('/storage/entity-images/categories/c-icon.webp');
});

/* ─────────────── Filament admin fillable (D-P1-3 / D-P1-4) ─────────────── */

it('lets an admin fill interval_info + inclusions on the Service form and persists them ordered', function () {
    $admin = User::factory()->admin()->create();
    $cat   = ServiceCategory::factory()->create();
    $name  = 'Phase1 Admin Service';

    $this->actingAs($admin);
    Livewire::test(CreateService::class)
        ->assertFormFieldExists('interval_info')
        ->assertFormFieldExists('inclusions')
        ->fillForm([
            'name'          => $name,
            'slug'          => Str::slug($name),
            'category_id'   => $cat->id,
            'is_active'     => true,
            'interval_info' => 'Every 5000 km or 3 months',
            'inclusions'    => [
                ['label' => 'Engine Oil Replacement'],
                ['label' => 'Oil Filter Replacement'],
                ['label' => 'Multi-point Inspection'],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $svc = Service::where('name', $name)->first();
    expect($svc)->not->toBeNull();
    expect($svc->interval_info)->toBe('Every 5000 km or 3 months');
    expect($svc->inclusions()->pluck('label')->all())->toBe([
        'Engine Oil Replacement',
        'Oil Filter Replacement',
        'Multi-point Inspection',
    ]);
});

it('exposes the icon_image field on the ServiceCategory admin form', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);
    Livewire::test(CreateServiceCategory::class)
        ->assertFormFieldExists('icon_image')
        ->assertFormFieldExists('image')
        ->assertFormFieldExists('description');
});
