<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Requests\Admin\SaveProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;

#[Group(name: 'Admin')]
#[Endpoint(title: 'Update product', description: 'Replace a product\'s fields.')]
final class UpdateProductController
{
    public function __invoke(SaveProductRequest $request, string $id): ProductResource
    {
        $model = Product::query()->findOrFail($id);
        $model->update([...$request->validated(), 'description' => $request->validated('description') ?? '']);

        return ProductResource::make($model->load('category'));
    }
}
