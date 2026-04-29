<?php

namespace App\Livewire\Stock;

use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;

class StockSummary extends Component
{
    use WithPagination;

    public string $search = '';

    public string $categoryFilter = '';

    public string $stockFilter = '';

    public string $sortField = 'name';

    public string $sortDirection = 'asc';

    public bool $showAdjustmentForm = false;

    public ?int $adjustmentProductId = null;

    public string $adjustmentTargetQuantity = '';

    public string $adjustmentNotes = '';

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

    public function startAdjustment(int $productId): void
    {
        abort_unless(auth()->user()->hasPermission('products.manage'), 403);

        $product = Product::query()->findOrFail($productId);

        $this->showAdjustmentForm = true;
        $this->adjustmentProductId = $product->id;
        $this->adjustmentTargetQuantity = number_format($product->current_stock_quantity, 2, '.', '');
        $this->adjustmentNotes = '';
        $this->resetValidation(['adjustmentTargetQuantity', 'adjustmentNotes']);
    }

    public function cancelAdjustment(): void
    {
        $this->showAdjustmentForm = false;
        $this->adjustmentProductId = null;
        $this->adjustmentTargetQuantity = '';
        $this->adjustmentNotes = '';
        $this->resetValidation(['adjustmentTargetQuantity', 'adjustmentNotes']);
    }

    public function saveAdjustment(): void
    {
        abort_unless(auth()->user()->hasPermission('products.manage'), 403);

        $data = $this->validate([
            'adjustmentProductId' => ['required', 'integer', 'exists:products,id'],
            'adjustmentTargetQuantity' => ['required', 'numeric', 'min:-999999999.99', 'max:999999999.99'],
            'adjustmentNotes' => ['required', 'string', 'max:2000'],
        ], [], [
            'adjustmentProductId' => 'المنتج',
            'adjustmentTargetQuantity' => 'الرصيد المستهدف',
            'adjustmentNotes' => 'سبب التسوية',
        ]);

        try {
            DB::transaction(function () use ($data) {
                $product = Product::query()
                    ->whereKey($data['adjustmentProductId'])
                    ->lockForUpdate()
                    ->firstOrFail();

                StockMovement::query()
                    ->where('product_id', $product->id)
                    ->lockForUpdate()
                    ->get();

                $currentQuantity = round((float) $product->current_stock_quantity, 2);
                $targetQuantity = round((float) $data['adjustmentTargetQuantity'], 2);
                $delta = round($targetQuantity - $currentQuantity, 2);

                if (abs($delta) < 0.01) {
                    throw ValidationException::withMessages([
                        'adjustmentTargetQuantity' => 'الرصيد المستهدف يساوي الرصيد الحالي، ولا توجد تسوية مطلوبة.',
                    ]);
                }

                $movementType = $delta > 0
                    ? StockMovement::TYPE_ADJUSTMENT_IN
                    : StockMovement::TYPE_ADJUSTMENT_OUT;

                $unitCost = round((float) $product->current_average_cost, 2);

                StockMovement::create([
                    'product_id' => $product->id,
                    'movement_type' => $movementType,
                    'source_type' => StockMovement::SOURCE_ADJUSTMENT,
                    'source_id' => $product->id,
                    'created_by' => auth()->id(),
                    'quantity' => $delta,
                    'balance_after' => $targetQuantity,
                    'unit_cost' => $unitCost,
                    'total_cost' => round(abs($delta) * max($unitCost, 0), 2),
                    'movement_date' => now()->toDateString(),
                    'notes' => 'تسوية مخزون من شاشة المخزون: ' . $data['adjustmentNotes'],
                ]);
            });

            session()->flash('success', 'تم تسجيل تسوية المخزون وتحديث الرصيد الحالي.');
            $this->cancelAdjustment();
            $this->resetPage();
        } catch (ValidationException $exception) {
            throw $exception;
        }
    }

    public function render()
    {
        $stockSql = $this->stockQuantitySql();

        $productsQuery = Product::query()
            ->with(['category', 'supplier'])
            ->withStockQuantity()
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('name', 'like', "%{$this->search}%")
                        ->orWhere('internal_sku', 'like', "%{$this->search}%")
                        ->orWhere('barcode', 'like', "%{$this->search}%");
                });
            })
            ->when($this->categoryFilter, fn ($query) => $query->where('category_id', $this->categoryFilter))
            ->when($this->stockFilter === 'in_stock', fn ($query) => $query->whereRaw($stockSql.' > 0'))
            ->when($this->stockFilter === 'zero', fn ($query) => $query->whereRaw($stockSql.' = 0'))
            ->when($this->stockFilter === 'low', fn ($query) => $query
                ->where('minimum_stock_alert_level', '>', 0)
                ->whereRaw($stockSql.' <= products.minimum_stock_alert_level'));

        $this->applySorting($productsQuery);

        $allProducts = Product::query()->withStockQuantity()->get();

        $summary = [
            'products_count' => $allProducts->count(),
            'total_quantity' => $allProducts->sum(fn (Product $product) => $product->current_stock_quantity),
            'stock_value_at_cost' => $allProducts->sum(fn (Product $product) => $product->stock_value_at_average_cost),
            'stock_value_at_sale' => $allProducts->sum(fn (Product $product) => $product->stock_value_at_sale_price),
            'low_stock_count' => $allProducts->filter(fn (Product $product) => $product->isLowStock())->count(),
        ];

        return view('livewire.stock.stock-summary', [
            'products' => $productsQuery->paginate(15),
            'categories' => Category::query()->orderBy('name')->get(),
            'summary' => $summary,
            'sortableFields' => $this->sortableFields(),
            'adjustmentProduct' => $this->adjustmentProductId ? Product::query()->find($this->adjustmentProductId) : null,
            'canAdjustStock' => auth()->user()->hasPermission('products.manage'),
        ])->layout('layouts.app', ['header' => 'ملخص المخزون']);
    }

    private function stockQuantitySql(): string
    {
        return Product::stockQuantitySubquerySql();
    }

    private function applySorting($query): void
    {
        $direction = $this->sortDirection === 'desc' ? 'desc' : 'asc';
        $stockSql = $this->stockQuantitySql();

        match ($this->sortField) {
            'category' => $query->orderBy(Category::query()->select('name')->whereColumn('categories.id', 'products.category_id'), $direction),
            'stock' => $query->orderByRaw($stockSql.' '.$direction),
            'minimum_stock' => $query->orderBy('minimum_stock_alert_level', $direction),
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
            'stock' => 'المخزون الحالي',
            'minimum_stock' => 'حد التنبيه',
            'value_cost' => 'القيمة بالتكلفة',
            'value_sale' => 'القيمة بسعر البيع',
        ];
    }
}
