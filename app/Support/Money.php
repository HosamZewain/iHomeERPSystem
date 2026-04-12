<?php

namespace App\Support;

class Money
{
    public static function format(float|int|string|null $amount): string
    {
        return number_format((float) ($amount ?? 0), 2) . ' ج.م';
    }
}
