<?php

namespace Database\Factories;

use App\Enums\QuotationStatus;
use App\Models\Customer;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Quotation>
 */
class QuotationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'quotation_number' => Quotation::nextQuotationNumber(),
            'customer_id' => Customer::factory(),
            'quotation_date' => fake()->date(),
            'notes' => fake()->optional()->sentence(),
            'subtotal' => 0,
            'invoice_discount_type' => Quotation::DISCOUNT_FIXED,
            'invoice_discount_value' => 0,
            'invoice_discount_amount' => 0,
            'installation_enabled' => false,
            'installation_pricing_mode' => Quotation::INSTALLATION_FIXED,
            'installation_percentage_value' => 0,
            'installation_fixed_amount' => 0,
            'installation_total' => 0,
            'installation_notes' => null,
            'total' => 0,
            'status' => QuotationStatus::Draft->value,
            'created_by' => User::factory(),
        ];
    }
}
