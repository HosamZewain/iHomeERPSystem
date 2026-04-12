<?php

namespace App\Enums;

enum SalesChannel: string
{
    case Direct = 'direct';
    case Partner = 'partner';

    public function label(): string
    {
        return match ($this) {
            self::Direct => __('ui.sales_channels.direct'),
            self::Partner => __('ui.sales_channels.partner'),
        };
    }
}
