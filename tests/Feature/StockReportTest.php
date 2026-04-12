<?php

namespace Tests\Feature;

use App\Livewire\Reports\StockReport;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StockReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_report_shows_movement_based_stock_and_valuations(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $category = Category::factory()->create(['name' => 'Report Category']);
        $lowProduct = Product::factory()->create([
            'name' => 'Report Low Switch',
            'internal_sku' => 'REPORT-LOW-SWITCH',
            'category_id' => $category->id,
            'current_average_cost' => 100,
            'sale_price' => 200,
            'minimum_stock_alert_level' => 5,
            'is_active' => true,
        ]);
        $zeroProduct = Product::factory()->create([
            'name' => 'Report Zero Sensor',
            'internal_sku' => 'REPORT-ZERO-SENSOR',
            'category_id' => $category->id,
            'current_average_cost' => 50,
            'sale_price' => 80,
            'minimum_stock_alert_level' => 0,
            'is_active' => true,
        ]);
        $normalProduct = Product::factory()->create([
            'name' => 'Report Normal Hub',
            'internal_sku' => 'REPORT-NORMAL-HUB',
            'category_id' => $category->id,
            'current_average_cost' => 20,
            'sale_price' => 40,
            'minimum_stock_alert_level' => 5,
            'is_active' => false,
        ]);

        StockMovement::factory()->create([
            'product_id' => $lowProduct->id,
            'source_id' => 901,
            'quantity' => 3,
            'balance_after' => 3,
            'unit_cost' => 100,
            'total_cost' => 300,
        ]);
        StockMovement::factory()->create([
            'product_id' => $normalProduct->id,
            'source_id' => 902,
            'quantity' => 10,
            'balance_after' => 10,
            'unit_cost' => 20,
            'total_cost' => 200,
        ]);

        $this->actingAs($user)
            ->get(route('reports.stock'))
            ->assertOk()
            ->assertSee('تقرير المخزون')
            ->assertSee('13.00')
            ->assertSee('500.00 ج.م')
            ->assertSee('1,000.00 ج.م')
            ->assertSee('Report Low Switch')
            ->assertSee('Report Zero Sensor')
            ->assertSee('Report Normal Hub')
            ->assertSee('حركات المخزون')
            ->assertSee(route('stock.movements.product', $lowProduct), false);
    }

    public function test_stock_report_filters_products_and_recalculates_visible_totals(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $category = Category::factory()->create();
        $matchingProduct = Product::factory()->create([
            'name' => 'Searchable Switch',
            'internal_sku' => 'SEARCH-SWITCH',
            'category_id' => $category->id,
            'current_average_cost' => 100,
            'sale_price' => 200,
            'minimum_stock_alert_level' => 5,
        ]);
        $hiddenProduct = Product::factory()->create([
            'name' => 'Hidden Sensor',
            'internal_sku' => 'HIDDEN-SENSOR',
            'category_id' => $category->id,
            'current_average_cost' => 50,
            'sale_price' => 80,
            'minimum_stock_alert_level' => 0,
        ]);

        StockMovement::factory()->create([
            'product_id' => $matchingProduct->id,
            'source_id' => 911,
            'quantity' => 3,
            'balance_after' => 3,
            'unit_cost' => 100,
            'total_cost' => 300,
        ]);
        StockMovement::factory()->create([
            'product_id' => $hiddenProduct->id,
            'source_id' => 912,
            'quantity' => 6,
            'balance_after' => 6,
            'unit_cost' => 50,
            'total_cost' => 300,
        ]);

        Livewire::actingAs($user)
            ->test(StockReport::class)
            ->set('search', 'Searchable')
            ->assertSee('Searchable Switch')
            ->assertDontSee('Hidden Sensor')
            ->assertSee('300.00 ج.م')
            ->assertSee('600.00 ج.م')
            ->set('stockFilter', 'low')
            ->assertSee('Searchable Switch')
            ->assertSee('منخفض');
    }
}
