@php
    $invoicePrint = $printSettings['sales_invoice'];
    $showCustomerInfo = collect([
        $invoicePrint['show_customer_name'],
        $invoicePrint['show_customer_phone'],
        $invoicePrint['show_customer_email'],
        $invoicePrint['show_customer_address'],
    ])->contains(true);
    $invoiceDiscountValue = $invoice->invoice_discount_type === \App\Models\SalesInvoice::DISCOUNT_PERCENTAGE
        ? number_format((float) $invoice->invoice_discount_value, 2) . '%'
        : \App\Support\Money::format($invoice->invoice_discount_value);
    $installationValue = $invoice->installation_pricing_mode === \App\Models\SalesInvoice::INSTALLATION_PERCENTAGE
        ? number_format((float) $invoice->installation_percentage_value, 2) . '% من إجمالي المنتجات'
        : \App\Support\Money::format($invoice->installation_fixed_amount);
    $netProductsTotal = max((float) $invoice->subtotal - (float) $invoice->invoice_discount_amount, 0);
    $showInstallationRow = $invoicePrint['show_installation'] && $invoice->installation_enabled;
    $installationItemName = filled($invoicePrint['installation_item_name'] ?? null) ? $invoicePrint['installation_item_name'] : 'خدمة التركيب';
    $printedItemsCount = $invoice->items->count() + ($showInstallationRow ? 1 : 0);
@endphp

@extends('print.layout')

@section('document-meta')
    @if($invoicePrint['show_number'])
        <div><strong>{{ $invoice->invoice_number }}</strong></div>
    @endif
    @if($invoicePrint['show_date'])
        <div>{{ $invoice->invoice_date->format('Y-m-d') }}</div>
    @endif
    @if($invoicePrint['show_status'])
        <div>{{ $invoice->status->label() }}</div>
    @endif
@endsection

@section('content')
    <section class="grid">
        @if($showCustomerInfo)
            <div class="box">
                <h2>بيانات العميل</h2>
                @if($invoicePrint['show_customer_name'])
                    <div class="row"><span>الاسم</span><strong>{{ $invoice->customer?->name ?: 'عميل نقدي' }}</strong></div>
                @endif
                @if($invoicePrint['show_customer_phone'])
                    <div class="row"><span>الهاتف</span><span>{{ $invoice->customer?->phone ?: '-' }}</span></div>
                @endif
                @if($invoicePrint['show_customer_email'])
                    <div class="row"><span>البريد الإلكتروني</span><span>{{ $invoice->customer?->email ?: '-' }}</span></div>
                @endif
                @if($invoicePrint['show_customer_address'])
                    <div class="row"><span>العنوان</span><span>{{ $invoice->customer?->address ?: '-' }}</span></div>
                @endif
            </div>
        @endif

        <div class="box">
            <h2>بيانات الفاتورة</h2>
            @if($invoicePrint['show_number'])
                <div class="row"><span>رقم الفاتورة</span><strong>{{ $invoice->invoice_number }}</strong></div>
            @endif
            @if($invoicePrint['show_date'])
                <div class="row"><span>التاريخ</span><span>{{ $invoice->invoice_date->format('Y-m-d') }}</span></div>
            @endif
            @if($invoicePrint['show_status'])
                <div class="row"><span>الحالة</span><span>{{ $invoice->status->label() }}</span></div>
            @endif
            @if($invoicePrint['show_creator'])
                <div class="row"><span>مسؤول البيع</span><span>{{ $invoice->creator?->name ?: '-' }}</span></div>
            @endif
            @if($invoicePrint['show_quotation_reference'] && $invoice->quotation)
                <div class="row"><span>عرض السعر</span><span>{{ $invoice->quotation->quotation_number }}</span></div>
            @endif
        </div>
    </section>

    <section class="section-title">
        <h2>بنود الفاتورة</h2>
        <span>{{ $printedItemsCount }} بند</span>
    </section>

    <table>
        <thead>
            <tr>
                <th class="sequence">م</th>
                @if($invoicePrint['show_product_images'])
                    <th class="image-column">الصورة</th>
                @endif
                <th>المنتج</th>
                <th>الكمية</th>
                <th>سعر الوحدة</th>
                @if($invoicePrint['show_item_discounts'])
                    <th>الخصم</th>
                @endif
                <th>إجمالي السطر</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $item)
                @php
                    $discountValue = $item->item_discount_type === \App\Models\SalesInvoice::DISCOUNT_PERCENTAGE
                        ? number_format((float) $item->item_discount_value, 2) . '%'
                        : \App\Support\Money::format($item->item_discount_value);
                @endphp
                <tr>
                    <td class="sequence">{{ $loop->iteration }}</td>
                    @if($invoicePrint['show_product_images'])
                        <td class="image-column">
                            @if($item->product->image_path)
                                <img class="product-image" src="{{ $item->product->image_path }}" alt="{{ $item->product->name }}">
                            @else
                                <span class="muted">-</span>
                            @endif
                        </td>
                    @endif
                    <td>
                        <div class="product-name">{{ $item->product->name }}</div>
                        <div class="muted">{{ $item->product->internal_sku }}</div>
                    </td>
                    <td class="number">{{ number_format((float) $item->quantity, 2) }}</td>
                    <td class="number">{{ \App\Support\Money::format($item->unit_sale_price) }}</td>
                    @if($invoicePrint['show_item_discounts'])
                        <td>
                            <div>{{ $discountTypes[$item->item_discount_type] ?? $item->item_discount_type }}: {{ $discountValue }}</div>
                            <div class="muted">القيمة: {{ \App\Support\Money::format($item->item_discount_amount) }}</div>
                        </td>
                    @endif
                    <td class="number">{{ \App\Support\Money::format($item->line_total) }}</td>
                </tr>
            @endforeach
            @if($showInstallationRow)
                <tr>
                    <td class="sequence">{{ $invoice->items->count() + 1 }}</td>
                    @if($invoicePrint['show_product_images'])
                        <td class="image-column"><span class="muted">-</span></td>
                    @endif
                    <td>
                        <div class="product-name">{{ $installationItemName }}</div>
                        <div class="muted">{{ $installationValue }}</div>
                        @if($invoice->installation_notes)
                            <div class="muted">{{ $invoice->installation_notes }}</div>
                        @endif
                    </td>
                    <td class="number">1.00</td>
                    <td class="number">{{ \App\Support\Money::format($invoice->installation_total) }}</td>
                    @if($invoicePrint['show_item_discounts'])
                        <td><span class="muted">-</span></td>
                    @endif
                    <td class="number">{{ \App\Support\Money::format($invoice->installation_total) }}</td>
                </tr>
            @endif
        </tbody>
    </table>

    @if($invoicePrint['show_subtotal'] || $invoicePrint['show_invoice_discount'] || $invoicePrint['show_gross_total'])
        <section class="summary">
            @if($invoicePrint['show_subtotal'])
                <div class="row">
                    <span>إجمالي المنتجات</span>
                    <strong class="number">{{ \App\Support\Money::format($invoice->subtotal) }}</strong>
                </div>
            @endif
            @if($invoicePrint['show_invoice_discount'])
                <div class="row">
                    <span>خصم الفاتورة على المنتجات {{ $invoiceDiscountValue }}</span>
                    <strong class="number">-{{ \App\Support\Money::format($invoice->invoice_discount_amount) }}</strong>
                </div>
            @endif
            @if($invoicePrint['show_subtotal'] || $invoicePrint['show_invoice_discount'])
                <div class="row">
                    <span>صافي المنتجات</span>
                    <strong class="number">{{ \App\Support\Money::format($netProductsTotal) }}</strong>
                </div>
            @endif
            @if($showInstallationRow)
                <div class="row">
                    <span>{{ $installationItemName }}</span>
                    <strong class="number">{{ \App\Support\Money::format($invoice->installation_total) }}</strong>
                </div>
            @endif
            @if($invoicePrint['show_gross_total'])
                <div class="row total">
                    <span>إجمالي فاتورة العميل</span>
                    <strong class="number">{{ \App\Support\Money::format($invoice->gross_total) }}</strong>
                </div>
            @endif
        </section>
    @endif

    @if($invoicePrint['show_notes'] && $invoice->notes)
        <section class="notes">
            <strong>ملاحظات</strong>
            <div>{{ $invoice->notes }}</div>
        </section>
    @endif
@endsection
