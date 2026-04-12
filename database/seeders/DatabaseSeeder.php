<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Partner;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Supplier;
use App\Models\User;
use App\Support\PrintTemplateSettings;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Default users — one per role for testing
        $users = [
            ['name' => 'Admin', 'email' => 'admin@ihome.com', 'role' => 'admin'],
            ['name' => 'Manager', 'email' => 'manager@ihome.com', 'role' => 'manager'],
            ['name' => 'Sales User', 'email' => 'sales@ihome.com', 'role' => 'sales'],
            ['name' => 'Inventory User', 'email' => 'inventory@ihome.com', 'role' => 'inventory'],
            ['name' => 'Purchasing User', 'email' => 'purchasing@ihome.com', 'role' => 'purchasing'],
        ];

        foreach ($users as $userData) {
            User::updateOrCreate(
                ['email' => $userData['email']],
                array_merge($userData, [
                    'password' => Hash::make('password'),
                    'is_active' => true,
                ])
            );
        }

        // Default settings
        $settings = [
            'company_name' => 'iHome',
            'company_phone' => '',
            'company_email' => '',
            'company_address' => '',
            'quotation_prefix' => 'QUO',
            'sales_invoice_prefix' => 'INV',
            'purchase_invoice_prefix' => 'PUR',
            'settlement_prefix' => 'SET',
            'default_quotation_validity_days' => '30',
        ];

        foreach ($settings as $key => $value) {
            Setting::set($key, $value);
        }

        PrintTemplateSettings::seedDefaults();
        PrintTemplateSettings::seedDefaultTemplates();

        foreach (['Lighting', 'Security', 'Climate Control', 'Sensors', 'Smart Switches'] as $category) {
            Category::firstOrCreate(['name' => $category]);
        }

        $suppliers = [
            ['name' => 'iHome Main Supplier', 'contact_person' => 'Operations Team', 'phone' => '+20 100 000 0001', 'email' => 'supplier@ihome.com'],
            ['name' => 'Smart Devices Distributor', 'contact_person' => 'Sales Desk', 'phone' => '+20 100 000 0002', 'email' => 'sales@smartdevices.example'],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::firstOrCreate(['name' => $supplier['name']], $supplier);
        }

        $admin = User::where('email', 'admin@ihome.com')->first();

        Customer::firstOrCreate(
            ['phone' => '+20 100 000 1000'],
            [
                'name' => 'Walk-in Customer',
                'email' => null,
                'address' => null,
                'notes' => 'Default showroom direct-sales customer.',
                'created_by' => $admin?->id,
            ],
        );

        $partners = [
            [
                'name' => 'Design Office Partner',
                'type' => 'engineering_office',
                'contact_person' => 'Partner Desk',
                'phone' => '+20 100 000 2000',
                'email' => 'partner@designoffice.example',
                'default_commission_type' => 'percentage',
                'default_commission_value' => 5,
                'is_active' => true,
            ],
            [
                'name' => 'Contracting Company Partner',
                'type' => 'company',
                'contact_person' => 'Projects Team',
                'phone' => '+20 100 000 2001',
                'email' => 'projects@contracting.example',
                'default_commission_type' => 'percentage',
                'default_commission_value' => 7.5,
                'is_active' => true,
            ],
        ];

        foreach ($partners as $partner) {
            Partner::firstOrCreate(['name' => $partner['name']], $partner);
        }

        $lighting = Category::where('name', 'Lighting')->first();
        $security = Category::where('name', 'Security')->first();
        $climate = Category::where('name', 'Climate Control')->first();
        $mainSupplier = Supplier::where('name', 'iHome Main Supplier')->first();
        $smartSupplier = Supplier::where('name', 'Smart Devices Distributor')->first();

        $products = [
            [
                'name' => 'Smart Dimmer Switch',
                'internal_sku' => 'IH-SW-DIM-001',
                'barcode' => null,
                'category_id' => $lighting?->id,
                'supplier_id' => $mainSupplier?->id,
                'sale_price' => 1850,
                'current_average_cost' => 1200,
                'minimum_stock_alert_level' => 5,
                'is_active' => true,
                'notes' => 'Starter lighting control product.',
            ],
            [
                'name' => 'Door Contact Sensor',
                'internal_sku' => 'IH-SE-DOOR-001',
                'barcode' => null,
                'category_id' => $security?->id,
                'supplier_id' => $smartSupplier?->id,
                'sale_price' => 950,
                'current_average_cost' => 620,
                'minimum_stock_alert_level' => 10,
                'is_active' => true,
                'notes' => null,
            ],
            [
                'name' => 'Smart Thermostat',
                'internal_sku' => 'IH-CL-THERM-001',
                'barcode' => null,
                'category_id' => $climate?->id,
                'supplier_id' => $mainSupplier?->id,
                'sale_price' => 4200,
                'current_average_cost' => 3100,
                'minimum_stock_alert_level' => 3,
                'is_active' => true,
                'notes' => null,
            ],
        ];

        foreach ($products as $product) {
            if ($product['category_id']) {
                Product::firstOrCreate(['internal_sku' => $product['internal_sku']], $product);
            }
        }
    }
}
