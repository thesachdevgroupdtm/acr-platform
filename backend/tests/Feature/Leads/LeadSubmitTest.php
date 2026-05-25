<?php

use App\Models\CarBrand;
use App\Models\CarModel;
use App\Models\Lead;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Support\Facades\DB;

/**
 * Phase 4.5.3 — POST /api/v1/leads.
 */

it('stores a valid lead', function () {
    $brand = CarBrand::factory()->create(['is_active' => true]);
    $model = CarModel::factory()->create([
        'brand_id'  => $brand->id,
        'is_active' => true,
    ]);
    $cat = ServiceCategory::factory()->create();
    $svc = Service::factory()->create([
        'category_id' => $cat->id,
        'is_active'   => true,
    ]);

    $response = $this->postJson('/api/v1/leads', [
        'name'       => 'Asha Verma',
        'email'      => 'asha@example.com',
        'phone'      => '9876543210',
        'brand_id'   => $brand->id,
        'model_id'   => $model->id,
        'service_id' => $svc->id,
    ]);

    $response->assertSuccessful();
    expect($response->json('ok'))->toBeTrue();
    expect($response->json('lead_id'))->toBeInt();

    $stored = Lead::find($response->json('lead_id'));
    expect($stored)->not->toBeNull();
    expect($stored->name)->toBe('Asha Verma');
    expect($stored->phone)->toBe('9876543210');
    expect($stored->status)->toBe('new');
    expect($stored->source)->toBe('explore_sidebar');
});

it('rejects invalid phone (returns 422)', function () {
    $this->postJson('/api/v1/leads', [
        'name'  => 'Bob',
        'phone' => '1234567890',           // starts with 1 — fails Indian regex
    ])->assertStatus(422)
      ->assertJsonValidationErrors(['phone']);

    // Also reject sub-10-digit and missing.
    $this->postJson('/api/v1/leads', [
        'name'  => 'Bob',
        'phone' => '98765',
    ])->assertStatus(422)
      ->assertJsonValidationErrors(['phone']);

    $this->postJson('/api/v1/leads', [
        'name' => 'Bob',
    ])->assertStatus(422)
      ->assertJsonValidationErrors(['phone']);
});

it('rejects model not matching brand (returns 422)', function () {
    $brandA = CarBrand::factory()->create(['is_active' => true]);
    $brandB = CarBrand::factory()->create(['is_active' => true]);
    $modelOfB = CarModel::factory()->create([
        'brand_id'  => $brandB->id,
        'is_active' => true,
    ]);

    $this->postJson('/api/v1/leads', [
        'name'     => 'Carol',
        'phone'    => '9876543211',
        'brand_id' => $brandA->id,
        'model_id' => $modelOfB->id,    // belongs to brand B, not A
    ])->assertStatus(422)
      ->assertJsonValidationErrors(['model_id']);
});

it('marks 4th submission from same phone in 24h as spam', function () {
    $payload = [
        'name'  => 'Spammer',
        'phone' => '9888777666',
    ];

    // First 3 — normal "new" leads.
    for ($i = 0; $i < 3; $i++) {
        $r = $this->postJson('/api/v1/leads', $payload);
        $r->assertSuccessful();
        $lead = Lead::find($r->json('lead_id'));
        expect($lead->status)->toBe('new');
    }

    // 4th submission within 24h — auto-flagged spam (response still 200).
    $r = $this->postJson('/api/v1/leads', $payload);
    $r->assertSuccessful();
    $lead = Lead::find($r->json('lead_id'));
    expect($lead->status)->toBe('spam');

    // Sanity — DB has 4 rows, 3 'new' + 1 'spam'.
    expect(DB::table('leads')->where('phone', '9888777666')->count())->toBe(4);
    expect(DB::table('leads')->where('phone', '9888777666')->where('status', 'spam')->count())->toBe(1);
});
