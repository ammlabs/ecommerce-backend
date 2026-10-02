<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class PlaceOrder
{
    /**
     * Create an order atomically: lock products, validate stock, decrement, price from the DB.
     *
     * @param  list<array{product_id: string, qty: int}>  $lines
     * @param  array<string, string>  $shippingAddress
     *
     * @throws ValidationException when a product is unavailable or short on stock (nothing is written)
     */
    public function handle(User $user, array $lines, array $shippingAddress): Order
    {
        return DB::transaction(function () use ($user, $lines, $shippingAddress): Order {
            $products = Product::query()
                ->whereIn('id', array_column($lines, 'product_id'))
                ->orderBy('id') // stable lock order avoids deadlocks between concurrent checkouts
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $errors = [];
            $total = 0;

            foreach ($lines as $index => $line) {
                $product = $products->get($line['product_id']);

                if (null === $product || ! $product->is_active) {
                    $errors["items.{$index}.product_id"] = [
                        __('api.orders.product_unavailable', ['product' => $line['product_id']]),
                    ];

                    continue;
                }

                if ($product->stock < $line['qty']) {
                    $errors["items.{$index}.qty"] = [
                        __('api.orders.insufficient_stock', ['product' => $line['product_id']]),
                    ];

                    continue;
                }

                $total += $product->price_cents * $line['qty'];
            }

            if ([] !== $errors) {
                throw ValidationException::withMessages($errors);
            }

            $order = Order::query()->create([
                'user_id' => $user->getKey(),
                'status' => OrderStatus::Pending,
                'total_cents' => $total,
                'shipping_address' => $shippingAddress,
            ]);

            foreach ($lines as $line) {
                /** @var Product $product */
                $product = $products->get($line['product_id']);
                $product->decrement('stock', $line['qty']);
                $order->items()->create([
                    'product_id' => $product->getKey(),
                    'qty' => $line['qty'],
                    'unit_price_cents' => $product->price_cents,
                ]);
            }

            return $order->load('items.product');
        });
    }
}
