<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Models\SalesInvoice;
use App\Models\SalesInvoicePayment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SalesInvoicePaymentFactory extends Factory
{
    protected $model = SalesInvoicePayment::class;

    public function definition(): array
    {
        return [
            'sales_invoice_id' => SalesInvoice::factory(),
            'receipt_number' => 'RCV-' . fake()->unique()->numerify('2026-#####'),
            'payment_date' => now()->toDateString(),
            'amount' => fake()->randomFloat(2, 100, 5000),
            'payment_method' => PaymentMethod::Cash->value,
            'reference_number' => fake()->optional()->bothify('REF-####'),
            'notes' => fake()->optional()->sentence(),
            'remaining_amount_after' => fake()->randomFloat(2, 0, 5000),
            'received_by' => User::factory(),
            'created_by' => User::factory(),
        ];
    }
}
