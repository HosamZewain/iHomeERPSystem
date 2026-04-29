<?php

namespace App\Enums;

enum SalesInvoiceStatus: string
{
    case Draft = 'draft';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';
    case Returned = 'returned';

    public function label(): string
    {
        return match ($this) {
            self::Draft => __('ui.status.draft'),
            self::Confirmed => __('ui.status.confirmed'),
            self::Cancelled => __('ui.status.cancelled'),
            self::Returned => __('ui.status.returned'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Confirmed => 'green',
            self::Cancelled => 'red',
            self::Returned => 'amber',
        };
    }
}
