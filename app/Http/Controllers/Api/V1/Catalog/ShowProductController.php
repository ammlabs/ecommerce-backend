<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Http\Resources\ProductResource;
use App\Models\Product;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Unauthenticated;

#[Group(name: 'Catalog')]
#[Endpoint(title: 'Show product', description: 'Fetch one active product by slug.')]
#[Unauthenticated]
final class ShowProductController
{
    public function __invoke(string $slug): ProductResource
    {
        $product = Product::query()->active()->with('category')->where('slug', $slug)->firstOrFail();

        return ProductResource::make($product);
    }
}
