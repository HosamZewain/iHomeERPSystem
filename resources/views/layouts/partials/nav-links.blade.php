@php $user = auth()->user(); @endphp

<x-nav-link route="dashboard" icon="home" :label="__('ui.nav.dashboard')" />

@if($user->hasAnyPermission(['products.manage', 'stock.view', 'categories.manage']))
    <div class="pt-4 pb-1">
        <p class="px-3 text-xs font-semibold text-primary-400 uppercase tracking-wider">{{ __('ui.nav.catalog') }}</p>
    </div>
    @if($user->hasPermission('products.manage'))
        <x-nav-link route="products.index" icon="cube" :label="__('ui.nav.products')" />
    @endif
    @if($user->hasPermission('categories.manage'))
        <x-nav-link route="categories.index" icon="tag" :label="__('ui.nav.categories')" />
    @endif
@endif

@if($user->hasAnyPermission(['quotations.create', 'sales.create']))
    <div class="pt-4 pb-1">
        <p class="px-3 text-xs font-semibold text-primary-400 uppercase tracking-wider">{{ __('ui.nav.sales') }}</p>
    </div>
    @if($user->hasPermission('quotations.create'))
        <x-nav-link route="quotations.index" icon="document-text" :label="__('ui.nav.quotations')" />
    @endif
    @if($user->hasPermission('sales.create') || $user->isAdmin())
        <x-nav-link route="sales-invoices.index" icon="receipt" :label="__('ui.nav.sales_invoices')" />
    @endif
@endif

@if($user->hasPermission('purchases.manage'))
    <div class="pt-4 pb-1">
        <p class="px-3 text-xs font-semibold text-primary-400 uppercase tracking-wider">{{ __('ui.nav.purchasing') }}</p>
    </div>
    <x-nav-link route="purchase-invoices.index" icon="truck" :label="__('ui.nav.purchase_invoices')" />
@endif

@if($user->hasAnyPermission(['customers.create', 'suppliers.manage', 'partners.manage']))
    <div class="pt-4 pb-1">
        <p class="px-3 text-xs font-semibold text-primary-400 uppercase tracking-wider">{{ __('ui.nav.people') }}</p>
    </div>
    @if($user->hasPermission('customers.create'))
        <x-nav-link route="customers.index" icon="users" :label="__('ui.nav.customers')" />
    @endif
    @if($user->hasPermission('suppliers.manage'))
        <x-nav-link route="suppliers.index" icon="building" :label="__('ui.nav.suppliers')" />
    @endif
    @if($user->hasPermission('partners.manage'))
        <x-nav-link route="partners.index" icon="handshake" :label="__('ui.nav.partners')" />
    @endif
@endif

@if($user->hasPermission('stock.view'))
    <div class="pt-4 pb-1">
        <p class="px-3 text-xs font-semibold text-primary-400 uppercase tracking-wider">{{ __('ui.nav.inventory') }}</p>
    </div>
    <x-nav-link route="stock.index" icon="archive" :label="__('ui.nav.stock_summary')" />
@endif

@if($user->hasPermission('reports.view'))
    <div class="pt-4 pb-1">
        <p class="px-3 text-xs font-semibold text-primary-400 uppercase tracking-wider">{{ __('ui.nav.reports') }}</p>
    </div>
    <x-nav-link route="reports.sales" icon="chart-bar" :label="__('ui.nav.sales_report')" />
    <x-nav-link route="reports.stock" icon="archive" :label="__('ui.nav.stock_report')" />
@endif

@if($user->hasPermission('users.manage'))
    <div class="pt-4 pb-1">
        <p class="px-3 text-xs font-semibold text-primary-400 uppercase tracking-wider">{{ __('ui.nav.system') }}</p>
    </div>
    @if($user->hasPermission('settings.manage'))
        <x-nav-link route="settings.print" icon="cog" :label="__('ui.nav.print_settings')" />
        <x-nav-link route="settings.backups" icon="archive" :label="__('ui.nav.database_backups')" />
    @endif
    <x-nav-link route="users.index" icon="user-group" :label="__('ui.nav.users')" />
@endif
