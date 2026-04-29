<?php

namespace App\Livewire\Expenses;

use App\Models\ExpenseCategory;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class ExpenseCategoryList extends Component
{
    use WithPagination;

    public string $search = '';
    public bool $showForm = false;
    public ?int $editingId = null;
    public string $name = '';
    public bool $is_active = true;
    public string $notes = '';

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('expense_categories', 'name')->ignore($this->editingId)],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $category = ExpenseCategory::findOrFail($id);

        $this->editingId = $category->id;
        $this->name = $category->name;
        $this->is_active = $category->is_active;
        $this->notes = $category->notes ?? '';
        $this->showForm = true;
        $this->resetValidation();
    }

    public function cancel(): void
    {
        $this->resetForm();
    }

    public function save(): void
    {
        $data = $this->validate();

        if ($this->editingId) {
            ExpenseCategory::findOrFail($this->editingId)->update($data);
            session()->flash('success', 'تم تحديث فئة المصروف.');
        } else {
            ExpenseCategory::create($data);
            session()->flash('success', 'تم إنشاء فئة المصروف.');
        }

        $this->resetForm();
    }

    public function toggleActive(int $id): void
    {
        $category = ExpenseCategory::findOrFail($id);
        $category->update(['is_active' => ! $category->is_active]);

        session()->flash('success', 'تم ' . ($category->is_active ? 'تفعيل ' : 'إيقاف ') . $category->name . '.');
    }

    public function delete(int $id): void
    {
        $category = ExpenseCategory::findOrFail($id);

        if (! $category->canDelete()) {
            session()->flash('error', 'لا يمكن حذف فئة مرتبطة بمصروفات مسجلة.');

            return;
        }

        $category->delete();
        session()->flash('success', 'تم حذف فئة المصروف.');
    }

    private function resetForm(): void
    {
        $this->reset(['showForm', 'editingId', 'name', 'notes']);
        $this->is_active = true;
        $this->resetValidation();
    }

    public function render()
    {
        $categories = ExpenseCategory::query()
            ->when($this->search, fn ($query) => $query->where('name', 'like', "%{$this->search}%"))
            ->withCount('expenses')
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.expenses.expense-category-list', [
            'categories' => $categories,
        ])->layout('layouts.app', ['header' => 'فئات المصروفات']);
    }
}
