<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\OrderStatus;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\QueryParam;

#[Group(name: 'Admin')]
#[Endpoint(title: 'List orders (admin)', description: 'All orders with customer and items, newest first.')]
#[QueryParam('status', type: 'string', description: 'Filter by status.', required: false, example: 'paid')]
final class ListOrdersController
{
    public function __invoke(Request $request): AnonymousResourceCollection
    {
        $status = OrderStatus::tryFrom((string) $request->query('status'));

        $query = Order::query()->with(['items.product', 'user']);

        if (null !== $status) {
            $query->where('status', $status->value);
        }

        return OrderResource::collection($query->latest()->paginate(20)->withQueryString());
    }
}
