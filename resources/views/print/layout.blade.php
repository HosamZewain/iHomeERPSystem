@php
    $layout = $printSettings['layout'] ?? [];
    $company = $printSettings['company'] ?? [];
    $general = $printSettings['general'] ?? [];
    $warranty = $printSettings['warranty'] ?? [];
    $showHeaderImage = \App\Support\PrintTemplateSettings::truthy($printSettings, 'company.show_header_image') && filled($company['header_image_path'] ?? null);
    $showFooterImage = \App\Support\PrintTemplateSettings::truthy($printSettings, 'company.show_footer_image') && filled($company['footer_image_path'] ?? null);
    $paperSize = $layout['paper_size'] ?? 'A4';
    $margin = match($layout['margin'] ?? 'normal') {
        'narrow' => '10mm',
        'wide' => '18mm',
        default => '14mm',
    };
    $pagePadding = match($layout['margin'] ?? 'normal') {
        'narrow' => '14mm',
        'wide' => '22mm',
        default => '18mm',
    };
    $fontSize = match($layout['font_size'] ?? 'normal') {
        'small' => '12px',
        'large' => '14px',
        default => '13px',
    };
    $titleSize = match($layout['font_size'] ?? 'normal') {
        'small' => '24px',
        'large' => '30px',
        default => '28px',
    };
    $rowPadding = ($layout['spacing'] ?? 'normal') === 'compact' ? '2px 0' : '3px 0';
    $tablePadding = ($layout['table_density'] ?? 'normal') === 'compact' ? '6px 8px' : '9px 10px';
    $logoWidth = match($layout['logo_size'] ?? 'medium') {
        'small' => '42px',
        'large' => '82px',
        default => '60px',
    };
    $headerClass = ($layout['header_alignment'] ?? 'split') === 'center' ? 'header center' : 'header';
@endphp

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $htmlTitle ?? $documentTitle ?? config('app.name') }}</title>
    <style>
        @page {
            size: {{ $paperSize }};
            margin: {{ $margin }};
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #eef2f3;
            color: #1f2933;
            font-family: Arial, "Tahoma", sans-serif;
            font-size: {{ $fontSize }};
            line-height: 1.6;
        }

        .toolbar {
            max-width: 210mm;
            margin: 16px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
            padding: 0 10px;
        }

        .template-selector {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px 10px;
            padding: 8px 10px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: #ffffff;
        }

        .template-selector label {
            color: #111827;
            font-size: 12px;
            font-weight: 700;
        }

        .template-selector select {
            min-height: 40px;
            min-width: 240px;
            max-width: 340px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: #ffffff;
            padding: 8px 12px;
            color: #111827;
            font: inherit;
        }

        .template-selector .hint {
            color: #6b7280;
            font-size: 11px;
        }

        .toolbar-actions {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
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
            padding: {{ $pagePadding }};
            background: #ffffff;
            box-shadow: 0 18px 48px rgba(31, 41, 51, 0.14);
            border: 1px solid #d8e0e4;
        }

        .warranty-page {
            page-break-before: always;
        }

        .image-strip {
            width: 100%;
            display: block;
            object-fit: contain;
            margin-bottom: 16px;
            border-radius: 6px;
        }

        .footer-image {
            margin-top: 18px;
            margin-bottom: 0;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: stretch;
            gap: 18px;
            margin-bottom: 18px;
            padding: 14px;
            border: 1px solid #d8e0e4;
            border-top: 6px solid #7cc342;
            border-radius: 8px;
            background: linear-gradient(180deg, #ffffff 0%, #f7faf8 100%);
        }

        .header.center {
            flex-direction: column;
            align-items: center;
            text-align: center;
            gap: 12px;
        }

        .brand {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            min-width: 0;
        }

        .header.center .brand {
            flex-direction: column;
            align-items: center;
        }

        .logo {
            width: {{ $logoWidth }};
            height: {{ $logoWidth }};
            object-fit: contain;
            flex-shrink: 0;
        }

        .company-name {
            margin: 0 0 6px;
            font-size: 23px;
            font-weight: 700;
            color: #1d5f43;
        }

        .muted {
            color: #66737a;
        }

        .title {
            margin: 0;
            font-size: {{ $titleSize }};
            font-weight: 700;
            text-align: left;
            color: #172026;
        }

        .header.center .title,
        .header.center .meta {
            text-align: center;
        }

        .meta {
            margin-top: 10px;
            text-align: left;
            color: #4b5960;
            font-size: 12px;
            line-height: 1.8;
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-top: 16px;
        }

        .box {
            border: 1px solid #d8e0e4;
            border-radius: 8px;
            padding: 12px 14px;
            background: #ffffff;
        }

        .box h2 {
            margin: 0 0 10px;
            font-size: 15px;
            color: #172026;
            padding-bottom: 7px;
            border-bottom: 1px solid #e7ecef;
        }

        .row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: {{ $rowPadding }};
            align-items: flex-start;
        }

        .row span:first-child {
            color: #66737a;
            white-space: nowrap;
        }

        .row strong,
        .row span:last-child {
            text-align: left;
        }

        .section-title {
            margin: 20px 0 8px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            color: #172026;
        }

        .section-title h2 {
            margin: 0;
            font-size: 16px;
            font-weight: 700;
        }

        .section-title span {
            color: #66737a;
            font-size: 12px;
        }

        table {
            width: 100%;
            margin-top: 8px;
            border-collapse: collapse;
            border: 1px solid #d8e0e4;
            border-radius: 8px;
            overflow: hidden;
        }

        th,
        td {
            border: 1px solid #e3e9ec;
            padding: {{ $tablePadding }};
            vertical-align: top;
            text-align: right;
        }

        th {
            background: #eef7ef;
            color: #172026;
            font-size: 12px;
            font-weight: 700;
        }

        tbody tr:nth-child(even) {
            background: #fbfcfc;
        }

        .sequence {
            width: 9mm;
            text-align: center;
        }

        .image-column {
            width: 18mm;
            text-align: center;
        }

        .product-image {
            width: 15mm;
            height: 15mm;
            object-fit: contain;
            display: inline-block;
        }

        .number {
            direction: ltr;
            text-align: left;
            white-space: nowrap;
        }

        .product-name {
            font-weight: 700;
            color: #172026;
        }

        .service-box {
            margin-top: 16px;
            background: #f8fbf8;
            border-color: #cfe4d2;
        }

        .service-box .total {
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1px solid #cfe4d2;
        }

        .summary {
            width: 86mm;
            margin: 18px 0 0 auto;
            border: 1px solid #d8e0e4;
            border-radius: 8px;
            overflow: hidden;
            background: #ffffff;
        }

        .summary .row {
            border-bottom: 1px solid #edf1f3;
            padding: 8px 12px;
        }

        .summary .total {
            border-bottom: 0;
            font-size: 17px;
            font-weight: 700;
            background: #eef7ef;
        }

        .notes,
        .terms {
            margin-top: 16px;
            border: 1px solid #d8e0e4;
            border-radius: 8px;
            padding: 12px 14px;
            background: #ffffff;
            white-space: pre-line;
        }

        .footer {
            margin-top: 22px;
            padding-top: 12px;
            border-top: 1px solid #d8e0e4;
            color: #66737a;
            font-size: 12px;
            white-space: pre-line;
        }

        .warranty-content {
            margin-top: 28px;
            white-space: pre-line;
            color: #111827;
            line-height: 1.9;
        }

        @media screen and (max-width: 820px) {
            .page {
                width: calc(100vw - 20px);
                min-height: auto;
                padding: 14px;
            }

            .toolbar,
            .template-selector,
            .template-selector select,
            .toolbar-actions,
            .button {
                width: 100%;
            }

            .header,
            .grid {
                grid-template-columns: 1fr;
                flex-direction: column;
            }

            .title,
            .meta {
                text-align: right;
            }

            .summary {
                width: 100%;
            }

            table {
                font-size: 12px;
            }
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
                border: 0;
            }
        }
    </style>
</head>
<body>
    <div class="toolbar no-print">
        @if(($availableTemplates ?? collect())->isNotEmpty())
            <form method="GET" action="{{ url()->current() }}" class="template-selector" aria-label="اختيار قالب الطباعة">
                <label for="template">اختيار قالب الطباعة</label>
                <select id="template" name="template" onchange="this.form.submit()">
                    @foreach($availableTemplates as $templateOption)
                        <option value="{{ $templateOption->id }}" @selected(($selectedTemplate?->id ?? null) === $templateOption->id)>
                            {{ $templateOption->name }}{{ $templateOption->is_default ? ' - افتراضي' : '' }}
                        </option>
                    @endforeach
                </select>
                <span class="hint">القالب الحالي: {{ $selectedTemplate?->name ?? 'القالب الافتراضي' }}</span>
            </form>
        @else
            <div class="template-selector">
                <strong>قالب الطباعة</strong>
                <span class="hint">لا توجد قوالب نشطة لهذا النوع. يتم استخدام القالب الافتراضي المؤقت.</span>
            </div>
        @endif
        <div class="toolbar-actions">
            <a class="button" href="{{ $backRoute }}">الرجوع</a>
            <button class="button primary" type="button" onclick="window.print()">طباعة / حفظ PDF</button>
        </div>
    </div>

    <main class="page">
        @if($showHeaderImage)
            <img class="image-strip" src="{{ $company['header_image_path'] }}" alt="ترويسة الطباعة">
        @endif

        <header class="{{ $headerClass }}">
            <section class="brand">
                @if(! blank($company['logo_path'] ?? '') && \App\Support\PrintTemplateSettings::truthy($printSettings, 'company.show_logo'))
                    <img class="logo" src="{{ $company['logo_path'] }}" alt="{{ $company['name'] ?? 'iHome' }}">
                @endif

                <div>
                    <h1 class="company-name">{{ $company['name'] ?? 'iHome' }}</h1>

                    @if(\App\Support\PrintTemplateSettings::truthy($printSettings, 'company.show_address'))
                        <div>{{ filled($company['address'] ?? null) ? $company['address'] : 'عنوان المعرض' }}</div>
                    @endif

                    @if(\App\Support\PrintTemplateSettings::truthy($printSettings, 'company.show_phone'))
                        <div>{{ filled($company['phone'] ?? null) ? $company['phone'] : 'رقم الهاتف' }}</div>
                    @endif

                    @if(\App\Support\PrintTemplateSettings::truthy($printSettings, 'company.show_email') && filled($company['email'] ?? null))
                        <div>{{ $company['email'] }}</div>
                    @endif

                    @if(\App\Support\PrintTemplateSettings::truthy($printSettings, 'company.show_website') && filled($company['website'] ?? null))
                        <div>{{ $company['website'] }}</div>
                    @endif

                    @if(\App\Support\PrintTemplateSettings::truthy($printSettings, 'company.show_tax_number') && filled($company['tax_number'] ?? null))
                        <div>الرقم الضريبي: {{ $company['tax_number'] }}</div>
                    @endif

                    @if(\App\Support\PrintTemplateSettings::truthy($printSettings, 'company.show_registration_number') && filled($company['registration_number'] ?? null))
                        <div>السجل التجاري: {{ $company['registration_number'] }}</div>
                    @endif
                </div>
            </section>

            <section>
                <p class="title">{{ $documentTitle }}</p>
                <div class="meta">
                    @yield('document-meta')
                </div>
            </section>
        </header>

        @yield('content')

        <footer class="footer">
            @if(filled($documentFooterText ?? null))
                <div>{{ $documentFooterText }}</div>
            @endif

            @if(\App\Support\PrintTemplateSettings::truthy($printSettings, 'general.show_thank_you_message') && filled($general['thank_you_message'] ?? null))
                <div>{{ $general['thank_you_message'] }}</div>
            @endif

            @if(filled($general['footer_text'] ?? null))
                <div>{{ $general['footer_text'] }}</div>
            @endif

            @if(\App\Support\PrintTemplateSettings::truthy($printSettings, 'general.show_disclaimer') && filled($general['disclaimer'] ?? null))
                <div>{{ $general['disclaimer'] }}</div>
            @endif
        </footer>

        @if($showFooterImage)
            <img class="image-strip footer-image" src="{{ $company['footer_image_path'] }}" alt="تذييل الطباعة">
        @endif
    </main>

    @if(\App\Support\PrintTemplateSettings::truthy($printSettings, 'warranty.enabled'))
        <section class="page warranty-page">
            @if($showHeaderImage)
                <img class="image-strip" src="{{ $company['header_image_path'] }}" alt="ترويسة شروط الضمان">
            @endif

            <header class="{{ $headerClass }}">
                <section class="brand">
                    @if(! blank($company['logo_path'] ?? '') && \App\Support\PrintTemplateSettings::truthy($printSettings, 'company.show_logo'))
                        <img class="logo" src="{{ $company['logo_path'] }}" alt="{{ $company['name'] ?? 'iHome' }}">
                    @endif

                    <div>
                        <h1 class="company-name">{{ $company['name'] ?? 'iHome' }}</h1>
                        @if(\App\Support\PrintTemplateSettings::truthy($printSettings, 'company.show_phone'))
                            <div>{{ filled($company['phone'] ?? null) ? $company['phone'] : 'رقم الهاتف' }}</div>
                        @endif
                    </div>
                </section>

                <section>
                    <p class="title">{{ $warranty['title'] ?? 'شروط الضمان' }}</p>
                </section>
            </header>

            <div class="warranty-content">{{ $warranty['body'] ?? '' }}</div>

            @if(filled($warranty['footer_text'] ?? null))
                <footer class="footer">{{ $warranty['footer_text'] }}</footer>
            @endif

            @if($showFooterImage)
                <img class="image-strip footer-image" src="{{ $company['footer_image_path'] }}" alt="تذييل شروط الضمان">
            @endif
        </section>
    @endif
</body>
</html>
