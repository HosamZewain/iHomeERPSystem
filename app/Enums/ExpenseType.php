<?php

namespace App\Enums;

enum ExpenseType: string
{
    case OneTime = 'one_time';
    case Recurring = 'recurring';

    public function label(): string
    {
        return match ($this) {
            self::OneTime => 'مرة واحدة',
            self::Recurring => 'متكرر',
        };
    }
}
