<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Orders;

use App\Http\Resources\OrderResource;
use App\Models\User;
use Illuminate\Http\Request;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;

#[Group(name: 'Orders')]
#[Endpoint(title: 'Show my order', description: 'One of the authenticated user\'s orders; 404 for anyone else\'s.')]
final class ShowOrderController
{
    public function __invoke(Request $request, string $id): OrderResource
    {
        /** @var User $user */
        $user = $request->user();

        return OrderResource::make(
            $user->orders()->with('items.product')->findOrFail($id),
        );
    }
}
