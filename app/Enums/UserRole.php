<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Manager = 'manager';
    case Sales = 'sales';
    case Inventory = 'inventory';
    case Purchasing = 'purchasing';

    public function label(): string
    {
        return match ($this) {
            self::Admin => __('ui.roles.admin'),
            self::Manager => __('ui.roles.manager'),
            self::Sales => __('ui.roles.sales'),
            self::Inventory => __('ui.roles.inventory'),
            self::Purchasing => __('ui.roles.purchasing'),
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Admin => __('ui.role_descriptions.admin'),
            self::Manager => __('ui.role_descriptions.manager'),
            self::Sales => __('ui.role_descriptions.sales'),
            self::Inventory => __('ui.role_descriptions.inventory'),
            self::Purchasing => __('ui.role_descriptions.purchasing'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Admin => 'red',
            self::Manager => 'purple',
            self::Sales => 'blue',
            self::Inventory => 'green',
            self::Purchasing => 'yellow',
        };
    }

    public function permissions(): array
    {
        return match ($this) {
            self::Admin => [
                'users.manage',
                'categories.manage',
                'suppliers.manage',
                'customers.create', 'customers.edit',
                'partners.manage',
                'products.manage', 'products.view_cost',
                'purchases.manage',
                'quotations.create', 'quotations.edit', 'quotations.convert',
                'sales.create', 'sales.void', 'sales.view_profit',
                'settlements.view',
                'stock.view', 'stock.view_cost', 'stock.movements',
                'reports.view',
                'settings.manage',
            ],
            self::Manager => [
                'categories.manage',
                'suppliers.manage',
                'customers.create', 'customers.edit',
                'partners.manage',
                'products.manage', 'products.view_cost',
                'purchases.manage',
                'quotations.create', 'quotations.edit', 'quotations.convert',
                'sales.create', 'sales.void', 'sales.view_profit',
                'settlements.view',
                'stock.view', 'stock.view_cost', 'stock.movements',
                'reports.view',
            ],
            self::Sales => [
                'customers.create', 'customers.edit',
                'quotations.create', 'quotations.edit', 'quotations.convert',
                'sales.create',
                'stock.view',
                'products.view_cost',
            ],
            self::Inventory => [
                'categories.manage',
                'products.manage', 'products.view_cost',
                'stock.view', 'stock.view_cost', 'stock.movements',
                'suppliers.manage',
            ],
            self::Purchasing => [
                'suppliers.manage',
                'purchases.manage',
                'products.view_cost',
                'stock.view', 'stock.view_cost',
            ],
        };
    }
}
