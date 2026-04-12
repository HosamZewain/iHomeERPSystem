<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\SalesInvoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SalesInvoiceItem>
 */
class SalesInvoiceItemFactory extends Factory
{
    public function definition(): array
    {
        $quantity = fake()->randomFloat(2, 1, 5);
        $unitPrice = fake()->randomFloat(2, 500, 5000);

        return [
            'sales_invoice_id' => SalesInvoice::factory(),
            'product_id' => Product::factory(),
            'quantity' => $quantity,
            'unit_sale_price' => $unitPrice,
            'item_discount_type' => SalesInvoice::DISCOUNT_FIXED,
            'item_discount_value' => 0,
            'item_discount_amount' => 0,
            'cost_at_sale_time' => 0,
            'line_total' => round($quantity * $unitPrice, 2),
            'line_profit' => 0,
        ];
    }
}
