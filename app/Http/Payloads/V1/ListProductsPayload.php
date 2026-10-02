<?php

declare(strict_types=1);

namespace App\Http\Payloads\V1;

use App\Http\Requests\Catalog\ListProductsRequest;

final readonly class ListProductsPayload
{
    /** @param list<string> $ids */
    public function __construct(
        public ?string $category,
        public ?string $search,
        public array $ids,
        public int $perPage,
    ) {}

    public static function fromRequest(ListProductsRequest $request): self
    {
        /** @var array{category?: string, search?: string, ids?: list<string>, per_page?: int} $data */
        $data = $request->validated();
        $search = isset($data['search']) ? mb_trim($data['search']) : '';

        return new self(
            category: $data['category'] ?? null,
            search: '' === $search ? null : $search,
            ids: $data['ids'] ?? [],
            perPage: $data['per_page'] ?? 12,
        );
    }
}
