<?php

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->seedService = function (): Service {
        return Service::factory()->create([
            'category_id' => ServiceCategory::factory()->create()->id,
            'base_price'  => 1000.00,
        ]);
    };

    $this->makeCart = function (array $overrides = []): Cart {
        return Cart::create(array_merge([
            'user_id'      => null,
            'session_uuid' => (string) Str::uuid(),
            'currency'     => 'INR',
            'expires_at'   => now()->addDays(7),
            'status'       => 'active',
        ], $overrides));
    };

    $this->backdate = function (Cart $cart, int $days): void {
        $ts = now()->subDays($days);
        Cart::query()->where('id', $cart->id)->update([
            'created_at' => $ts,
            'updated_at' => $ts,
        ]);
        CartItem::query()->where('cart_id', $cart->id)->update([
            'created_at' => $ts,
            'updated_at' => $ts,
        ]);
    };
});

it('dry-run reports eligible count but performs zero deletes', function () {
    $stale = ($this->makeCart)();
    ($this->backdate)($stale, 30);
    $before = Cart::count();

    $this->artisan('carts:prune', ['--dry-run' => true])
        ->expectsOutputToContain('Eligible for prune: 1')
        ->expectsOutputToContain('--dry-run set: no deletes performed.')
        ->assertExitCode(0);

    expect(Cart::count())->toBe($before);
    expect(Cart::find($stale->id))->not->toBeNull();
});

it('deletes guest carts older than the default 14-day threshold', function () {
    $stale = ($this->makeCart)();
    ($this->backdate)($stale, 20);

    $this->artisan('carts:prune')->assertExitCode(0);

    expect(Cart::find($stale->id))->toBeNull();
});

it('preserves user carts of any age (user_id NOT NULL is sacred)', function () {
    $user = User::factory()->create();
    $userCart = ($this->makeCart)(['user_id' => $user->id, 'session_uuid' => null]);
    ($this->backdate)($userCart, 365);

    $this->artisan('carts:prune')->assertExitCode(0);

    expect(Cart::find($userCart->id))->not->toBeNull();
});

it('preserves guest carts updated within the threshold', function () {
    $fresh = ($this->makeCart)();
    ($this->backdate)($fresh, 5);

    $this->artisan('carts:prune')->assertExitCode(0);

    expect(Cart::find($fresh->id))->not->toBeNull();
});

it('preserves an old empty cart whose owner came back yesterday (updated_at fresh)', function () {
    $cart = ($this->makeCart)();
    Cart::query()->where('id', $cart->id)->update([
        'created_at' => now()->subDays(60),
        'updated_at' => now()->subDay(),
    ]);

    $this->artisan('carts:prune')->assertExitCode(0);

    expect(Cart::find($cart->id))->not->toBeNull();
});

it('preserves an old guest cart whose items were touched recently (fresh item shield)', function () {
    $cart = ($this->makeCart)();
    $service = ($this->seedService)();

    CartItem::create([
        'cart_id'              => $cart->id,
        'service_id'           => $service->id,
        'quantity'             => 1,
        'unit_price_snapshot'  => 1000.00,
    ]);

    Cart::query()->where('id', $cart->id)->update([
        'created_at' => now()->subDays(30),
        'updated_at' => now()->subDays(30),
    ]);

    $this->artisan('carts:prune')->assertExitCode(0);

    expect(Cart::find($cart->id))->not->toBeNull();
});

it('deletes an old guest cart whose items are also old (full cascade)', function () {
    $cart = ($this->makeCart)();
    $service = ($this->seedService)();

    $item = CartItem::create([
        'cart_id'              => $cart->id,
        'service_id'           => $service->id,
        'quantity'             => 1,
        'unit_price_snapshot'  => 1000.00,
    ]);

    ($this->backdate)($cart, 30);

    $this->artisan('carts:prune')->assertExitCode(0);

    expect(Cart::find($cart->id))->toBeNull();
    expect(CartItem::find($item->id))->toBeNull();
});

it('respects --days option (3-day threshold deletes a 5-day-old cart that 14d would keep)', function () {
    $cart = ($this->makeCart)();
    ($this->backdate)($cart, 5);

    $this->artisan('carts:prune', ['--days' => 3])->assertExitCode(0);

    expect(Cart::find($cart->id))->toBeNull();
});

it('is idempotent: second run with nothing to prune is a clean no-op', function () {
    $cart = ($this->makeCart)();
    ($this->backdate)($cart, 30);

    $this->artisan('carts:prune')->assertExitCode(0);
    expect(Cart::find($cart->id))->toBeNull();

    $this->artisan('carts:prune')
        ->expectsOutputToContain('Eligible for prune: 0')
        ->expectsOutputToContain('Nothing to prune.')
        ->assertExitCode(0);
});
