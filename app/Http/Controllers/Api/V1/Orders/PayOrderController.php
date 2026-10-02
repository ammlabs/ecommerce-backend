<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Orders;

use App\Enums\OrderStatus;
use App\Http\Resources\OrderResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;

#[Group(name: 'Orders')]
#[Endpoint(title: 'Pay order (stub)', description: 'Marks a pending order as paid. Replace with a Stripe PaymentIntent + webhook.')]
final class PayOrderController
{
    public function __invoke(Request $request, string $id): JsonResponse|OrderResource
    {
        /** @var User $user */
        $user = $request->user();
        $model = $user->orders()->findOrFail($id);

        if (OrderStatus::Pending !== $model->status) {
            return new JsonResponse(['message' => __('api.orders.not_payable')], 409);
        }

        // ponytail: stub payment — no provider call. Swap for a Stripe PaymentIntent + webhook that sets Paid.
        $model->update(['status' => OrderStatus::Paid]);

        return OrderResource::make($model->load('items.product'));
    }
}
