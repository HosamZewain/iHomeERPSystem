<?php

namespace App\Enums;

enum ExpensePaymentStatus: string
{
    case Unpaid = 'unpaid';
    case PartiallyPaid = 'partially_paid';
    case Paid = 'paid';

    public function label(): string
    {
        return match ($this) {
            self::Unpaid => 'غير مدفوع',
            self::PartiallyPaid => 'مدفوع جزئيًا',
            self::Paid => 'مدفوع بالكامل',
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

    public static function fromAmounts(float $paidAmount, float $totalAmount): self
    {
        $paidAmount = round(max($paidAmount, 0), 2);
        $totalAmount = round(max($totalAmount, 0), 2);

        if ($paidAmount <= 0) {
            return self::Unpaid;
        }

        if ($paidAmount >= $totalAmount && $totalAmount > 0) {
            return self::Paid;
        }

        return self::PartiallyPaid;
    }
}
