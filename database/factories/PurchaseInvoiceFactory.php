<?php

namespace Database\Factories;

use App\Enums\PurchaseInvoiceStatus;
use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PurchaseInvoice>
 */
class PurchaseInvoiceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'invoice_number' => PurchaseInvoice::nextInvoiceNumber(),
            'supplier_id' => Supplier::factory(),
            'invoice_date' => fake()->date(),
            'notes' => fake()->optional()->sentence(),
            'subtotal' => 0,
            'total' => 0,
            'status' => PurchaseInvoiceStatus::Draft->value,
            'created_by' => User::factory(),
        ];
    }
}
