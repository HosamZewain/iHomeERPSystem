<?php

namespace App\Livewire\Settings\PrintTemplates;

use App\Models\PrintTemplate;
use Livewire\Component;
use Livewire\WithPagination;

class PrintTemplateList extends Component
{
    use WithPagination;

    public string $search = '';

    public string $documentTypeFilter = '';

    public string $statusFilter = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingDocumentTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function toggleActive(int $templateId): void
    {
        $template = PrintTemplate::query()->findOrFail($templateId);
        $template->update(['is_active' => ! $template->is_active]);

        session()->flash('success', 'تم تحديث حالة القالب.');
    }

    public function setDefault(int $templateId): void
    {
        $template = PrintTemplate::query()->findOrFail($templateId);
        $template->update([
            'is_active' => true,
            'is_default' => true,
        ]);

        session()->flash('success', 'تم تعيين القالب كافتراضي لهذا النوع.');
    }

    public function render()
    {
        $templates = PrintTemplate::query()
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('name', 'like', "%{$this->search}%")
                        ->orWhere('code', 'like', "%{$this->search}%")
                        ->orWhere('title', 'like', "%{$this->search}%");
                });
            })
            ->when($this->documentTypeFilter, fn ($query) => $query->where('document_type', $this->documentTypeFilter))
            ->when($this->statusFilter === 'active', fn ($query) => $query->where('is_active', true))
            ->when($this->statusFilter === 'inactive', fn ($query) => $query->where('is_active', false))
            ->orderBy('document_type')
            ->orderByDesc('is_default')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.settings.print-templates.print-template-list', [
            'templates' => $templates,
            'documentTypes' => PrintTemplate::documentTypes(),
        ])->layout('layouts.app', ['header' => 'قوالب الطباعة']);
    }
}
