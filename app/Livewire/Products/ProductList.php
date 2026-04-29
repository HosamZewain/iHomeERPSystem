<?php

namespace App\Livewire\Products;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class ProductList extends Component
{
    use WithFileUploads;
    use WithPagination;

    public string $search = '';

    public string $categoryFilter = '';

    public string $supplierFilter = '';

    public string $statusFilter = '';

    public string $sortField = 'name';

    public string $sortDirection = 'asc';

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $internal_sku = '';

    public ?string $barcode = '';

    public ?string $image_path = '';

    public string $category_id = '';

    public ?string $supplier_id = '';

    public string $sale_price = '0';

    public string $current_average_cost = '0';

    public string $minimum_stock_alert_level = '0';

    public bool $is_active = true;

    public string $notes = '';

    public $imageUpload = null;

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'internal_sku' => ['required', 'string', 'max:255', Rule::unique('products', 'internal_sku')->ignore($this->editingId)],
            'barcode' => ['nullable', 'string', 'max:255', Rule::unique('products', 'barcode')->ignore($this->editingId)],
            'image_path' => ['nullable', 'string', 'max:500'],
            'imageUpload' => ['nullable', 'image', 'max:4096'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'sale_price' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'current_average_cost' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'minimum_stock_alert_level' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingCategoryFilter(): void
    {
        $this->resetPage();
    }

    public function updatingSupplierFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
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

    public function create(): void
    {
        $this->resetForm();
        $this->category_id = (string) Category::query()->orderBy('name')->value('id');
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $product = Product::findOrFail($id);

        $this->editingId = $product->id;
        $this->name = $product->name;
        $this->internal_sku = $product->internal_sku;
        $this->barcode = $product->barcode ?? '';
        $this->image_path = $product->image_path ?? '';
        $this->category_id = (string) $product->category_id;
        $this->supplier_id = $product->supplier_id ? (string) $product->supplier_id : '';
        $this->sale_price = (string) $product->sale_price;
        $this->current_average_cost = (string) $product->current_average_cost;
        $this->minimum_stock_alert_level = (string) $product->minimum_stock_alert_level;
        $this->is_active = $product->is_active;
        $this->notes = $product->notes ?? '';
        $this->showForm = true;
        $this->resetValidation();
    }

    public function cancel(): void
    {
        $this->resetForm();
    }

    public function save(): void
    {
        $this->supplier_id = $this->supplier_id === '' ? null : $this->supplier_id;
        $this->barcode = $this->barcode === '' ? null : $this->barcode;
        $this->image_path = $this->image_path === '' ? null : $this->image_path;

        $data = $this->validate();
        unset($data['imageUpload']);

        if ($this->imageUpload) {
            $storedPath = $this->imageUpload->store('products', 'public');
            $data['image_path'] = Storage::disk('public')->url($storedPath);
        }

        if ($this->editingId) {
            Product::findOrFail($this->editingId)->update($data);
            session()->flash('success', 'تم تحديث المنتج.');
        } else {
            Product::create($data);
            session()->flash('success', 'تم إنشاء المنتج.');
        }

        $this->resetForm();
    }

    public function toggleActive(int $id): void
    {
        $product = Product::findOrFail($id);
        $product->update(['is_active' => ! $product->is_active]);

        session()->flash('success', 'تم '.($product->is_active ? 'تفعيل ' : 'إيقاف ').$product->name.'.');
    }

    public function delete(int $id): void
    {
        $product = Product::findOrFail($id);

        if (! $product->canDelete()) {
            session()->flash('error', 'لا يمكن حذف "'.$product->name.'" لأنه مرتبط بحركات مخزون أو بنود فواتير. يمكن إيقافه بدلًا من الحذف.');

            return;
        }

        $product->delete();
        session()->flash('success', 'تم حذف المنتج.');
    }

    private function resetForm(): void
    {
        $this->reset([
            'showForm',
            'editingId',
            'name',
            'internal_sku',
            'barcode',
            'image_path',
            'category_id',
            'supplier_id',
            'notes',
            'imageUpload',
        ]);
        $this->sale_price = '0';
        $this->current_average_cost = '0';
        $this->minimum_stock_alert_level = '0';
        $this->is_active = true;
        $this->resetValidation();
    }

    public function render()
    {
        $products = Product::query()
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
            ->when($this->supplierFilter, fn ($query) => $query->where('supplier_id', $this->supplierFilter))
            ->when($this->statusFilter !== '', fn ($query) => $query->where('is_active', $this->statusFilter === 'active'));

        $this->applySorting($products);

        $products = $products->paginate(15);

        return view('livewire.products.product-list', [
            'products' => $products,
            'categories' => Category::query()->orderBy('name')->get(),
            'suppliers' => Supplier::query()->orderBy('name')->get(),
            'sortableFields' => $this->sortableFields(),
        ])->layout('layouts.app', ['header' => 'المنتجات']);
    }

    private function applySorting($query): void
    {
        $direction = $this->sortDirection === 'desc' ? 'desc' : 'asc';

        match ($this->sortField) {
            'sku' => $query->orderBy('internal_sku', $direction),
            'category' => $query->orderBy(Category::query()->select('name')->whereColumn('categories.id', 'products.category_id'), $direction),
            'supplier' => $query->orderBy(Supplier::query()->select('name')->whereColumn('suppliers.id', 'products.supplier_id'), $direction),
            'sale_price' => $query->orderBy('sale_price', $direction),
            'average_cost' => $query->orderBy('current_average_cost', $direction),
            'stock' => $query->orderByRaw(Product::stockQuantitySubquerySql().' '.$direction),
            'status' => $query->orderBy('is_active', $direction),
            'created_at' => $query->orderBy('created_at', $direction),
            'updated_at' => $query->orderBy('updated_at', $direction),
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
            'sku' => 'SKU',
            'category' => 'التصنيف',
            'supplier' => 'المورد',
            'sale_price' => 'السعر',
            'average_cost' => 'متوسط التكلفة',
            'stock' => 'المخزون',
            'status' => 'الحالة',
            'created_at' => 'تاريخ الإنشاء',
            'updated_at' => 'آخر تحديث',
        ];
    }
}
