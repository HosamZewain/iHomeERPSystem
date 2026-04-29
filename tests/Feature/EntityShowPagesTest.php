<?php

namespace Tests\Feature;

use App\Enums\InvoicePaymentStatus;
use App\Enums\PartnerType;
use App\Enums\PaymentMethod;
use App\Enums\PurchaseInvoiceStatus;
use App\Enums\SalesChannel;
use App\Enums\SalesInvoiceStatus;
use App\Models\Customer;
use App\Models\Partner;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\Quotation;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EntityShowPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_show_page_displays_financial_and_product_summary(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $customer = Customer::factory()->create([
            'name' => 'Ahmed Customer',
            'phone' => '01000000001',
        ]);
        $product = Product::factory()->create([
            'name' => 'Smart Switch Pro',
            'internal_sku' => 'SS-PRO',
        ]);

        Quotation::factory()->create(['customer_id' => $customer->id]);

        $invoice = SalesInvoice::factory()->create([
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-CUST-001',
            'gross_total' => 1000,
            'net_revenue_after_partner_commission' => 1000,
            'status' => SalesInvoiceStatus::Confirmed->value,
            'payment_status' => InvoicePaymentStatus::PartiallyPaid->value,
            'paid_amount' => 400,
            'remaining_amount' => 600,
            'created_by' => $user->id,
        ]);

        SalesInvoiceItem::factory()->create([
            'sales_invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_sale_price' => 500,
            'line_total' => 1000,
            'sort_order' => 1,
        ]);

        $this->actingAs($user)
            ->get(route('customers.show', $customer))
            ->assertOk()
            ->assertSee('Ahmed Customer')
            ->assertSee('INV-CUST-001')
            ->assertSee('Smart Switch Pro')
            ->assertSee('إجمالي ما دفعه العميل')
            ->assertSee('400.00 ج.م')
            ->assertSee('600.00 ج.م');
    }

    public function test_supplier_show_page_displays_purchase_and_product_summary(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $supplier = Supplier::factory()->create([
            'name' => 'Main Supplier',
            'phone' => '01000000002',
        ]);
        $product = Product::factory()->create([
            'name' => 'Door Sensor',
            'internal_sku' => 'DS-001',
            'supplier_id' => $supplier->id,
            'is_active' => true,
        ]);

        $purchaseInvoice = PurchaseInvoice::factory()->create([
            'supplier_id' => $supplier->id,
            'invoice_number' => 'PINV-SUP-001',
            'total' => 1500,
            'status' => PurchaseInvoiceStatus::Confirmed->value,
            'created_by' => $user->id,
        ]);

        PurchaseInvoiceItem::factory()->create([
            'purchase_invoice_id' => $purchaseInvoice->id,
            'product_id' => $product->id,
            'quantity' => 5,
            'unit_cost' => 300,
            'line_total' => 1500,
        ]);

        $this->actingAs($user)
            ->get(route('suppliers.show', $supplier))
            ->assertOk()
            ->assertSee('Main Supplier')
            ->assertSee('PINV-SUP-001')
            ->assertSee('Door Sensor')
            ->assertSee('إجمالي الشراء المؤكد')
            ->assertSee('1,500.00 ج.م');
    }

    public function test_partner_show_page_displays_commission_and_invoice_summary(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $customer = Customer::factory()->create(['name' => 'Partner Customer']);
        $partner = Partner::factory()->create([
            'name' => 'Elite Partner',
            'type' => PartnerType::Company->value,
            'phone' => '01000000003',
        ]);

        $invoice = SalesInvoice::factory()->create([
            'customer_id' => $customer->id,
            'partner_id' => $partner->id,
            'sales_channel' => SalesChannel::Partner->value,
            'invoice_number' => 'INV-PART-001',
            'gross_total' => 2000,
            'partner_commission_amount' => 200,
            'net_revenue_after_partner_commission' => 1800,
            'status' => SalesInvoiceStatus::Confirmed->value,
            'payment_status' => InvoicePaymentStatus::Unpaid->value,
            'paid_amount' => 0,
            'remaining_amount' => 2000,
            'created_by' => $user->id,
        ]);

        $invoice->recordPayment([
            'payment_date' => now()->toDateString(),
            'amount' => 2000,
            'payment_method' => PaymentMethod::Cash->value,
            'reference_number' => 'PARTNER-CASH-001',
            'notes' => 'تحصيل كامل',
        ], $user);

        $this->actingAs($user)
            ->get(route('partners.show', $partner))
            ->assertOk()
            ->assertSee('Elite Partner')
            ->assertSee('INV-PART-001')
            ->assertSee('Partner Customer')
            ->assertSee('إجمالي العمولة المكتسبة')
            ->assertSee('200.00 ج.م')
            ->assertSee('2,000.00 ج.م');
    }
}
