<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\OrderStatus;
use App\Http\Requests\Admin\UpdateOrderStatusRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;

#[Group(name: 'Admin')]
#[Endpoint(title: 'Update order status', description: 'pending→paid|cancelled, paid→shipped|cancelled. Cancelling restocks items.')]
final class UpdateOrderStatusController
{
    public function __invoke(UpdateOrderStatusRequest $request, string $id): OrderResource
    {
        $next = OrderStatus::from($request->string('status')->toString());

        $updated = DB::transaction(function () use ($id, $next): ?Order {
            $locked = Order::query()->with('items')->lockForUpdate()->findOrFail($id);

            if ( ! $locked->status->canTransitionTo($next)) {
                return null;
            }

            if (OrderStatus::Cancelled === $next) {
                foreach ($locked->items as $item) {
                    Product::query()->whereKey($item->product_id)->increment('stock', $item->qty);
                }
            }

            $locked->update(['status' => $next]);

            return $locked;
        });

        if (null === $updated) {
            throw ValidationException::withMessages(['status' => [__('api.orders.invalid_transition')]]);
        }

        return OrderResource::make($updated->load(['items.product', 'user']));
    }
}
