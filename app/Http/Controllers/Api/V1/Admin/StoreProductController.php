<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Requests\Admin\SaveProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;

#[Group(name: 'Admin')]
#[Endpoint(title: 'Create product', description: 'Create a product.')]
final class StoreProductController
{
    public function __invoke(SaveProductRequest $request): JsonResponse
    {
        $product = Product::query()->create([...$request->validated(), 'description' => $request->validated('description') ?? '']);

        return ProductResource::make($product->load('category'))->response()->setStatusCode(201);
    }
}
