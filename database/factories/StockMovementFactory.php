<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StockMovement>
 */
class StockMovementFactory extends Factory
{
    public function definition(): array
    {
        $quantity = fake()->randomFloat(2, 1, 20);
        $unitCost = fake()->randomFloat(2, 100, 5000);

        return [
            'product_id' => Product::factory(),
            'movement_type' => StockMovement::TYPE_PURCHASE_IN,
            'source_type' => StockMovement::SOURCE_PURCHASE_ITEM,
            'source_id' => fake()->unique()->numberBetween(1, 1000000),
            'created_by' => User::factory(),
            'quantity' => $quantity,
            'balance_after' => $quantity,
            'unit_cost' => $unitCost,
            'total_cost' => round($quantity * $unitCost, 2),
            'movement_date' => fake()->date(),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
