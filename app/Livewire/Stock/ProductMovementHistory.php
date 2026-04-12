<?php

namespace App\Livewire\Stock;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class ProductMovementHistory extends Component
{
    use WithPagination;

    public Product $product;
    public string $movementTypeFilter = '';
    public string $sortField = 'movement_date';
    public string $sortDirection = 'desc';

    public function mount(Product $product): void
    {
        $this->product = $product->load(['category', 'supplier']);
    }

    public function updatingMovementTypeFilter(): void
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

    public function render()
    {
        $movements = StockMovement::query()
            ->with('creator')
            ->where('product_id', $this->product->id)
            ->when($this->movementTypeFilter, fn ($query) => $query->where('movement_type', $this->movementTypeFilter));

        $this->applySorting($movements);

        $movements = $movements->paginate(20);

        return view('livewire.stock.product-movement-history', [
            'movements' => $movements,
            'sortableFields' => $this->sortableFields(),
            'movementTypes' => collect([
                StockMovement::TYPE_PURCHASE_IN,
                StockMovement::TYPE_SALE_OUT,
                StockMovement::TYPE_ADJUSTMENT_IN,
                StockMovement::TYPE_ADJUSTMENT_OUT,
                StockMovement::TYPE_RETURN_IN,
                StockMovement::TYPE_RETURN_OUT,
            ])->mapWithKeys(fn (string $type) => [$type => StockMovement::labelForMovementType($type)]),
        ])->layout('layouts.app', ['header' => 'حركات المنتج: ' . $this->product->name]);
    }

    private function applySorting($query): void
    {
        $direction = $this->sortDirection === 'desc' ? 'desc' : 'asc';

        match ($this->sortField) {
            'movement_type' => $query->orderBy('movement_type', $direction),
            'quantity_in' => $query->orderByRaw('CASE WHEN quantity > 0 THEN quantity ELSE 0 END '.$direction),
            'quantity_out' => $query->orderByRaw('CASE WHEN quantity < 0 THEN ABS(quantity) ELSE 0 END '.$direction),
            'balance_after' => $query->orderBy('balance_after', $direction),
            'reference' => $query->orderBy('source_type', $direction)->orderBy('source_id', $direction),
            'creator' => $query->orderBy(User::query()->select('name')->whereColumn('users.id', 'stock_movements.created_by'), $direction),
            default => $query->orderBy('movement_date', $direction),
        };

        $query->orderBy('id', $direction);
    }

    private function sortableFields(): array
    {
        return [
            'movement_date' => 'التاريخ',
            'movement_type' => 'نوع الحركة',
            'quantity_in' => 'وارد',
            'quantity_out' => 'صادر',
            'balance_after' => 'الرصيد بعد الحركة',
            'reference' => 'المرجع',
            'creator' => 'أنشأها',
        ];
    }
}
