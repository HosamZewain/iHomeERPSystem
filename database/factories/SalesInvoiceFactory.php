<?php

namespace Database\Factories;

use App\Enums\SalesChannel;
use App\Enums\SalesInvoiceStatus;
use App\Models\Customer;
use App\Models\SalesInvoice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SalesInvoice>
 */
class SalesInvoiceFactory extends Factory
{
    protected $model = SalesInvoice::class;

    public function definition(): array
    {
        return [
            'invoice_number' => 'INV-' . fake()->unique()->numerify('2026-#####'),
            'quotation_id' => null,
            'customer_id' => Customer::factory(),
            'sales_channel' => SalesChannel::Direct->value,
            'partner_id' => null,
            'invoice_date' => now()->toDateString(),
            'notes' => fake()->optional()->sentence(),
            'subtotal' => 0,
            'invoice_discount_type' => SalesInvoice::DISCOUNT_FIXED,
            'invoice_discount_value' => 0,
            'invoice_discount_amount' => 0,
            'installation_enabled' => false,
            'installation_pricing_mode' => SalesInvoice::INSTALLATION_FIXED,
            'installation_percentage_value' => 0,
            'installation_fixed_amount' => 0,
            'installation_total' => 0,
            'installation_party_type' => SalesInvoice::INSTALLATION_PARTY_NONE,
            'installation_party_reference' => null,
            'installation_payout_amount' => 0,
            'installation_profit' => 0,
            'product_profit' => 0,
            'installation_notes' => null,
            'gross_total' => 0,
            'partner_commission_type' => SalesInvoice::DISCOUNT_FIXED,
            'partner_commission_value' => 0,
            'partner_commission_amount' => 0,
            'net_revenue_after_partner_commission' => 0,
            'total_cost' => 0,
            'total_profit' => 0,
            'status' => SalesInvoiceStatus::Draft->value,
            'created_by' => User::factory(),
        ];
    }
}
