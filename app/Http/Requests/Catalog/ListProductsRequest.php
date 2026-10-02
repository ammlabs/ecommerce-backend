<?php

declare(strict_types=1);

namespace App\Http\Requests\Catalog;

use Illuminate\Foundation\Http\FormRequest;

final class ListProductsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'category' => ['sometimes', 'string', 'max:255'],
            'search' => ['sometimes', 'string', 'max:100'],
            'ids' => ['sometimes', 'array', 'max:50'],
            'ids.*' => ['string', 'size:26'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ];
    }
}
