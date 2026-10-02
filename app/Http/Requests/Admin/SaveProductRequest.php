<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SaveProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // gated by the `can:admin` route middleware
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        // The admin controllers take the id as a plain string (no implicit model binding),
        // so route('id') is the raw id on update and null on create.
        $product = $this->route('id');

        return [
            'category_id' => ['required', 'string', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required', 'string', 'max:255', 'alpha_dash:ascii',
                Rule::unique('products', 'slug')->ignore(is_string($product) ? $product : null),
            ],
            'description' => ['present', 'nullable', 'string', 'max:5000'],
            'price_cents' => ['required', 'integer', 'min:0', 'max:10000000'],
            'stock' => ['required', 'integer', 'min:0', 'max:1000000'],
            'image_url' => ['present', 'nullable', 'url', 'max:2048'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
