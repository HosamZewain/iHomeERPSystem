<?php

namespace App\Enums;

enum CommissionType: string
{
    case Percentage = 'percentage';
    case Fixed = 'fixed';

    public function label(): string
    {
        return match ($this) {
            self::Percentage => __('ui.commission_types.percentage'),
            self::Fixed => __('ui.commission_types.fixed'),
        };
    }
}
