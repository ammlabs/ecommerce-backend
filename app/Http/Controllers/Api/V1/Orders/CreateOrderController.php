<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Orders;

use App\Actions\PlaceOrder;
use App\Http\Payloads\V1\CreateOrderPayload;
use App\Http\Requests\Orders\CreateOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;

#[Group(name: 'Orders')]
#[Endpoint(title: 'Place order', description: 'Create an order from {product_id, qty} lines. Prices come from the database. Supports Idempotency-Key.')]
final class CreateOrderController
{
    public function __invoke(CreateOrderRequest $request, PlaceOrder $placeOrder): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $payload = CreateOrderPayload::fromRequest($request);

        $order = $placeOrder->handle($user, $payload->items, $payload->shippingAddress);

        return OrderResource::make($order)->response()->setStatusCode(201);
    }
}
