<?php

namespace App\Enums;

enum SalesInvoiceStatus: string
{
    case Draft = 'draft';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => __('ui.status.draft'),
            self::Confirmed => __('ui.status.confirmed'),
            self::Cancelled => __('ui.status.cancelled'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Confirmed => 'green',
            self::Cancelled => 'red',
        };
    }
}
