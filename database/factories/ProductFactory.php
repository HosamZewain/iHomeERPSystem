<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $cost = fake()->randomFloat(2, 300, 8000);

        return [
            'name' => fake()->unique()->words(3, true),
            'internal_sku' => 'IH-'.strtoupper(Str::random(8)),
            'barcode' => fake()->optional()->ean13(),
            'image_path' => null,
            'category_id' => Category::factory(),
            'supplier_id' => Supplier::factory(),
            'sale_price' => $cost * fake()->randomFloat(2, 1.2, 1.8),
            'current_average_cost' => $cost,
            'minimum_stock_alert_level' => fake()->numberBetween(0, 10),
            'is_active' => fake()->boolean(90),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
