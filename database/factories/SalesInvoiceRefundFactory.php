<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceRefund;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SalesInvoiceRefundFactory extends Factory
{
    protected $model = SalesInvoiceRefund::class;

    public function definition(): array
    {
        return [
            'sales_invoice_id' => SalesInvoice::factory(),
            'refund_number' => 'RFD-' . fake()->unique()->numerify('2026-#####'),
            'refund_date' => now()->toDateString(),
            'amount' => fake()->randomFloat(2, 1, 5000),
            'payment_method' => PaymentMethod::Cash->value,
            'reference_number' => fake()->optional()->bothify('REF-####'),
            'notes' => fake()->optional()->sentence(),
            'created_by' => User::factory(),
        ];
    }
}
