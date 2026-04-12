<?php

namespace App\Livewire\Settings\PrintTemplates;

use App\Models\PrintTemplate;
use App\Support\PrintTemplateSettings;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class PrintTemplateForm extends Component
{
    use WithFileUploads;

    public ?PrintTemplate $printTemplate = null;

    public bool $isEditing = false;

    public string $name = '';

    public string $code = '';

    public string $document_type = PrintTemplate::TYPE_QUOTATION;

    public bool $is_active = true;

    public bool $is_default = false;

    public string $title = 'عرض سعر';

    public string $notes = '';

    public int|string $sort_order = 0;

    public array $settings = [];

    public $logoUpload = null;

    public $headerImageUpload = null;

    public $footerImageUpload = null;

    public function mount(?PrintTemplate $printTemplate = null): void
    {
        $this->settings = PrintTemplateSettings::templateDefaults($this->document_type);

        if ($printTemplate?->exists) {
            $this->printTemplate = $printTemplate;
            $this->isEditing = true;
            $this->name = $printTemplate->name;
            $this->code = $printTemplate->code;
            $this->document_type = $printTemplate->document_type;
            $this->is_active = $printTemplate->is_active;
            $this->is_default = $printTemplate->is_default;
            $this->title = $printTemplate->title;
            $this->notes = $printTemplate->notes ?? '';
            $this->sort_order = $printTemplate->sort_order;
            $this->settings = array_replace_recursive(
                PrintTemplateSettings::templateDefaults($this->document_type),
                $printTemplate->settings ?? [],
            );
        }
    }

    protected function rules(): array
    {
        $options = PrintTemplateSettings::options();

        return array_merge([
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'nullable',
                'string',
                'max:120',
                Rule::unique('print_templates', 'code')->ignore($this->printTemplate?->id),
            ],
            'document_type' => ['required', Rule::in(array_keys(PrintTemplate::documentTypes()))],
            'is_active' => ['boolean'],
            'is_default' => ['boolean'],
            'title' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:999999'],
            'settings.company.use_global_identity' => ['boolean'],
            'settings.company.name' => ['nullable', 'string', 'max:255'],
            'settings.company.logo_path' => ['nullable', 'string', 'max:500'],
            'settings.company.header_image_path' => ['nullable', 'string', 'max:500'],
            'settings.company.footer_image_path' => ['nullable', 'string', 'max:500'],
            'logoUpload' => ['nullable', 'image', 'max:2048'],
            'headerImageUpload' => ['nullable', 'image', 'max:4096'],
            'footerImageUpload' => ['nullable', 'image', 'max:4096'],
            'settings.company.phone' => ['nullable', 'string', 'max:255'],
            'settings.company.email' => ['nullable', 'email', 'max:255'],
            'settings.company.address' => ['nullable', 'string', 'max:500'],
            'settings.company.website' => ['nullable', 'string', 'max:255'],
            'settings.company.tax_number' => ['nullable', 'string', 'max:255'],
            'settings.company.registration_number' => ['nullable', 'string', 'max:255'],
            'settings.company.currency_label' => ['required', 'string', 'max:50'],
            'settings.general.footer_text' => ['nullable', 'string', 'max:1000'],
            'settings.general.thank_you_message' => ['nullable', 'string', 'max:1000'],
            'settings.general.disclaimer' => ['nullable', 'string', 'max:2000'],
            'settings.layout.paper_size' => ['required', Rule::in(array_keys($options['paper_size']))],
            'settings.layout.header_alignment' => ['required', Rule::in(array_keys($options['header_alignment']))],
            'settings.layout.spacing' => ['required', Rule::in(array_keys($options['spacing']))],
            'settings.layout.font_size' => ['required', Rule::in(array_keys($options['font_size']))],
            'settings.layout.table_density' => ['required', Rule::in(array_keys($options['table_density']))],
            'settings.layout.logo_size' => ['required', Rule::in(array_keys($options['logo_size']))],
            'settings.layout.margin' => ['required', Rule::in(array_keys($options['margin']))],
            'settings.quotation.footer_text' => ['nullable', 'string', 'max:1000'],
            'settings.quotation.terms' => ['nullable', 'string', 'max:2000'],
            'settings.quotation.installation_item_name' => ['required', 'string', 'max:255'],
            'settings.sales_invoice.footer_text' => ['nullable', 'string', 'max:1000'],
            'settings.sales_invoice.installation_item_name' => ['required', 'string', 'max:255'],
            'settings.warranty.title' => ['required', 'string', 'max:255'],
            'settings.warranty.body' => ['nullable', 'string', 'max:5000'],
            'settings.warranty.footer_text' => ['nullable', 'string', 'max:1000'],
        ], $this->booleanRules());
    }

    public function updatedDocumentType(): void
    {
        if ($this->isEditing) {
            return;
        }

        $this->settings = PrintTemplateSettings::templateDefaults($this->document_type);
        $this->title = PrintTemplate::defaultTitleFor($this->document_type);
    }

    public function save(): void
    {
        $data = $this->validate();
        $settings = $data['settings'];

        $this->storeBrandingUploads($settings);

        data_set($settings, $this->document_type.'.title', $data['title']);

        $template = $this->printTemplate ?: new PrintTemplate;
        $template->fill([
            'name' => $data['name'],
            'code' => $data['code'] ?: PrintTemplate::generateCode($data['document_type']),
            'document_type' => $data['document_type'],
            'is_active' => $data['is_active'],
            'is_default' => $data['is_default'],
            'title' => $data['title'],
            'notes' => $data['notes'] ?: null,
            'sort_order' => (int) $data['sort_order'],
            'settings' => $settings,
        ]);
        $template->save();

        session()->flash('success', $this->isEditing ? 'تم تحديث قالب الطباعة.' : 'تم إنشاء قالب الطباعة.');

        $this->redirect(route('settings.print'), navigate: true);
    }

    public function loadDefaults(): void
    {
        $this->settings = PrintTemplateSettings::templateDefaults($this->document_type);
        $this->title = PrintTemplate::defaultTitleFor($this->document_type);
        session()->flash('success', 'تم تحميل إعدادات القالب الافتراضية. اضغط حفظ لتطبيقها.');
    }

    public function render()
    {
        return view('livewire.settings.print-templates.print-template-form', [
            'documentTypes' => PrintTemplate::documentTypes(),
            'options' => PrintTemplateSettings::options(),
            'pageTitle' => $this->isEditing ? 'تعديل قالب طباعة' : 'إنشاء قالب طباعة',
        ])->layout('layouts.app', ['header' => $this->isEditing ? 'تعديل قالب طباعة' : 'إنشاء قالب طباعة']);
    }

    private function booleanRules(): array
    {
        return collect(PrintTemplateSettings::booleanPaths())
            ->mapWithKeys(fn (string $path) => ['settings.'.$path => ['boolean']])
            ->all();
    }

    private function storeBrandingUploads(array &$settings): void
    {
        $uploads = [
            'logoUpload' => 'logo_path',
            'headerImageUpload' => 'header_image_path',
            'footerImageUpload' => 'footer_image_path',
        ];

        foreach ($uploads as $property => $path) {
            if (! $this->{$property}) {
                continue;
            }

            $storedPath = $this->{$property}->store('print-templates', 'public');
            data_set($settings, 'company.'.$path, Storage::disk('public')->url($storedPath));
        }
    }
}
