<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Http\Payloads\V1\ListProductsPayload;
use App\Http\Requests\Catalog\ListProductsRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Unauthenticated;

#[Group(name: 'Catalog')]
#[Endpoint(title: 'List products', description: 'Paginated active products, filterable by category, name search, or ids.')]
#[Unauthenticated]
final class ListProductsController
{
    public function __invoke(ListProductsRequest $request): AnonymousResourceCollection
    {
        $payload = ListProductsPayload::fromRequest($request);

        $query = Product::query()->active()->with('category');

        if (null !== $payload->category) {
            $query->whereRelation('category', 'slug', $payload->category);
        }

        if (null !== $payload->search) {
            $needle = '%' . addcslashes(mb_strtolower($payload->search), '%_\\') . '%';
            $query->whereRaw("lower(name) like ? escape '\\'", [$needle]);
        }

        if ([] !== $payload->ids) {
            $query->whereIn('id', $payload->ids);
        }

        return ProductResource::collection(
            $query->orderBy('name')->paginate($payload->perPage)->withQueryString(),
        );
    }
}
