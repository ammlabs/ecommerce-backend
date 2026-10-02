<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lists only active products with their category', function (): void {
    $category = Category::factory()->create(['name' => 'Shoes', 'slug' => 'shoes']);
    Product::factory()->for($category)->create(['name' => 'Runner', 'slug' => 'runner']);
    Product::factory()->for($category)->create(['name' => 'Hidden', 'slug' => 'hidden', 'is_active' => false]);

    $this->getJson('/v1/products')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.type', 'products')
        ->assertJsonPath('data.0.attributes.slug', 'runner')
        ->assertJsonPath('data.0.attributes.category.slug', 'shoes')
        ->assertJsonPath('meta.total', 1);
});

it('filters products by category slug', function (): void {
    $shoes = Category::factory()->create(['slug' => 'shoes']);
    $hats = Category::factory()->create(['slug' => 'hats']);
    Product::factory()->for($shoes)->create(['slug' => 'runner']);
    Product::factory()->for($hats)->create(['slug' => 'beanie']);

    $this->getJson('/v1/products?category=hats')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.attributes.slug', 'beanie');
});

it('searches by name case-insensitively', function (): void {
    Product::factory()->create(['name' => 'Blue Runner', 'slug' => 'blue-runner']);
    Product::factory()->create(['name' => 'Red Boot', 'slug' => 'red-boot']);

    $this->getJson('/v1/products?search=RUNNER')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.attributes.slug', 'blue-runner');
});

it('does not treat percent or underscore in search as wildcards', function (): void {
    Product::factory()->create(['name' => 'Blue Runner', 'slug' => 'blue-runner']);

    $this->getJson('/v1/products?search=%25')->assertOk()->assertJsonCount(0, 'data');
    $this->getJson('/v1/products?search=_')->assertOk()->assertJsonCount(0, 'data');
});

it('fetches products by ids', function (): void {
    $a = Product::factory()->create(['slug' => 'a']);
    Product::factory()->create(['slug' => 'b']);

    $this->getJson('/v1/products?ids[]=' . $a->id)
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $a->id);
});

it('rejects an oversized page size', function (): void {
    $this->getJson('/v1/products?per_page=500')->assertStatus(422);
});

it('shows a product by slug', function (): void {
    Product::factory()->create(['slug' => 'runner', 'price_cents' => 4999]);

    $this->getJson('/v1/products/runner')
        ->assertOk()
        ->assertJsonPath('data.attributes.price_cents', 4999);
});

it('returns 404 for a missing or inactive product', function (): void {
    Product::factory()->create(['slug' => 'hidden', 'is_active' => false]);

    $this->getJson('/v1/products/hidden')->assertNotFound();
    $this->getJson('/v1/products/nope')->assertNotFound();
});

it('lists categories alphabetically', function (): void {
    Category::factory()->create(['name' => 'Hats', 'slug' => 'hats']);
    Category::factory()->create(['name' => 'Boots', 'slug' => 'boots']);

    $this->getJson('/v1/categories')
        ->assertOk()
        ->assertJsonPath('data.0.attributes.slug', 'boots')
        ->assertJsonPath('data.1.attributes.slug', 'hats');
});
