<?php

namespace App\Enums;

enum InvoicePaymentStatus: string
{
    case Unpaid = 'unpaid';
    case PartiallyPaid = 'partially_paid';
    case Paid = 'paid';

    public function label(): string
    {
        return match ($this) {
            self::Unpaid => __('ui.payment_status.unpaid'),
            self::PartiallyPaid => __('ui.payment_status.partially_paid'),
            self::Paid => __('ui.payment_status.paid'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Unpaid => 'gray',
            self::PartiallyPaid => 'yellow',
            self::Paid => 'green',
        };
    }

    public static function fromAmounts(float $paidAmount, float $invoiceTotal): self
    {
        $paidAmount = round(max($paidAmount, 0), 2);
        $invoiceTotal = round(max($invoiceTotal, 0), 2);

        if ($paidAmount <= 0) {
            return self::Unpaid;
        }

        if ($paidAmount >= $invoiceTotal && $invoiceTotal > 0) {
            return self::Paid;
        }

        return self::PartiallyPaid;
    }
}
