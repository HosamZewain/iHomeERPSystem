<?php

namespace App\Livewire\Suppliers;

use App\Models\Supplier;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class SupplierList extends Component
{
    use WithPagination;

    public string $search = '';
    public string $contactFilter = '';
    public bool $showForm = false;
    public ?int $editingId = null;
    public string $name = '';
    public string $contact_person = '';
    public string $phone = '';
    public string $email = '';
    public string $address = '';
    public string $notes = '';

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('suppliers', 'name')->ignore($this->editingId)],
            'contact_person' => ['nullable', 'string', 'max:255'],
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

    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $supplier = Supplier::findOrFail($id);

        $this->editingId = $supplier->id;
        $this->name = $supplier->name;
        $this->contact_person = $supplier->contact_person ?? '';
        $this->phone = $supplier->phone;
        $this->email = $supplier->email ?? '';
        $this->address = $supplier->address ?? '';
        $this->notes = $supplier->notes ?? '';
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
            Supplier::findOrFail($this->editingId)->update($data);
            session()->flash('success', 'تم تحديث المورد.');
        } else {
            Supplier::create($data);
            session()->flash('success', 'تم إنشاء المورد.');
        }

        $this->resetForm();
    }

    public function delete(int $id): void
    {
        $supplier = Supplier::findOrFail($id);

        if (! $supplier->canDelete()) {
            session()->flash('error', 'لا يمكن حذف "' . $supplier->name . '" لأنه مرتبط بمنتجات أو فواتير شراء.');
            return;
        }

        $supplier->delete();
        session()->flash('success', 'تم حذف المورد.');
    }

    private function resetForm(): void
    {
        $this->reset([
            'showForm',
            'editingId',
            'name',
            'contact_person',
            'phone',
            'email',
            'address',
            'notes',
        ]);
        $this->resetValidation();
    }

    public function render()
    {
        $suppliers = Supplier::query()
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('name', 'like', "%{$this->search}%")
                        ->orWhere('contact_person', 'like', "%{$this->search}%")
                        ->orWhere('phone', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%");
                });
            })
            ->when($this->contactFilter === 'with_email', fn ($query) => $query->whereNotNull('email')->where('email', '!=', ''))
            ->when($this->contactFilter === 'without_email', fn ($query) => $query->where(function ($query) {
                $query->whereNull('email')->orWhere('email', '');
            }))
            ->when($this->contactFilter === 'with_contact_person', fn ($query) => $query->whereNotNull('contact_person')->where('contact_person', '!=', ''))
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.suppliers.supplier-list', [
            'suppliers' => $suppliers,
        ])->layout('layouts.app', ['header' => 'الموردون']);
    }
}
