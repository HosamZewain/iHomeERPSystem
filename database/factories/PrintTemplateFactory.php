<?php

namespace Database\Factories;

use App\Models\PrintTemplate;
use App\Support\PrintTemplateSettings;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PrintTemplate>
 */
class PrintTemplateFactory extends Factory
{
    protected $model = PrintTemplate::class;

    public function definition(): array
    {
        $documentType = fake()->randomElement([
            PrintTemplate::TYPE_QUOTATION,
            PrintTemplate::TYPE_SALES_INVOICE,
        ]);

        return [
            'name' => 'قالب '.PrintTemplate::documentTypes()[$documentType].' '.fake()->unique()->numberBetween(1, 999),
            'code' => PrintTemplate::generateCode($documentType),
            'document_type' => $documentType,
            'is_active' => true,
            'is_default' => false,
            'title' => PrintTemplate::defaultTitleFor($documentType),
            'notes' => null,
            'sort_order' => 0,
            'settings' => PrintTemplateSettings::templateDefaults($documentType),
        ];
    }
}
