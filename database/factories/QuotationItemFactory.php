<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Quotation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\QuotationItem>
 */
class QuotationItemFactory extends Factory
{
    public function definition(): array
    {
        $quantity = fake()->randomFloat(2, 1, 10);
        $unitSalePrice = fake()->randomFloat(2, 500, 10000);
        $discountValue = fake()->randomFloat(2, 0, 500);
        $lineTotal = Quotation::lineTotal($quantity, $unitSalePrice, Quotation::DISCOUNT_FIXED, $discountValue);

        return [
            'quotation_id' => Quotation::factory(),
            'product_id' => Product::factory(),
            'sort_order' => fake()->numberBetween(1, 10),
            'quantity' => $quantity,
            'unit_sale_price' => $unitSalePrice,
            'item_discount_type' => Quotation::DISCOUNT_FIXED,
            'item_discount_value' => $discountValue,
            'item_discount_amount' => round(min($discountValue, $quantity * $unitSalePrice), 2),
            'line_total' => $lineTotal,
        ];
    }
}
