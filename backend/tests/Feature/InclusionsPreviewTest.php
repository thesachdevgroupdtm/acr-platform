<?php

use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceInclusion;
use Illuminate\Support\Facades\DB;

/**
 * Phase 2 (PART A) — lean `inclusions_preview` ({labels, total}) on the
 * category list endpoint GET /api/v1/services/{slug}, bulk-loaded (no N+1).
 * Phase 2e (D-2e-4) — labels are now the FULL list (position-ordered, lean:
 * labels only) so the card can expand inclusions IN PLACE; `total` == count.
 */

it('returns inclusions_preview with ALL labels (by position) + total', function () {
    $cat = ServiceCategory::factory()->create(['slug' => 'cat-' . uniqid(), 'is_active' => true]);
    $svc = Service::factory()->create(['category_id' => $cat->id, 'slug' => 'svc-' . uniqid(), 'is_active' => true]);

    // Insert out of order; preview must be ordered by position (no cap — the
    // full label list ships so the card expands in place).
    foreach ([5 => 'Fifth', 1 => 'First', 3 => 'Third', 2 => 'Second', 6 => 'Sixth', 4 => 'Fourth'] as $pos => $label) {
        ServiceInclusion::create(['service_id' => $svc->id, 'label' => $label, 'position' => $pos]);
    }

    $resp = $this->getJson("/api/v1/services/{$cat->slug}");
    $resp->assertOk()
        ->assertJsonStructure(['services' => [['id', 'inclusions_preview' => ['labels', 'total']]]]);

    $row = collect($resp->json('services'))->firstWhere('id', $svc->id);
    expect($row['inclusions_preview']['total'])->toBe(6);
    expect($row['inclusions_preview']['labels'])->toBe(['First', 'Second', 'Third', 'Fourth', 'Fifth', 'Sixth']);
});

it('returns an empty preview for a service with no inclusions', function () {
    $cat = ServiceCategory::factory()->create(['slug' => 'cat-' . uniqid(), 'is_active' => true]);
    $svc = Service::factory()->create(['category_id' => $cat->id, 'slug' => 'svc-' . uniqid(), 'is_active' => true]);

    $resp = $this->getJson("/api/v1/services/{$cat->slug}");
    $resp->assertOk();

    $row = collect($resp->json('services'))->firstWhere('id', $svc->id);
    expect($row['inclusions_preview'])->toBe(['labels' => [], 'total' => 0]);
});

it('loads inclusions for the whole category in ONE query (no N+1)', function () {
    $cat = ServiceCategory::factory()->create(['slug' => 'cat-' . uniqid(), 'is_active' => true]);
    foreach (range(1, 8) as $n) {
        $svc = Service::factory()->create(['category_id' => $cat->id, 'slug' => "svc-{$n}-" . uniqid(), 'is_active' => true]);
        foreach (range(1, 5) as $i) {
            ServiceInclusion::create(['service_id' => $svc->id, 'label' => "Svc{$n} Item{$i}", 'position' => $i]);
        }
    }

    DB::enableQueryLog();
    $this->getJson("/api/v1/services/{$cat->slug}")->assertOk();
    $log = DB::getQueryLog();
    DB::disableQueryLog();

    // Exactly one query touches service_inclusions regardless of 8 services.
    $inclQueries = collect($log)->filter(fn ($q) => str_contains($q['query'], 'service_inclusions'))->count();
    expect($inclQueries)->toBe(1);
});

it('keeps the full inclusions[] on the detail endpoint but NOT on the category list', function () {
    $cat = ServiceCategory::factory()->create(['slug' => 'cat-' . uniqid(), 'is_active' => true]);
    $svc = Service::factory()->create(['category_id' => $cat->id, 'slug' => 'svc-' . uniqid(), 'is_active' => true]);
    ServiceInclusion::create(['service_id' => $svc->id, 'label' => 'Only One', 'group_name' => 'Essential', 'position' => 1]);

    // Category list: preview present, full inclusions[] absent (lean).
    $list = $this->getJson("/api/v1/services/{$cat->slug}");
    $row = collect($list->json('services'))->firstWhere('id', $svc->id);
    expect($row['inclusions_preview']['total'])->toBe(1);
    expect($row)->not->toHaveKey('inclusions');

    // Detail: full inclusions[] present (with group_name).
    $detail = $this->getJson("/api/v1/services/{$cat->slug}/{$svc->slug}");
    $detail->assertOk()->assertJsonPath('service.inclusions.0.group_name', 'Essential');
});
