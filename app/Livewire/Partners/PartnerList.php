<?php

namespace App\Livewire\Partners;

use App\Enums\CommissionType;
use App\Enums\PartnerType;
use App\Models\Partner;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class PartnerList extends Component
{
    use WithPagination;

    public string $search = '';
    public string $typeFilter = '';
    public string $statusFilter = '';
    public bool $showForm = false;
    public ?int $editingId = null;
    public string $name = '';
    public string $type = 'engineering_office';
    public string $contact_person = '';
    public string $phone = '';
    public string $email = '';
    public string $address = '';
    public string $default_commission_type = 'percentage';
    public string $default_commission_value = '0';
    public string $notes = '';
    public bool $is_active = true;

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('partners', 'name')->ignore($this->editingId)],
            'type' => ['required', Rule::in(array_column(PartnerType::cases(), 'value'))],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'default_commission_type' => ['required', Rule::in(array_column(CommissionType::cases(), 'value'))],
            'default_commission_value' => [
                'required',
                'numeric',
                'min:0',
                $this->default_commission_type === CommissionType::Percentage->value ? 'max:100' : 'max:999999999.99',
            ],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['boolean'],
        ];
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
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
        $partner = Partner::findOrFail($id);

        $this->editingId = $partner->id;
        $this->name = $partner->name;
        $this->type = $partner->type->value;
        $this->contact_person = $partner->contact_person ?? '';
        $this->phone = $partner->phone;
        $this->email = $partner->email ?? '';
        $this->address = $partner->address ?? '';
        $this->default_commission_type = $partner->default_commission_type->value;
        $this->default_commission_value = (string) $partner->default_commission_value;
        $this->notes = $partner->notes ?? '';
        $this->is_active = $partner->is_active;
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
            Partner::findOrFail($this->editingId)->update($data);
            session()->flash('success', 'تم تحديث الشريك.');
        } else {
            Partner::create($data);
            session()->flash('success', 'تم إنشاء الشريك.');
        }

        $this->resetForm();
    }

    public function toggleActive(int $id): void
    {
        $partner = Partner::findOrFail($id);
        $partner->update(['is_active' => ! $partner->is_active]);

        session()->flash('success', 'تم ' . ($partner->is_active ? 'تفعيل ' : 'إيقاف ') . $partner->name . '.');
    }

    public function delete(int $id): void
    {
        $partner = Partner::findOrFail($id);

        if (! $partner->canDelete()) {
            session()->flash('error', 'لا يمكن حذف "' . $partner->name . '" لأنه مرتبط بفواتير بيع أو تسويات. يمكن إيقافه بدلًا من الحذف.');
            return;
        }

        $partner->delete();
        session()->flash('success', 'تم حذف الشريك.');
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
        $this->type = PartnerType::EngineeringOffice->value;
        $this->default_commission_type = CommissionType::Percentage->value;
        $this->default_commission_value = '0';
        $this->is_active = true;
        $this->resetValidation();
    }

    public function render()
    {
        $partners = Partner::query()
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('name', 'like', "%{$this->search}%")
                        ->orWhere('contact_person', 'like', "%{$this->search}%")
                        ->orWhere('phone', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%");
                });
            })
            ->when($this->typeFilter, fn ($query) => $query->where('type', $this->typeFilter))
            ->when($this->statusFilter !== '', fn ($query) => $query->where('is_active', $this->statusFilter === 'active'))
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.partners.partner-list', [
            'partners' => $partners,
            'types' => PartnerType::cases(),
            'commissionTypes' => CommissionType::cases(),
        ])->layout('layouts.app', ['header' => 'الشركاء']);
    }
}
