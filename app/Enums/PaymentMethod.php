<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case BankTransfer = 'bank_transfer';
    case Instapay = 'instapay';
    case Card = 'card';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Cash => __('ui.payment_methods.cash'),
            self::BankTransfer => __('ui.payment_methods.bank_transfer'),
            self::Instapay => __('ui.payment_methods.instapay'),
            self::Card => __('ui.payment_methods.card'),
            self::Other => __('ui.payment_methods.other'),
        };
    }
}
