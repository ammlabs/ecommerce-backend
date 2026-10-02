<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Product> */
final class ProductFactory extends Factory
{
    protected $model = Product::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $name = $this->faker->unique()->words(3, true);

        return [
            'category_id' => Category::factory(),
            'name' => $name,
            'slug' => Str::slug($name) . '-' . Str::lower(Str::random(5)),
            'description' => $this->faker->paragraph(),
            'price_cents' => $this->faker->numberBetween(500, 20000),
            'stock' => 10,
            'image_url' => null,
            'is_active' => true,
        ];
    }
}
