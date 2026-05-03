@php
    $quotationPrint = $printSettings['quotation'];
    $showCustomerInfo = collect([
        $quotationPrint['show_customer_name'],
        $quotationPrint['show_customer_phone'],
        $quotationPrint['show_customer_email'],
        $quotationPrint['show_customer_address'],
    ])->contains(true);
    $invoiceDiscountValue = $quotation->invoice_discount_type === \App\Models\Quotation::DISCOUNT_PERCENTAGE
        ? number_format((float) $quotation->invoice_discount_value, 2) . '%'
        : \App\Support\Money::format($quotation->invoice_discount_value);
    $installationValue = $quotation->installation_pricing_mode === \App\Models\Quotation::INSTALLATION_PERCENTAGE
        ? number_format((float) $quotation->installation_percentage_value, 2) . '% من إجمالي المنتجات'
        : \App\Support\Money::format($quotation->installation_fixed_amount);
    $netProductsTotal = max((float) $quotation->subtotal - (float) $quotation->invoice_discount_amount, 0);
    $showInstallationRow = $quotationPrint['show_installation'] && $quotation->installation_enabled;
    $installationItemName = filled($quotationPrint['installation_item_name'] ?? null) ? $quotationPrint['installation_item_name'] : 'خدمة التركيب';
    $printedItemsCount = $quotation->items->where('row_type', \App\Models\QuotationItem::TYPE_PRODUCT)->count() + ($showInstallationRow ? 1 : 0);
@endphp

@extends('print.layout')

@section('document-meta')
    @if($quotationPrint['show_number'])
        <div><strong>{{ $quotation->quotation_number }}</strong></div>
    @endif
    @if($quotationPrint['show_date'])
        <div>{{ $quotation->quotation_date->format('Y-m-d') }}</div>
    @endif
    @if($quotationPrint['show_status'])
        <div>{{ $quotation->status->label() }}</div>
    @endif
@endsection

@section('content')
    <section class="grid">
        @if($showCustomerInfo)
            <div class="box">
                <h2>بيانات العميل</h2>
                @if($quotationPrint['show_customer_name'])
                    <div class="row"><span>الاسم</span><strong>{{ $quotation->customer->name }}</strong></div>
                @endif
                @if($quotationPrint['show_customer_phone'])
                    <div class="row"><span>الهاتف</span><span>{{ $quotation->customer->phone ?: '-' }}</span></div>
                @endif
                @if($quotationPrint['show_customer_email'])
                    <div class="row"><span>البريد الإلكتروني</span><span>{{ $quotation->customer->email ?: '-' }}</span></div>
                @endif
                @if($quotationPrint['show_customer_address'])
                    <div class="row"><span>العنوان</span><span>{{ $quotation->customer->address ?: '-' }}</span></div>
                @endif
            </div>
        @endif

        <div class="box">
            <h2>بيانات العرض</h2>
            @if($quotationPrint['show_number'])
                <div class="row"><span>رقم العرض</span><strong>{{ $quotation->quotation_number }}</strong></div>
            @endif
            @if($quotationPrint['show_date'])
                <div class="row"><span>التاريخ</span><span>{{ $quotation->quotation_date->format('Y-m-d') }}</span></div>
            @endif
            @if($quotationPrint['show_status'])
                <div class="row"><span>الحالة</span><span>{{ $quotation->status->label() }}</span></div>
            @endif
            @if($quotationPrint['show_creator'])
                <div class="row"><span>مسؤول البيع</span><span>{{ $quotation->creator?->name ?: '-' }}</span></div>
            @endif
        </div>
    </section>

    <section class="section-title">
        <h2>بنود العرض</h2>
        <span>{{ $printedItemsCount }} بند</span>
    </section>

    <table>
        <thead>
            <tr>
                <th class="sequence">م</th>
                @if($quotationPrint['show_product_images'])
                    <th class="image-column">الصورة</th>
                @endif
                <th>المنتج</th>
                <th>الكمية</th>
                <th>سعر الوحدة</th>
                @if($quotationPrint['show_item_discounts'])
                    <th>الخصم</th>
                @endif
                <th>إجمالي السطر</th>
            </tr>
        </thead>
        <tbody>
            @php $printedSequence = 0; @endphp
            @foreach($quotation->items as $item)
                @if($item->isSection())
                    <tr>
                        <td colspan="{{ $quotationPrint['show_product_images'] ? ($quotationPrint['show_item_discounts'] ? 7 : 6) : ($quotationPrint['show_item_discounts'] ? 6 : 5) }}"
                            style="background:#eef4ff;font-weight:700;color:#1e3a8a;">
                            {{ $item->section_title }}
                        </td>
                    </tr>
                @else
                    @php
                        $printedSequence++;
                        $discountValue = $item->item_discount_type === \App\Models\Quotation::DISCOUNT_PERCENTAGE
                            ? number_format((float) $item->item_discount_value, 2) . '%'
                            : \App\Support\Money::format($item->item_discount_value);
                    @endphp
                    <tr>
                        <td class="sequence">{{ $printedSequence }}</td>
                        @if($quotationPrint['show_product_images'])
                            <td class="image-column">
                                @if($item->product?->image_path)
                                    <img class="product-image" src="{{ $item->product->image_path }}" alt="{{ $item->product?->name }}">
                                @else
                                    <span class="muted">-</span>
                                @endif
                            </td>
                        @endif
                        <td>
                            <div class="product-name">{{ $item->product?->name }}</div>
                            <div class="muted">{{ $item->product?->internal_sku }}</div>
                            @if($item->description)
                                <div class="muted" style="margin-top:6px; white-space:pre-line;">{{ $item->description }}</div>
                            @endif
                        </td>
                        <td class="number">{{ number_format((float) $item->quantity, 2) }}</td>
                        <td class="number">{{ \App\Support\Money::format($item->unit_sale_price) }}</td>
                        @if($quotationPrint['show_item_discounts'])
                            <td>
                                <div>{{ $discountTypes[$item->item_discount_type] ?? $item->item_discount_type }}: {{ $discountValue }}</div>
                                <div class="muted">القيمة: {{ \App\Support\Money::format($item->item_discount_amount) }}</div>
                            </td>
                        @endif
                        <td class="number">{{ \App\Support\Money::format($item->line_total) }}</td>
                    </tr>
                @endif
            @endforeach
            @if($showInstallationRow)
                <tr>
                    <td class="sequence">{{ $printedSequence + 1 }}</td>
                    @if($quotationPrint['show_product_images'])
                        <td class="image-column"><span class="muted">-</span></td>
                    @endif
                    <td>
                        <div class="product-name">{{ $installationItemName }}</div>
                        <div class="muted">{{ $installationValue }}</div>
                        @if($quotation->installation_notes)
                            <div class="muted">{{ $quotation->installation_notes }}</div>
                        @endif
                    </td>
                    <td class="number">1.00</td>
                    <td class="number">{{ \App\Support\Money::format($quotation->installation_total) }}</td>
                    @if($quotationPrint['show_item_discounts'])
                        <td><span class="muted">-</span></td>
                    @endif
                    <td class="number">{{ \App\Support\Money::format($quotation->installation_total) }}</td>
                </tr>
            @endif
        </tbody>
    </table>

    @if($quotationPrint['show_subtotal'] || $quotationPrint['show_invoice_discount'] || $quotationPrint['show_total'])
        <section class="summary">
            @if($quotationPrint['show_subtotal'])
                <div class="row">
                    <span>إجمالي المنتجات</span>
                    <strong class="number">{{ \App\Support\Money::format($quotation->subtotal) }}</strong>
                </div>
            @endif
            @if($quotationPrint['show_invoice_discount'])
                <div class="row">
                    <span>خصم العرض على المنتجات {{ $invoiceDiscountValue }}</span>
                    <strong class="number">-{{ \App\Support\Money::format($quotation->invoice_discount_amount) }}</strong>
                </div>
            @endif
            @if($quotationPrint['show_subtotal'] || $quotationPrint['show_invoice_discount'])
                <div class="row">
                    <span>صافي المنتجات</span>
                    <strong class="number">{{ \App\Support\Money::format($netProductsTotal) }}</strong>
                </div>
            @endif
            @if($showInstallationRow)
                <div class="row">
                    <span>{{ $installationItemName }}</span>
                    <strong class="number">{{ \App\Support\Money::format($quotation->installation_total) }}</strong>
                </div>
            @endif
            @if($quotationPrint['show_total'])
                <div class="row total">
                    <span>الإجمالي النهائي</span>
                    <strong class="number">{{ \App\Support\Money::format($quotation->total) }}</strong>
                </div>
            @endif
        </section>
    @endif

    @if($quotationPrint['show_notes'] && $quotation->notes)
        <section class="notes">
            <strong>ملاحظات</strong>
            <div>{{ $quotation->notes }}</div>
        </section>
    @endif

    @if($quotationPrint['show_terms'] && filled($quotationPrint['terms'] ?? null))
        <section class="terms">
            <strong>الشروط والأحكام</strong>
            <div>{{ $quotationPrint['terms'] }}</div>
        </section>
    @endif
@endsection
