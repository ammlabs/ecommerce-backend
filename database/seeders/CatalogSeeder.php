<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

final class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $catalog = [
            'Shoes' => [['Trail Runner', 8999], ['City Sneaker', 6999], ['Leather Boot', 12999], ['Canvas Slip-on', 3999]],
            'Bags' => [['Daypack', 5999], ['Tote', 2999], ['Messenger', 7999], ['Duffel', 9999]],
            'Accessories' => [['Wool Beanie', 1999], ['Leather Belt', 3499], ['Sunglasses', 4999], ['Water Bottle', 2499]],
        ];

        foreach ($catalog as $categoryName => $products) {
            $category = Category::query()->firstOrCreate(
                ['slug' => str($categoryName)->slug()->toString()],
                ['name' => $categoryName],
            );

            foreach ($products as [$name, $price]) {
                Product::query()->firstOrCreate(
                    ['slug' => str($name)->slug()->toString()],
                    [
                        'category_id' => $category->getKey(),
                        'name' => $name,
                        'description' => "{$name} — demo product for the prototype.",
                        'price_cents' => $price,
                        'stock' => 25,
                        'image_url' => 'https://picsum.photos/seed/' . str($name)->slug() . '/600/600',
                        'is_active' => true,
                    ],
                );
            }
        }
    }
}
