<?php

namespace Tests\Feature;

use App\Enums\SalesChannel;
use App\Enums\SalesInvoiceStatus;
use App\Models\Customer;
use App\Models\Partner;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_dashboard_uses_confirmed_sales_for_business_metrics(): void
    {
        Carbon::setTestNow('2026-04-12 10:00:00');

        $user = User::factory()->create(['role' => 'admin']);
        $customer = Customer::factory()->create(['name' => 'Dashboard Customer']);
        $partner = Partner::factory()->create(['name' => 'Dashboard Partner']);
        $lowStockProduct = Product::factory()->create([
            'name' => 'Low Stock Switch',
            'internal_sku' => 'LOW-STOCK-001',
            'current_average_cost' => 100,
            'sale_price' => 200,
            'minimum_stock_alert_level' => 5,
        ]);
        $product = Product::factory()->create([
            'name' => 'Top Sensor',
            'internal_sku' => 'TOP-SENSOR-001',
            'current_average_cost' => 50,
            'sale_price' => 100,
            'minimum_stock_alert_level' => 1,
        ]);

        StockMovement::factory()->create([
            'product_id' => $lowStockProduct->id,
            'source_id' => 101,
            'quantity' => 2,
            'balance_after' => 2,
            'unit_cost' => 100,
            'total_cost' => 200,
        ]);
        StockMovement::factory()->create([
            'product_id' => $product->id,
            'source_id' => 102,
            'quantity' => 10,
            'balance_after' => 10,
            'unit_cost' => 50,
            'total_cost' => 500,
        ]);

        $directInvoice = SalesInvoice::factory()->create([
            'invoice_number' => 'INV-DASH-001',
            'customer_id' => $customer->id,
            'sales_channel' => SalesChannel::Direct->value,
            'invoice_date' => '2026-04-12',
            'gross_total' => 1000,
            'partner_commission_amount' => 0,
            'net_revenue_after_partner_commission' => 1000,
            'total_profit' => 300,
            'status' => SalesInvoiceStatus::Confirmed->value,
            'created_by' => $user->id,
        ]);
        SalesInvoiceItem::factory()->create([
            'sales_invoice_id' => $directInvoice->id,
            'product_id' => $lowStockProduct->id,
            'quantity' => 2,
            'unit_sale_price' => 500,
            'line_total' => 1000,
        ]);

        $partnerInvoice = SalesInvoice::factory()->create([
            'invoice_number' => 'INV-DASH-002',
            'customer_id' => $customer->id,
            'sales_channel' => SalesChannel::Partner->value,
            'partner_id' => $partner->id,
            'invoice_date' => '2026-04-12',
            'gross_total' => 2000,
            'partner_commission_amount' => 200,
            'net_revenue_after_partner_commission' => 1800,
            'total_profit' => 600,
            'status' => SalesInvoiceStatus::Confirmed->value,
            'created_by' => $user->id,
        ]);
        SalesInvoiceItem::factory()->create([
            'sales_invoice_id' => $partnerInvoice->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_sale_price' => 2000,
            'line_total' => 2000,
        ]);

        SalesInvoice::factory()->create([
            'invoice_number' => 'INV-DASH-DRAFT',
            'customer_id' => $customer->id,
            'invoice_date' => '2026-04-12',
            'gross_total' => 9999,
            'net_revenue_after_partner_commission' => 9999,
            'total_profit' => 9999,
            'status' => SalesInvoiceStatus::Draft->value,
            'created_by' => $user->id,
        ]);

        Quotation::factory()->create([
            'quotation_number' => 'QUO-DASH-TODAY',
            'customer_id' => $customer->id,
            'quotation_date' => '2026-04-12',
            'total' => 700,
            'created_by' => $user->id,
        ]);
        Quotation::factory()->create([
            'quotation_number' => 'QUO-DASH-MONTH',
            'customer_id' => $customer->id,
            'quotation_date' => '2026-04-05',
            'total' => 800,
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('مبيعات اليوم')
            ->assertSee('3,000.00 ج.م')
            ->assertSee('900.00 ج.م')
            ->assertSee('200.00 ج.م')
            ->assertSee('2,800.00 ج.م')
            ->assertSee('700.00 ج.م')
            ->assertSee('1,400.00 ج.م')
            ->assertSee('Low Stock Switch')
            ->assertSee('Top Sensor')
            ->assertSee('Dashboard Customer')
            ->assertSee('Dashboard Partner')
            ->assertSee('بيع مباشر')
            ->assertSee('بيع من خلال شريك');
    }
}
