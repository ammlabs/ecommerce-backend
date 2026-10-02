<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;

final class ProductResource extends JsonApiResource
{
    public function toId(Request $request): string
    {
        /** @var Product $product */
        $product = $this->resource;

        return $product->id;
    }

    public function toType(Request $request): string
    {
        return 'products';
    }

    /** @return array<string, mixed> */
    public function toAttributes(Request $request): array
    {
        /** @var Product $product */
        $product = $this->resource;
        $category = $product->relationLoaded('category') ? $product->category : null;

        return [
            'name' => $product->name,
            'slug' => $product->slug,
            'description' => $product->description,
            'price_cents' => $product->price_cents,
            'stock' => $product->stock,
            'image_url' => $product->image_url,
            'is_active' => $product->is_active,
            'category' => null === $category ? null : [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
            ],
        ];
    }
}
