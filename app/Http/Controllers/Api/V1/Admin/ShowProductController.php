<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Resources\ProductResource;
use App\Models\Product;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;

#[Group(name: 'Admin')]
#[Endpoint(title: 'Show product (admin)', description: 'One product by id, active or not.')]
final class ShowProductController
{
    public function __invoke(string $id): ProductResource
    {
        return ProductResource::make(Product::query()->with('category')->findOrFail($id));
    }
}
