<?php

declare(strict_types=1);

use App\Enums\OrderStatus;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function actingAsAdmin(): User
{
    $admin = User::factory()->admin()->create();
    Sanctum::actingAs($admin, ['auth:me']);

    return $admin;
}

/** @return array<string, mixed> */
function productBody(Category $category, array $overrides = []): array
{
    return array_merge([
        'category_id' => $category->id,
        'name' => 'Trail Runner',
        'slug' => 'trail-runner',
        'description' => 'Fast shoe',
        'price_cents' => 8999,
        'stock' => 10,
        'image_url' => null,
        'is_active' => true,
    ], $overrides);
}

it('exposes is_admin on the authenticated user', function (): void {
    Sanctum::actingAs(User::factory()->admin()->create(), ['auth:me']);

    $this->getJson('/v1/auth/me')->assertOk()->assertJsonPath('data.attributes.is_admin', true);
});

it('forbids non-admins and guests from admin routes', function (): void {
    $this->getJson('/v1/admin/products')->assertUnauthorized();

    Sanctum::actingAs(User::factory()->create(), ['auth:me']);
    $this->getJson('/v1/admin/products')->assertForbidden();
    $this->getJson('/v1/admin/orders')->assertForbidden();
});

it('cannot mass-assign is_admin through registration', function (): void {
    $this->postJson('/v1/auth/register', [
        'name' => 'Eve',
        'email' => 'eve@example.com',
        'password' => 'password123',
        'device_name' => 'test',
        'is_admin' => true,
    ])->assertCreated();

    expect(User::query()->where('email', 'eve@example.com')->firstOrFail()->is_admin)->toBeFalse();
});

it('creates, shows, updates and lists products including inactive ones', function (): void {
    actingAsAdmin();
    $category = Category::factory()->create();

    $id = $this->postJson('/v1/admin/products', productBody($category))
        ->assertCreated()
        ->assertJsonPath('data.attributes.slug', 'trail-runner')
        ->json('data.id');

    $this->putJson("/v1/admin/products/{$id}", productBody($category, ['name' => 'Trail Runner 2', 'is_active' => false]))
        ->assertOk()
        ->assertJsonPath('data.attributes.name', 'Trail Runner 2');

    $this->getJson("/v1/admin/products/{$id}")->assertOk()->assertJsonPath('data.attributes.is_active', false);
    $this->getJson('/v1/admin/products')->assertOk()->assertJsonCount(1, 'data');
});

it('validates product input', function (): void {
    actingAsAdmin();
    $category = Category::factory()->create();
    Product::factory()->for($category)->create(['slug' => 'taken']);

    $this->postJson('/v1/admin/products', productBody($category, ['slug' => 'taken']))->assertStatus(422);
    $this->postJson('/v1/admin/products', productBody($category, ['price_cents' => -1]))->assertStatus(422);
    $this->postJson('/v1/admin/products', productBody($category, ['stock' => -1]))->assertStatus(422);
    $this->postJson('/v1/admin/products', productBody($category, ['slug' => 'Bad Slug!']))->assertStatus(422);
});

it('allows an update to keep its own slug', function (): void {
    actingAsAdmin();
    $product = Product::factory()->create(['slug' => 'mine']);

    $this->putJson("/v1/admin/products/{$product->id}", productBody($product->category, ['slug' => 'mine']))->assertOk();
});

it('deletes an unordered product but refuses one that has orders', function (): void {
    actingAsAdmin();
    $free = Product::factory()->create();
    $ordered = Product::factory()->create();
    OrderItem::query()->create([
        'order_id' => Order::factory()->create()->id,
        'product_id' => $ordered->id,
        'qty' => 1,
        'unit_price_cents' => 100,
    ]);

    $this->deleteJson("/v1/admin/products/{$free->id}")->assertNoContent();
    $this->deleteJson("/v1/admin/products/{$ordered->id}")->assertStatus(409);
    expect(Product::query()->whereKey($ordered->id)->exists())->toBeTrue();
});

it('lists all orders with customer and filters by status', function (): void {
    actingAsAdmin();
    Order::factory()->create(['status' => OrderStatus::Paid]);
    Order::factory()->create(['status' => OrderStatus::Pending]);

    $this->getJson('/v1/admin/orders')->assertOk()->assertJsonCount(2, 'data')
        ->assertJsonStructure(['data' => [['attributes' => ['customer' => ['name', 'email']]]]]);
    $this->getJson('/v1/admin/orders?status=paid')->assertOk()->assertJsonCount(1, 'data');
});

it('moves an order forward through its lifecycle', function (): void {
    actingAsAdmin();
    $order = Order::factory()->create(['status' => OrderStatus::Pending]);

    $this->patchJson("/v1/admin/orders/{$order->id}", ['status' => 'paid'])->assertOk()->assertJsonPath('data.attributes.status', 'paid');
    $this->patchJson("/v1/admin/orders/{$order->id}", ['status' => 'shipped'])->assertOk();
});

it('refuses illegal transitions', function (): void {
    actingAsAdmin();
    $shipped = Order::factory()->create(['status' => OrderStatus::Shipped]);
    $pending = Order::factory()->create(['status' => OrderStatus::Pending]);

    $this->patchJson("/v1/admin/orders/{$shipped->id}", ['status' => 'cancelled'])->assertStatus(422);
    $this->patchJson("/v1/admin/orders/{$pending->id}", ['status' => 'shipped'])->assertStatus(422);
    $this->patchJson("/v1/admin/orders/{$pending->id}", ['status' => 'bogus'])->assertStatus(422);
});

it('restocks items when an order is cancelled', function (): void {
    actingAsAdmin();
    $product = Product::factory()->create(['stock' => 3]);
    $order = Order::factory()->create(['status' => OrderStatus::Paid]);
    OrderItem::query()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'qty' => 2,
        'unit_price_cents' => 100,
    ]);

    $this->patchJson("/v1/admin/orders/{$order->id}", ['status' => 'cancelled'])->assertOk();

    expect($product->fresh()->stock)->toBe(5);
    $this->patchJson("/v1/admin/orders/{$order->id}", ['status' => 'cancelled'])->assertStatus(422);
    expect($product->fresh()->stock)->toBe(5);
});

it('accepts an empty description', function (): void {
    actingAsAdmin();
    $category = Category::factory()->create();

    $this->postJson('/v1/admin/products', productBody($category, ['description' => '']))->assertCreated();
});
