<?php

declare(strict_types=1);

namespace App\Http\Payloads\V1;

use App\Http\Requests\Orders\CreateOrderRequest;

final readonly class CreateOrderPayload
{
    /**
     * @param  list<array{product_id: string, qty: int}>  $items
     * @param  array<string, string>  $shippingAddress
     */
    public function __construct(
        public array $items,
        public array $shippingAddress,
    ) {}

    public static function fromRequest(CreateOrderRequest $request): self
    {
        /** @var array{items: list<array{product_id: string, qty: int}>, shipping_address: array<string, string>} $data */
        $data = $request->validated();

        return new self($data['items'], $data['shipping_address']);
    }
}
