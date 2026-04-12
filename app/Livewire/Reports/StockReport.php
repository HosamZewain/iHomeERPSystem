<?php

namespace App\Livewire\Reports;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class StockReport extends Component
{
    use WithPagination;

    public string $search = '';

    public string $categoryFilter = '';

    public string $stockFilter = '';

    public string $activeFilter = '';

    public string $sortField = 'name';

    public string $sortDirection = 'asc';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingCategoryFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStockFilter(): void
    {
        $this->resetPage();
    }

    public function updatingActiveFilter(): void
    {
        $this->resetPage();
    }

    public function updatingSortField(): void
    {
        $this->resetPage();
    }

    public function updatingSortDirection(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if (! array_key_exists($field, $this->sortableFields())) {
            return;
        }

        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'categoryFilter', 'stockFilter', 'activeFilter']);
        $this->resetPage();
    }

    public function render()
    {
        $productsQuery = $this->filteredProductsQuery()
            ->with(['category', 'supplier'])
            ->withStockQuantity();

        $this->applySorting($productsQuery);

        $lowStockProducts = $this->filteredProductsQuery()
            ->with(['category'])
            ->withStockQuantity()
            ->where('minimum_stock_alert_level', '>', 0)
            ->whereRaw($this->stockQuantitySql().' <= products.minimum_stock_alert_level')
            ->orderByRaw($this->stockQuantitySql().' asc')
            ->orderBy('name')
            ->limit(8)
            ->get();

        return view('livewire.reports.stock-report', [
            'products' => $productsQuery->paginate(15),
            'categories' => Category::query()->orderBy('name')->get(),
            'summary' => $this->summary(),
            'lowStockProducts' => $lowStockProducts,
            'sortableFields' => $this->sortableFields(),
        ])->layout('layouts.app', ['header' => 'تقرير المخزون']);
    }

    private function filteredProductsQuery(): Builder
    {
        $stockSql = $this->stockQuantitySql();

        return Product::query()
            ->when($this->search, function (Builder $query) {
                $query->where(function (Builder $query) {
                    $query->where('name', 'like', "%{$this->search}%")
                        ->orWhere('internal_sku', 'like', "%{$this->search}%")
                        ->orWhere('barcode', 'like', "%{$this->search}%");
                });
            })
            ->when($this->categoryFilter, fn (Builder $query) => $query->where('category_id', $this->categoryFilter))
            ->when($this->activeFilter === 'active', fn (Builder $query) => $query->where('is_active', true))
            ->when($this->activeFilter === 'inactive', fn (Builder $query) => $query->where('is_active', false))
            ->when($this->stockFilter === 'in_stock', fn (Builder $query) => $query->whereRaw($stockSql.' > 0'))
            ->when($this->stockFilter === 'zero', fn (Builder $query) => $query->whereRaw($stockSql.' = 0'))
            ->when($this->stockFilter === 'low', fn (Builder $query) => $query
                ->where('minimum_stock_alert_level', '>', 0)
                ->whereRaw($stockSql.' <= products.minimum_stock_alert_level'))
            ->when($this->stockFilter === 'negative', fn (Builder $query) => $query->whereRaw($stockSql.' < 0'));
    }

    private function summary(): array
    {
        $stockSql = $this->stockQuantitySql();

        $summary = $this->filteredProductsQuery()
            ->selectRaw('COUNT(*) as products_count')
            ->selectRaw('COALESCE(SUM('.$stockSql.'), 0) as total_quantity')
            ->selectRaw('COALESCE(SUM(('.$stockSql.') * products.current_average_cost), 0) as value_at_average_cost')
            ->selectRaw('COALESCE(SUM(('.$stockSql.') * products.sale_price), 0) as value_at_sale_price')
            ->selectRaw('SUM(CASE WHEN products.minimum_stock_alert_level > 0 AND '.$stockSql.' <= products.minimum_stock_alert_level THEN 1 ELSE 0 END) as low_stock_count')
            ->selectRaw('SUM(CASE WHEN '.$stockSql.' = 0 THEN 1 ELSE 0 END) as zero_stock_count')
            ->selectRaw('SUM(CASE WHEN '.$stockSql.' < 0 THEN 1 ELSE 0 END) as negative_stock_count')
            ->first();

        return [
            'products_count' => (int) $summary->products_count,
            'total_quantity' => (float) $summary->total_quantity,
            'value_at_average_cost' => (float) $summary->value_at_average_cost,
            'value_at_sale_price' => (float) $summary->value_at_sale_price,
            'low_stock_count' => (int) $summary->low_stock_count,
            'zero_stock_count' => (int) $summary->zero_stock_count,
            'negative_stock_count' => (int) $summary->negative_stock_count,
        ];
    }

    private function stockQuantitySql(): string
    {
        return Product::stockQuantitySubquerySql();
    }

    private function applySorting(Builder $query): void
    {
        $direction = $this->sortDirection === 'desc' ? 'desc' : 'asc';
        $stockSql = $this->stockQuantitySql();

        match ($this->sortField) {
            'category' => $query->orderBy(Category::query()->select('name')->whereColumn('categories.id', 'products.category_id'), $direction),
            'status' => $query->orderBy('is_active', $direction),
            'stock' => $query->orderByRaw($stockSql.' '.$direction),
            'minimum_stock' => $query->orderBy('minimum_stock_alert_level', $direction),
            'average_cost' => $query->orderBy('current_average_cost', $direction),
            'sale_price' => $query->orderBy('sale_price', $direction),
            'value_cost' => $query->orderByRaw('('.$stockSql.') * products.current_average_cost '.$direction),
            'value_sale' => $query->orderByRaw('('.$stockSql.') * products.sale_price '.$direction),
            default => $query->orderBy('name', $direction),
        };

        if ($this->sortField !== 'name') {
            $query->orderBy('name');
        }
    }

    private function sortableFields(): array
    {
        return [
            'name' => 'المنتج',
            'category' => 'التصنيف',
            'status' => 'الحالة',
            'stock' => 'المخزون الحالي',
            'minimum_stock' => 'حد التنبيه',
            'average_cost' => 'متوسط التكلفة',
            'sale_price' => 'سعر البيع',
            'value_cost' => 'القيمة بالتكلفة',
            'value_sale' => 'القيمة بسعر البيع',
        ];
    }
}
