<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>مستند عمولة شريك {{ $invoice->invoice_number }}</title>
    <style>
        @page {
            size: A4;
            margin: 14mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #f3f4f6;
            color: #111827;
            font-family: Arial, "Tahoma", sans-serif;
            font-size: 13px;
            line-height: 1.6;
        }

        .toolbar {
            max-width: 210mm;
            margin: 16px auto;
            display: flex;
            justify-content: flex-end;
            gap: 8px;
        }

        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 40px;
            padding: 8px 14px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: #ffffff;
            color: #111827;
            font: inherit;
            text-decoration: none;
            cursor: pointer;
        }

        .button.primary {
            border-color: #111827;
            background: #111827;
            color: #ffffff;
        }

        .page {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto 24px;
            padding: 18mm;
            background: #ffffff;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.12);
        }

        .header {
            display: flex;
            justify-content: space-between;
            gap: 24px;
            padding-bottom: 18px;
            border-bottom: 2px solid #111827;
        }

        .company-name {
            margin: 0 0 8px;
            font-size: 24px;
            font-weight: 700;
        }

        .muted {
            color: #6b7280;
        }

        .title {
            margin: 0;
            font-size: 26px;
            font-weight: 700;
            text-align: left;
        }

        .meta {
            margin-top: 8px;
            text-align: left;
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-top: 20px;
        }

        .box {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 14px;
        }

        .box h2 {
            margin: 0 0 10px;
            font-size: 15px;
        }

        .row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 5px 0;
        }

        .row span:first-child {
            color: #6b7280;
            white-space: nowrap;
        }

        .summary {
            margin-top: 22px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 14px;
        }

        .summary .row {
            border-bottom: 1px solid #e5e7eb;
            padding: 10px 0;
        }

        .summary .row:last-child {
            border-bottom: 0;
        }

        .summary .total {
            font-size: 17px;
            font-weight: 700;
        }

        .number {
            direction: ltr;
            text-align: left;
            white-space: nowrap;
        }

        .notes {
            margin-top: 24px;
            border-top: 1px solid #e5e7eb;
            padding-top: 14px;
            white-space: pre-line;
        }

        .footer {
            margin-top: 28px;
            padding-top: 12px;
            border-top: 1px solid #e5e7eb;
            color: #6b7280;
            font-size: 12px;
        }

        @media print {
            body {
                background: #ffffff;
            }

            .no-print {
                display: none !important;
            }

            .page {
                width: auto;
                min-height: auto;
                margin: 0;
                padding: 0;
                box-shadow: none;
            }
        }
    </style>
</head>
<body>
    <div class="toolbar no-print">
        <a class="button" href="{{ route('sales-invoices.show', $invoice) }}">الرجوع</a>
        <button class="button primary" type="button" onclick="window.print()">طباعة / حفظ PDF</button>
    </div>

    @php
        $commissionValue = $invoice->partner_commission_type === \App\Models\SalesInvoice::DISCOUNT_PERCENTAGE
            ? number_format((float) $invoice->partner_commission_value, 2) . '%'
            : \App\Support\Money::format($invoice->partner_commission_value);
    @endphp

    <main class="page">
        <header class="header">
            <section>
                <h1 class="company-name">{{ $company['name'] }}</h1>
                @if($company['address'])
                    <div>{{ $company['address'] }}</div>
                @else
                    <div class="muted">عنوان المعرض</div>
                @endif

                @if($company['phone'])
                    <div>{{ $company['phone'] }}</div>
                @else
                    <div class="muted">رقم الهاتف</div>
                @endif

                @if($company['email'])
                    <div>{{ $company['email'] }}</div>
                @endif
            </section>

            <section>
                <p class="title">مستند عمولة شريك</p>
                <div class="meta">
                    <div><strong>{{ $invoice->invoice_number }}</strong></div>
                    <div>{{ $invoice->invoice_date->format('Y-m-d') }}</div>
                    <div>{{ $invoice->status->label() }}</div>
                </div>
            </section>
        </header>

        <section class="grid">
            <div class="box">
                <h2>بيانات الشريك</h2>
                <div class="row"><span>الشريك</span><strong>{{ $invoice->partner->name }}</strong></div>
                <div class="row"><span>الهاتف</span><span>{{ $invoice->partner->phone ?: '-' }}</span></div>
                <div class="row"><span>البريد الإلكتروني</span><span>{{ $invoice->partner->email ?: '-' }}</span></div>
                <div class="row"><span>النوع</span><span>{{ $invoice->partner->type->label() }}</span></div>
            </div>

            <div class="box">
                <h2>مرجع فاتورة العميل</h2>
                <div class="row"><span>رقم الفاتورة</span><strong>{{ $invoice->invoice_number }}</strong></div>
                <div class="row"><span>التاريخ</span><span>{{ $invoice->invoice_date->format('Y-m-d') }}</span></div>
                <div class="row"><span>العميل</span><span>{{ $invoice->customer?->name ?: 'عميل نقدي' }}</span></div>
                @if($invoice->quotation)
                    <div class="row"><span>عرض السعر</span><span>{{ $invoice->quotation->quotation_number }}</span></div>
                @endif
            </div>
        </section>

        <section class="summary">
            <div class="row">
                <span>إجمالي فاتورة العميل</span>
                <strong class="number">{{ \App\Support\Money::format($invoice->gross_total) }}</strong>
            </div>
            <div class="row">
                <span>نوع العمولة</span>
                <strong>{{ $commissionTypes[$invoice->partner_commission_type] ?? $invoice->partner_commission_type }}</strong>
            </div>
            <div class="row">
                <span>قيمة العمولة</span>
                <strong class="number">{{ $commissionValue }}</strong>
            </div>
            <div class="row">
                <span>مبلغ عمولة الشريك</span>
                <strong class="number">{{ \App\Support\Money::format($invoice->partner_commission_amount) }}</strong>
            </div>
            <div class="row total">
                <span>صافي المبلغ بعد العمولة</span>
                <strong class="number">{{ \App\Support\Money::format($invoice->net_revenue_after_partner_commission) }}</strong>
            </div>
        </section>

        @if($invoice->notes)
            <section class="notes">
                <strong>ملاحظات</strong>
                <div>{{ $invoice->notes }}</div>
            </section>
        @endif

        <footer class="footer">
            هذا المستند منفصل عن فاتورة العميل ويستخدم لتوضيح عمولة الشريك الخاصة بالفاتورة المشار إليها.
        </footer>
    </main>
</body>
</html>
