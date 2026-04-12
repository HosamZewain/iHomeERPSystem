<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\PurchaseInvoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PurchaseInvoiceItem>
 */
class PurchaseInvoiceItemFactory extends Factory
{
    public function definition(): array
    {
        $quantity = fake()->randomFloat(2, 1, 20);
        $unitCost = fake()->randomFloat(2, 100, 5000);

        return [
            'purchase_invoice_id' => PurchaseInvoice::factory(),
            'product_id' => Product::factory(),
            'quantity' => $quantity,
            'unit_cost' => $unitCost,
            'line_total' => round($quantity * $unitCost, 2),
        ];
    }
}
