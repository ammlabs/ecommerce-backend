<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;

#[Group(name: 'Admin')]
#[Endpoint(title: 'List products (admin)', description: 'All products including inactive, newest first.')]
final class ListProductsController
{
    public function __invoke(): AnonymousResourceCollection
    {
        return ProductResource::collection(
            Product::query()->with('category')->latest()->paginate(20),
        );
    }
}
