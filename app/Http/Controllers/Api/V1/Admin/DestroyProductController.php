<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;

#[Group(name: 'Admin')]
#[Endpoint(title: 'Delete product', description: 'Delete a product that has never been ordered; otherwise 409 (deactivate it instead).')]
final class DestroyProductController
{
    public function __invoke(string $id): JsonResponse|Response
    {
        $model = Product::query()->findOrFail($id);

        if ($model->orderItems()->exists()) {
            return new JsonResponse(['message' => __('api.admin.product_has_orders')], 409);
        }

        $model->delete();

        return response()->noContent();
    }
}
