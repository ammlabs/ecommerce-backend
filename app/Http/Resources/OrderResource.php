<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;

final class OrderResource extends JsonApiResource
{
    public function toId(Request $request): string
    {
        /** @var Order $order */
        $order = $this->resource;

        return $order->id;
    }

    public function toType(Request $request): string
    {
        return 'orders';
    }

    /** @return array<string, mixed> */
    public function toAttributes(Request $request): array
    {
        /** @var Order $order */
        $order = $this->resource;
        $customer = $order->relationLoaded('user') ? $order->user : null;

        return [
            'status' => $order->status->value,
            'total_cents' => $order->total_cents,
            'shipping_address' => $order->shipping_address,
            'items' => $order->relationLoaded('items')
                ? $order->items->map(fn(OrderItem $item): array => [
                    'product_id' => $item->product_id,
                    'name' => $item->relationLoaded('product') ? $item->product?->name : null,
                    'qty' => $item->qty,
                    'unit_price_cents' => $item->unit_price_cents,
                ])->values()->all()
                : [],
            'customer' => null === $customer ? null : ['name' => $customer->name, 'email' => $customer->email],
            'created_at' => $order->created_at?->toAtomString(),
        ];
    }
}
