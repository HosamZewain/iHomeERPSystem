@php
    $paidAmountAfter = (float) $invoice->gross_total - (float) $payment->remaining_amount_after;
    $paymentStatusAfter = \App\Enums\InvoicePaymentStatus::fromAmounts($paidAmountAfter, (float) $invoice->gross_total);
@endphp

@extends('print.layout')

@section('document-meta')
    <div><strong>{{ $payment->receipt_number }}</strong></div>
    <div>{{ $payment->payment_date->format('Y-m-d') }}</div>
    <div>{{ $payment->payment_method->label() }}</div>
@endsection

@section('content')
    <section class="grid">
        <div class="box">
            <h2>بيانات العميل</h2>
            <div class="row"><span>العميل</span><strong>{{ $invoice->customer?->name ?: 'عميل نقدي' }}</strong></div>
            <div class="row"><span>رقم الفاتورة</span><span>{{ $invoice->invoice_number }}</span></div>
            @if($invoice->quotation)
                <div class="row"><span>عرض السعر</span><span>{{ $invoice->quotation->quotation_number }}</span></div>
            @endif
            @if($invoice->customer?->phone)
                <div class="row"><span>الهاتف</span><span>{{ $invoice->customer->phone }}</span></div>
            @endif
        </div>

        <div class="box">
            <h2>بيانات الدفعة</h2>
            <div class="row"><span>رقم الإيصال</span><strong>{{ $payment->receipt_number }}</strong></div>
            <div class="row"><span>تاريخ الدفعة</span><span>{{ $payment->payment_date->format('Y-m-d') }}</span></div>
            <div class="row"><span>طريقة الدفع</span><span>{{ $payment->payment_method->label() }}</span></div>
            <div class="row"><span>المبلغ المستلم</span><strong class="number">{{ \App\Support\Money::format($payment->amount) }}</strong></div>
            @if($payment->reference_number)
                <div class="row"><span>رقم المرجع</span><span>{{ $payment->reference_number }}</span></div>
            @endif
            <div class="row"><span>المستلم</span><span>{{ $payment->receiver?->name ?: $payment->creator?->name ?: '-' }}</span></div>
        </div>
    </section>

    <section class="summary">
        <div class="row">
            <span>إجمالي فاتورة العميل</span>
            <strong class="number">{{ \App\Support\Money::format($invoice->gross_total) }}</strong>
        </div>
        <div class="row">
            <span>إجمالي المدفوع بعد هذه الدفعة</span>
            <strong class="number">{{ \App\Support\Money::format($paidAmountAfter) }}</strong>
        </div>
        <div class="row">
            <span>المتبقي بعد هذه الدفعة</span>
            <strong class="number">{{ \App\Support\Money::format($payment->remaining_amount_after) }}</strong>
        </div>
        <div class="row total">
            <span>حالة السداد بعد هذه الدفعة</span>
            <strong>{{ $paymentStatusAfter->label() }}</strong>
        </div>
    </section>

    @if($payment->notes)
        <section class="notes">
            <strong>ملاحظات الدفعة</strong>
            <div>{{ $payment->notes }}</div>
        </section>
    @endif
@endsection
