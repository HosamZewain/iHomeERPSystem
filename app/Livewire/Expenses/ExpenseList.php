<?php

namespace App\Livewire\Expenses;

use App\Enums\ExpensePaymentStatus;
use App\Enums\ExpenseRecurringFrequency;
use App\Enums\ExpenseType;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use Livewire\Component;
use Livewire\WithPagination;

class ExpenseList extends Component
{
    use WithPagination;

    public string $search = '';
    public string $categoryFilter = '';
    public string $paymentStatusFilter = '';
    public string $startDate = '';
    public string $endDate = '';
    public string $sortField = 'expense_date';
    public string $sortDirection = 'desc';
    public bool $showForm = false;
    public ?int $editingId = null;
    public string $expense_category_id = '';
    public string $title = '';
    public string $amount = '0';
    public string $expense_date = '';
    public string $expense_type = 'one_time';
    public string $recurring_frequency = 'monthly';
    public string $paid_amount = '0';
    public string $vendor_name = '';
    public string $notes = '';

    protected function rules(): array
    {
        return [
            'expense_category_id' => ['required', 'exists:expense_categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'expense_date' => ['required', 'date'],
            'expense_type' => ['required', 'in:' . implode(',', array_column(ExpenseType::cases(), 'value'))],
            'recurring_frequency' => ['nullable', 'in:' . implode(',', array_column(ExpenseRecurringFrequency::cases(), 'value'))],
            'paid_amount' => ['required', 'numeric', 'min:0'],
            'vendor_name' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function mount(): void
    {
        $this->expense_date = now()->toDateString();
        $this->startDate = now()->startOfMonth()->toDateString();
        $this->endDate = now()->toDateString();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingCategoryFilter(): void
    {
        $this->resetPage();
    }

    public function updatingPaymentStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStartDate(): void
    {
        $this->resetPage();
    }

    public function updatingEndDate(): void
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

    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $expense = Expense::findOrFail($id);

        $this->editingId = $expense->id;
        $this->expense_category_id = (string) $expense->expense_category_id;
        $this->title = $expense->title;
        $this->amount = (string) $expense->amount;
        $this->expense_date = $expense->expense_date->toDateString();
        $this->expense_type = $expense->expense_type->value;
        $this->recurring_frequency = $expense->recurring_frequency?->value ?: 'monthly';
        $this->paid_amount = (string) $expense->paid_amount;
        $this->vendor_name = $expense->vendor_name ?? '';
        $this->notes = $expense->notes ?? '';
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

        if ((float) $data['paid_amount'] > (float) $data['amount']) {
            $this->addError('paid_amount', 'المبلغ المدفوع لا يمكن أن يتجاوز قيمة المصروف.');

            return;
        }

        $payload = [
            'expense_category_id' => (int) $data['expense_category_id'],
            'title' => $data['title'],
            'amount' => round((float) $data['amount'], 2),
            'expense_date' => $data['expense_date'],
            'expense_type' => $data['expense_type'],
            'recurring_frequency' => $data['expense_type'] === ExpenseType::Recurring->value ? $data['recurring_frequency'] : null,
            'paid_amount' => round((float) $data['paid_amount'], 2),
            'vendor_name' => $data['vendor_name'] ?: null,
            'notes' => $data['notes'] ?: null,
        ];

        if ($this->editingId) {
            Expense::findOrFail($this->editingId)->update($payload);
            session()->flash('success', 'تم تحديث المصروف.');
        } else {
            $payload['created_by'] = auth()->id();
            Expense::create($payload);
            session()->flash('success', 'تم إنشاء المصروف.');
        }

        $this->resetForm();
    }

    public function delete(int $id): void
    {
        Expense::findOrFail($id)->delete();
        session()->flash('success', 'تم حذف المصروف.');
    }

    public function generateNextOccurrence(int $id): void
    {
        $expense = Expense::findOrFail($id);
        $expense->generateNextOccurrence(auth()->user());

        session()->flash('success', 'تم توليد الفترة التالية للمصروف المتكرر.');
    }

    private function resetForm(): void
    {
        $this->reset([
            'showForm',
            'editingId',
            'expense_category_id',
            'title',
            'amount',
            'expense_type',
            'vendor_name',
            'notes',
        ]);

        $this->expense_date = now()->toDateString();
        $this->recurring_frequency = ExpenseRecurringFrequency::Monthly->value;
        $this->paid_amount = '0';
        $this->amount = '0';
        $this->resetValidation();
    }

    public function render()
    {
        $expenses = Expense::query()
            ->with(['category', 'creator', 'generatedFrom'])
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('title', 'like', "%{$this->search}%")
                        ->orWhere('vendor_name', 'like', "%{$this->search}%")
                        ->orWhere('notes', 'like', "%{$this->search}%");
                });
            })
            ->when($this->categoryFilter, fn ($query) => $query->where('expense_category_id', $this->categoryFilter))
            ->when($this->paymentStatusFilter, fn ($query) => $query->where('payment_status', $this->paymentStatusFilter))
            ->when($this->startDate, fn ($query) => $query->whereDate('expense_date', '>=', $this->startDate))
            ->when($this->endDate, fn ($query) => $query->whereDate('expense_date', '<=', $this->endDate));

        $this->applySorting($expenses);

        $expenses = $expenses->paginate(15);

        return view('livewire.expenses.expense-list', [
            'expenses' => $expenses,
            'categories' => ExpenseCategory::query()->where('is_active', true)->orderBy('name')->get(),
            'allCategories' => ExpenseCategory::query()->orderBy('name')->get(),
            'paymentStatuses' => ExpensePaymentStatus::cases(),
            'expenseTypes' => ExpenseType::cases(),
            'recurringFrequencies' => ExpenseRecurringFrequency::cases(),
            'sortableFields' => $this->sortableFields(),
        ])->layout('layouts.app', ['header' => 'المصروفات']);
    }

    private function applySorting($query): void
    {
        $direction = $this->sortDirection === 'desc' ? 'desc' : 'asc';

        match ($this->sortField) {
            'title' => $query->orderBy('title', $direction),
            'category' => $query->orderBy(
                ExpenseCategory::query()->select('name')->whereColumn('expense_categories.id', 'expenses.expense_category_id'),
                $direction
            ),
            'amount' => $query->orderBy('amount', $direction),
            'payment_status' => $query->orderBy('payment_status', $direction),
            'created_at' => $query->orderBy('created_at', $direction),
            'updated_at' => $query->orderBy('updated_at', $direction),
            default => $query->orderBy('expense_date', $direction),
        };

        $query->orderBy('id', $direction);
    }

    private function sortableFields(): array
    {
        return [
            'expense_date' => 'تاريخ المصروف',
            'title' => 'الوصف',
            'category' => 'الفئة',
            'amount' => 'القيمة',
            'payment_status' => 'حالة السداد',
            'created_at' => 'تاريخ الإنشاء',
            'updated_at' => 'آخر تحديث',
        ];
    }
}
