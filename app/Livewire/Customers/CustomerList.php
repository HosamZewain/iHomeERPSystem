<?php

namespace App\Livewire\Customers;

use App\Models\Customer;
use Livewire\Component;
use Livewire\WithPagination;

class CustomerList extends Component
{
    use WithPagination;

    public string $search = '';
    public string $contactFilter = '';
    public string $sortField = 'name';
    public string $sortDirection = 'asc';
    public bool $showForm = false;
    public ?int $editingId = null;
    public string $name = '';
    public string $phone = '';
    public string $email = '';
    public string $address = '';
    public string $notes = '';

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingContactFilter(): void
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
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $customer = Customer::findOrFail($id);

        $this->editingId = $customer->id;
        $this->name = $customer->name;
        $this->phone = $customer->phone;
        $this->email = $customer->email ?? '';
        $this->address = $customer->address ?? '';
        $this->notes = $customer->notes ?? '';
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
            Customer::findOrFail($this->editingId)->update($data);
            session()->flash('success', 'تم تحديث العميل.');
        } else {
            $data['created_by'] = auth()->id();
            Customer::create($data);
            session()->flash('success', 'تم إنشاء العميل.');
        }

        $this->resetForm();
    }

    public function delete(int $id): void
    {
        $customer = Customer::findOrFail($id);

        if (! $customer->canDelete()) {
            session()->flash('error', 'لا يمكن حذف "' . $customer->name . '" لأنه مرتبط بعروض أسعار أو فواتير بيع.');
            return;
        }

        $customer->delete();
        session()->flash('success', 'تم حذف العميل.');
    }

    private function resetForm(): void
    {
        $this->reset([
            'showForm',
            'editingId',
            'name',
            'phone',
            'email',
            'address',
            'notes',
        ]);
        $this->resetValidation();
    }

    public function render()
    {
        $customers = Customer::query()
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('name', 'like', "%{$this->search}%")
                        ->orWhere('phone', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%");
                });
            })
            ->when($this->contactFilter === 'with_email', fn ($query) => $query->whereNotNull('email')->where('email', '!=', ''))
            ->when($this->contactFilter === 'without_email', fn ($query) => $query->where(function ($query) {
                $query->whereNull('email')->orWhere('email', '');
            }))
            ->when($this->contactFilter === 'with_address', fn ($query) => $query->whereNotNull('address')->where('address', '!=', ''));

        $this->applySorting($customers);

        $customers = $customers->paginate(15);

        return view('livewire.customers.customer-list', [
            'customers' => $customers,
            'sortableFields' => $this->sortableFields(),
        ])->layout('layouts.app', ['header' => 'العملاء']);
    }

    private function applySorting($query): void
    {
        $direction = $this->sortDirection === 'desc' ? 'desc' : 'asc';

        match ($this->sortField) {
            'phone' => $query->orderBy('phone', $direction),
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
            'name' => 'العميل',
            'phone' => 'الهاتف',
            'created_at' => 'تاريخ الإنشاء',
            'updated_at' => 'آخر تحديث',
        ];
    }
}
