<?php

namespace Tests\Feature;

use App\Enums\SalesChannel;
use App\Enums\SalesInvoiceStatus;
use App\Livewire\Reports\SalesReport;
use App\Models\Customer;
use App\Models\Partner;
use App\Models\Product;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class SalesReportTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_sales_report_uses_confirmed_invoices_for_monthly_metrics(): void
    {
        Carbon::setTestNow('2026-04-12 10:00:00');

        $user = User::factory()->create(['role' => 'admin']);
        $customer = Customer::factory()->create(['name' => 'Report Customer']);
        $partner = Partner::factory()->create(['name' => 'Report Partner']);
        $directProduct = Product::factory()->create(['name' => 'Report Switch', 'internal_sku' => 'REPORT-SWITCH']);
        $partnerProduct = Product::factory()->create(['name' => 'Report Sensor', 'internal_sku' => 'REPORT-SENSOR']);

        $directInvoice = SalesInvoice::factory()->create([
            'invoice_number' => 'INV-REPORT-001',
            'customer_id' => $customer->id,
            'sales_channel' => SalesChannel::Direct->value,
            'invoice_date' => '2026-04-12',
            'gross_total' => 1000,
            'partner_commission_amount' => 0,
            'net_revenue_after_partner_commission' => 1000,
            'total_profit' => 250,
            'status' => SalesInvoiceStatus::Confirmed->value,
            'created_by' => $user->id,
        ]);

        SalesInvoiceItem::factory()->create([
            'sales_invoice_id' => $directInvoice->id,
            'product_id' => $directProduct->id,
            'quantity' => 2,
            'unit_sale_price' => 500,
            'line_total' => 1000,
            'line_profit' => 250,
        ]);

        $partnerInvoice = SalesInvoice::factory()->create([
            'invoice_number' => 'INV-REPORT-002',
            'customer_id' => $customer->id,
            'sales_channel' => SalesChannel::Partner->value,
            'partner_id' => $partner->id,
            'invoice_date' => '2026-04-12',
            'gross_total' => 2000,
            'partner_commission_amount' => 200,
            'net_revenue_after_partner_commission' => 1800,
            'total_profit' => 500,
            'status' => SalesInvoiceStatus::Confirmed->value,
            'created_by' => $user->id,
        ]);

        SalesInvoiceItem::factory()->create([
            'sales_invoice_id' => $partnerInvoice->id,
            'product_id' => $partnerProduct->id,
            'quantity' => 1,
            'unit_sale_price' => 2000,
            'line_total' => 2000,
            'line_profit' => 500,
        ]);

        SalesInvoice::factory()->create([
            'invoice_number' => 'INV-REPORT-DRAFT',
            'customer_id' => $customer->id,
            'invoice_date' => '2026-04-12',
            'gross_total' => 9999,
            'partner_commission_amount' => 999,
            'net_revenue_after_partner_commission' => 9000,
            'total_profit' => 9999,
            'status' => SalesInvoiceStatus::Draft->value,
            'created_by' => $user->id,
        ]);

        SalesInvoice::factory()->create([
            'invoice_number' => 'INV-REPORT-MARCH',
            'customer_id' => $customer->id,
            'invoice_date' => '2026-03-30',
            'gross_total' => 500,
            'net_revenue_after_partner_commission' => 500,
            'total_profit' => 100,
            'status' => SalesInvoiceStatus::Confirmed->value,
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('reports.sales'))
            ->assertOk()
            ->assertSee('تقرير المبيعات')
            ->assertSee('3,000.00 ج.م')
            ->assertSee('2,800.00 ج.م')
            ->assertSee('750.00 ج.م')
            ->assertSee('200.00 ج.م')
            ->assertSee('1,500.00 ج.م')
            ->assertSee('Report Switch')
            ->assertSee('Report Sensor')
            ->assertSee('Report Customer')
            ->assertSee('Report Partner')
            ->assertSee('بيع مباشر')
            ->assertSee('بيع من خلال شريك')
            ->assertDontSee('9,999.00 ج.م');
    }

    public function test_sales_report_date_range_filter_limits_results(): void
    {
        Carbon::setTestNow('2026-04-12 10:00:00');

        $user = User::factory()->create(['role' => 'admin']);
        $customer = Customer::factory()->create(['name' => 'Range Customer']);

        SalesInvoice::factory()->create([
            'invoice_number' => 'INV-RANGE-IN',
            'customer_id' => $customer->id,
            'invoice_date' => '2026-04-10',
            'gross_total' => 400,
            'net_revenue_after_partner_commission' => 400,
            'total_profit' => 120,
            'status' => SalesInvoiceStatus::Confirmed->value,
            'created_by' => $user->id,
        ]);

        SalesInvoice::factory()->create([
            'invoice_number' => 'INV-RANGE-OUT',
            'customer_id' => $customer->id,
            'invoice_date' => '2026-04-12',
            'gross_total' => 800,
            'net_revenue_after_partner_commission' => 800,
            'total_profit' => 300,
            'status' => SalesInvoiceStatus::Confirmed->value,
            'created_by' => $user->id,
        ]);

        Livewire::actingAs($user)
            ->test(SalesReport::class)
            ->set('reportMode', 'range')
            ->set('startDate', '2026-04-10')
            ->set('endDate', '2026-04-10')
            ->assertSee('400.00 ج.م')
            ->assertSee('120.00 ج.م')
            ->assertDontSee('800.00 ج.م');
    }
}
