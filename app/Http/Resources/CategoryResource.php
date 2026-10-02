<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;

final class CategoryResource extends JsonApiResource
{
    public function toId(Request $request): string
    {
        /** @var Category $category */
        $category = $this->resource;

        return $category->id;
    }

    public function toType(Request $request): string
    {
        return 'categories';
    }

    /** @return array<string, mixed> */
    public function toAttributes(Request $request): array
    {
        /** @var Category $category */
        $category = $this->resource;

        return ['name' => $category->name, 'slug' => $category->slug];
    }
}
