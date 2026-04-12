<?php

namespace App\Livewire\Categories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class CategoryList extends Component
{
    use WithPagination;

    public string $search = '';
    public string $sort = 'name';
    public string $name = '';
    public ?int $editingId = null;
    public string $editingName = '';

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('categories', 'name')],
        ];
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingSort(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->validate();

        Category::create(['name' => $this->name]);

        $this->reset('name');
        session()->flash('success', 'تم إنشاء التصنيف.');
    }

    public function startEdit(int $id): void
    {
        $category = Category::findOrFail($id);
        $this->editingId = $id;
        $this->editingName = $category->name;
    }

    public function cancelEdit(): void
    {
        $this->reset('editingId', 'editingName');
        $this->resetValidation();
    }

    public function update(): void
    {
        $this->validate([
            'editingName' => 'required|string|max:255|unique:categories,name,' . $this->editingId,
        ]);

        $category = Category::findOrFail($this->editingId);
        $category->update(['name' => $this->editingName]);

        $this->reset('editingId', 'editingName');
        session()->flash('success', 'تم تحديث التصنيف.');
    }

    public function delete(int $id): void
    {
        $category = Category::findOrFail($id);

        if (! $category->canDelete()) {
            session()->flash('error', 'لا يمكن حذف "' . $category->name . '" لأنه مرتبط بمنتجات.');
            return;
        }

        $category->delete();
        session()->flash('success', 'تم حذف التصنيف.');
    }

    public function render()
    {
        $query = Category::query()
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"));

        if (class_exists(Product::class)) {
            $query->withCount('products');
        }

        $categories = $query
            ->when($this->sort === 'latest', fn ($q) => $q->latest(), fn ($q) => $q->orderBy('name'))
            ->paginate(20);

        return view('livewire.categories.category-list', [
            'categories' => $categories,
        ])->layout('layouts.app', ['header' => 'التصنيفات']);
    }
}
