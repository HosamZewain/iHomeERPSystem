<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockMovementTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_invoice_confirmation_records_stock_movement_balance_and_creator(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->create([
            'current_average_cost' => 100,
            'sale_price' => 180,
        ]);
        $invoice = PurchaseInvoice::factory()->create([
            'supplier_id' => $product->supplier_id,
            'created_by' => $user->id,
            'subtotal' => 150,
            'total' => 150,
        ]);

        $item = PurchaseInvoiceItem::factory()->create([
            'purchase_invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'quantity' => 3,
            'unit_cost' => 50,
            'line_total' => 150,
        ]);

        $invoice->confirm($user);

        $movement = StockMovement::query()->firstOrFail();

        $this->assertSame($product->id, $movement->product_id);
        $this->assertSame(StockMovement::TYPE_PURCHASE_IN, $movement->movement_type);
        $this->assertSame(StockMovement::SOURCE_PURCHASE_ITEM, $movement->reference_type);
        $this->assertSame($item->id, $movement->reference_id);
        $this->assertSame($user->id, $movement->created_by);
        $this->assertEquals(3.0, (float) $movement->quantity);
        $this->assertEquals(3.0, (float) $movement->balance_after);
        $this->assertEquals(3.0, Product::find($product->id)->current_stock_quantity);
        $this->assertEquals(50.0, (float) Product::find($product->id)->current_average_cost);
    }

    public function test_stock_summary_and_product_movement_pages_render_for_authorized_users(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->create(['name' => 'Smart Switch']);

        StockMovement::factory()->create([
            'product_id' => $product->id,
            'quantity' => 4,
            'balance_after' => 4,
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('stock.index'))
            ->assertOk()
            ->assertSee('ملخص المخزون')
            ->assertSee('Smart Switch');

        $this->actingAs($user)
            ->get(route('stock.movements.product', $product))
            ->assertOk()
            ->assertSee('حركات المنتج')
            ->assertSee('شراء');
    }
}
