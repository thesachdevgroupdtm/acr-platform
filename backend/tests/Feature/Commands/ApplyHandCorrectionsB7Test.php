<?php

use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceInclusion;

beforeEach(function () {
    $this->seedService = function (string $slug, ?string $intervalInfo = null): Service {
        return Service::factory()->create([
            'category_id'   => ServiceCategory::factory()->create()->id,
            'slug'          => $slug,
            'base_price'    => 1000,
            'interval_info' => $intervalInfo,
        ]);
    };

    $this->seedInclusion = function (Service $service, string $label, string $group): ServiceInclusion {
        return ServiceInclusion::create([
            'service_id' => $service->id,
            'label'      => $label,
            'group_name' => $group,
            'position'   => 1,
        ]);
    };
});

it('dry-run reports counts but performs zero writes', function () {
    $svcA = ($this->seedService)('test-svc-a');
    $fluid = ($this->seedInclusion)($svcA, 'Wiper Fluid Top Up', 'Performance');
    $insp = ($this->seedInclusion)($svcA, 'Exterior Inspection', 'Additional');
    $brake = ($this->seedService)('front-brake-pad');

    $this->artisan('corrections:apply-b7', ['--dry-run' => true])
        ->expectsOutputToContain('DRY-RUN')
        ->expectsOutputToContain('Updated 0 inclusion groups + 0 interval_info rows.')
        ->assertExitCode(0);

    expect(ServiceInclusion::find($fluid->id)->group_name)->toBe('Performance');
    expect(ServiceInclusion::find($insp->id)->group_name)->toBe('Additional');
    expect(Service::find($brake->id)->interval_info)->toBeNull();
});

it('moves all matching fluid top-ups from Performance to Essential', function () {
    $a = ($this->seedService)('svc-a');
    $b = ($this->seedService)('svc-b');

    $brake = ($this->seedInclusion)($a, 'Brake Fluid Top Up upto (100 ml)', 'Performance');
    $wiperA = ($this->seedInclusion)($a, 'Wiper Fluid Top Up', 'Performance');
    $wiperB = ($this->seedInclusion)($b, 'Wiper Fluid Top Up', 'Performance');
    $battery = ($this->seedInclusion)($a, 'Battery Water Top Up', 'Performance');
    // Negative control — a Performance inclusion that should NOT move.
    $sparkPlug = ($this->seedInclusion)($a, 'Spark Plug Cleaning', 'Performance');

    $this->artisan('corrections:apply-b7')->assertExitCode(0);

    expect(ServiceInclusion::find($brake->id)->group_name)->toBe('Essential');
    expect(ServiceInclusion::find($wiperA->id)->group_name)->toBe('Essential');
    expect(ServiceInclusion::find($wiperB->id)->group_name)->toBe('Essential');
    expect(ServiceInclusion::find($battery->id)->group_name)->toBe('Essential');
    expect(ServiceInclusion::find($sparkPlug->id)->group_name)->toBe('Performance');
});

it('moves only Exterior Inspection + Exterior and Interior Inspection from Additional to Essential', function () {
    $svc = ($this->seedService)('inspection-svc');

    $ext = ($this->seedInclusion)($svc, 'Exterior Inspection', 'Additional');
    $extInt = ($this->seedInclusion)($svc, 'Exterior and Interior Inspection', 'Additional');
    // Negative control — substring match must NOT trigger.
    $other = ($this->seedInclusion)($svc, 'Pre-Service Visual Exterior Inspection (extended)', 'Additional');
    $polish = ($this->seedInclusion)($svc, 'Rubbing and Polishing', 'Additional');

    $this->artisan('corrections:apply-b7')->assertExitCode(0);

    expect(ServiceInclusion::find($ext->id)->group_name)->toBe('Essential');
    expect(ServiceInclusion::find($extInt->id)->group_name)->toBe('Essential');
    expect(ServiceInclusion::find($other->id)->group_name)->toBe('Additional');
    expect(ServiceInclusion::find($polish->id)->group_name)->toBe('Additional');
});

it('seeds interval_info on all 5 target services with the locked values', function () {
    ($this->seedService)('front-brake-pad');
    ($this->seedService)('rear-brake-shoes');
    ($this->seedService)('tyre-rotation');
    ($this->seedService)('wheel-balancing');
    ($this->seedService)('complete-wheel-care');

    $this->artisan('corrections:apply-b7')->assertExitCode(0);

    expect(Service::where('slug', 'front-brake-pad')->value('interval_info'))
        ->toBe('After every 40,000 kms (Recommended)');
    expect(Service::where('slug', 'rear-brake-shoes')->value('interval_info'))
        ->toBe('After every 40,000 kms (Recommended)');
    expect(Service::where('slug', 'tyre-rotation')->value('interval_info'))
        ->toBe('After every 5,000 kms (Recommended)');
    expect(Service::where('slug', 'wheel-balancing')->value('interval_info'))
        ->toBe('After every 10,000 kms (Recommended)');
    expect(Service::where('slug', 'complete-wheel-care')->value('interval_info'))
        ->toBe('After every 10,000 kms (Recommended)');
});

it('NEVER overwrites a service that already has a non-NULL interval_info', function () {
    $custom = 'Operator-edited every 6 months';
    ($this->seedService)('tyre-rotation', $custom);

    $this->artisan('corrections:apply-b7')
        ->expectsOutputToContain('SKIP (already set — never overwrite)')
        ->assertExitCode(0);

    expect(Service::where('slug', 'tyre-rotation')->value('interval_info'))->toBe($custom);
});

it('is fully idempotent: second run with nothing to change exits 0 with WARN lines', function () {
    $svc = ($this->seedService)('idem-svc');
    ($this->seedInclusion)($svc, 'Wiper Fluid Top Up', 'Performance');
    ($this->seedInclusion)($svc, 'Exterior Inspection', 'Additional');
    ($this->seedService)('front-brake-pad');

    $this->artisan('corrections:apply-b7')->assertExitCode(0);

    $this->artisan('corrections:apply-b7')
        ->expectsOutputToContain('WARN: 0 rows matched for SP-PEND-1')
        ->expectsOutputToContain('WARN: 0 rows matched for SP-PEND-2')
        ->expectsOutputToContain('WARN: 0 services eligible for SP-PEND-3')
        ->expectsOutputToContain('Updated 0 inclusion groups + 0 interval_info rows.')
        ->assertExitCode(0);
});
