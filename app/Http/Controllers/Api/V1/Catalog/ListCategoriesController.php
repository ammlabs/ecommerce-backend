<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Unauthenticated;

#[Group(name: 'Catalog')]
#[Endpoint(title: 'List categories', description: 'All categories, alphabetical.')]
#[Unauthenticated]
final class ListCategoriesController
{
    public function __invoke(): AnonymousResourceCollection
    {
        return CategoryResource::collection(Category::query()->orderBy('name')->get());
    }
}
