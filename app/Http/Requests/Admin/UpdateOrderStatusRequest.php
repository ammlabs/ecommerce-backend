<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\OrderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // gated by the `can:admin` route middleware
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['status' => ['required', 'string', Rule::enum(OrderStatus::class)]];
    }
}
