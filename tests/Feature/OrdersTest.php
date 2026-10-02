<?php

declare(strict_types=1);

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

/** @return array<string, string> */
function shippingAddress(): array
{
    return [
        'name' => 'Jane Doe',
        'line1' => '1 Main St',
        'city' => 'Springfield',
        'postal_code' => '12345',
        'country' => 'US',
    ];
}

function customer(): User
{
    $user = User::factory()->create();
    Sanctum::actingAs($user, ['orders:read', 'orders:write']);

    return $user;
}

it('creates an order, decrements stock and prices from the database', function (): void {
    customer();
    $a = Product::factory()->create(['price_cents' => 1000, 'stock' => 5]);
    $b = Product::factory()->create(['price_cents' => 250, 'stock' => 5]);

    $this->postJson('/v1/orders', [
        'items' => [
            ['product_id' => $a->id, 'qty' => 2, 'price_cents' => 1],
            ['product_id' => $b->id, 'qty' => 1],
        ],
        'total_cents' => 1,
        'shipping_address' => shippingAddress(),
    ])
        ->assertCreated()
        ->assertJsonPath('data.type', 'orders')
        ->assertJsonPath('data.attributes.status', 'pending')
        ->assertJsonPath('data.attributes.total_cents', 2250)
        ->assertJsonCount(2, 'data.attributes.items');

    expect($a->fresh()->stock)->toBe(3);
    expect($b->fresh()->stock)->toBe(4);
    expect(Order::query()->count())->toBe(1);
});

it('rejects an order that exceeds stock and changes nothing', function (): void {
    customer();
    $ok = Product::factory()->create(['stock' => 5]);
    $low = Product::factory()->create(['stock' => 1]);

    $response = $this->postJson('/v1/orders', [
        'items' => [
            ['product_id' => $ok->id, 'qty' => 2],
            ['product_id' => $low->id, 'qty' => 2],
        ],
        'shipping_address' => shippingAddress(),
    ])->assertStatus(422);

    expect($response->json('errors')['items.1.qty'][0])->toContain($low->id);
    expect($ok->fresh()->stock)->toBe(5);
    expect($low->fresh()->stock)->toBe(1);
    expect(Order::query()->count())->toBe(0);
});

it('refuses a second order once stock is exhausted', function (): void {
    customer();
    $p = Product::factory()->create(['stock' => 3]);
    $body = ['items' => [['product_id' => $p->id, 'qty' => 2]], 'shipping_address' => shippingAddress()];

    $this->postJson('/v1/orders', $body)->assertCreated();
    $this->postJson('/v1/orders', $body)->assertStatus(422);

    expect($p->fresh()->stock)->toBe(1);
});

it('rejects inactive products', function (): void {
    customer();
    $p = Product::factory()->create(['is_active' => false]);

    $this->postJson('/v1/orders', [
        'items' => [['product_id' => $p->id, 'qty' => 1]],
        'shipping_address' => shippingAddress(),
    ])->assertStatus(422);
});

it('rejects duplicate product lines, empty carts and bad quantities', function (): void {
    customer();
    $p = Product::factory()->create(['stock' => 10]);

    $this->postJson('/v1/orders', [
        'items' => [['product_id' => $p->id, 'qty' => 1], ['product_id' => $p->id, 'qty' => 1]],
        'shipping_address' => shippingAddress(),
    ])->assertStatus(422);

    $this->postJson('/v1/orders', ['items' => [], 'shipping_address' => shippingAddress()])->assertStatus(422);

    $this->postJson('/v1/orders', [
        'items' => [['product_id' => $p->id, 'qty' => 0]],
        'shipping_address' => shippingAddress(),
    ])->assertStatus(422);
});

it('replays an idempotent order request without creating a second order', function (): void {
    customer();
    $p = Product::factory()->create(['stock' => 5]);
    $body = ['items' => [['product_id' => $p->id, 'qty' => 1]], 'shipping_address' => shippingAddress()];
    $headers = ['Idempotency-Key' => 'checkout-attempt-0001'];

    $first = $this->postJson('/v1/orders', $body, $headers)->assertCreated();
    $second = $this->postJson('/v1/orders', $body, $headers)->assertCreated()->assertHeader('Idempotency-Replayed', 'true');

    expect($second->json('data.id'))->toBe($first->json('data.id'));
    expect(Order::query()->count())->toBe(1);
    expect($p->fresh()->stock)->toBe(4);
});

it('lists only my orders and hides other users orders', function (): void {
    $me = customer();
    $mine = Order::factory()->for($me)->create();
    $theirs = Order::factory()->create();

    $this->getJson('/v1/orders')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $mine->id);

    $this->getJson('/v1/orders/' . $mine->id)->assertOk();
    $this->getJson('/v1/orders/' . $theirs->id)->assertNotFound();
});

it('pays a pending order once', function (): void {
    $me = customer();
    $order = Order::factory()->for($me)->create();

    $this->postJson("/v1/orders/{$order->id}/pay")
        ->assertOk()
        ->assertJsonPath('data.attributes.status', 'paid');

    $this->postJson("/v1/orders/{$order->id}/pay")->assertStatus(409);
    expect($order->fresh()->status)->toBe(OrderStatus::Paid);
});

it('does not let me pay someone elses order', function (): void {
    customer();
    $theirs = Order::factory()->create();

    $this->postJson("/v1/orders/{$theirs->id}/pay")->assertNotFound();
});

it('requires authentication and the right token ability', function (): void {
    $this->getJson('/v1/orders')->assertUnauthorized();

    Sanctum::actingAs(User::factory()->create(), ['auth:me']);
    $this->getJson('/v1/orders')->assertForbidden();
    $this->postJson('/v1/orders', [])->assertForbidden();
});
