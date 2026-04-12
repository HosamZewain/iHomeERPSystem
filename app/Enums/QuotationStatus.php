<?php

namespace App\Enums;

enum QuotationStatus: string
{
    case Draft = 'draft';
    case Sent = 'sent';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Expired = 'expired';
    case Converted = 'converted';

    public function label(): string
    {
        return match ($this) {
            self::Draft => __('ui.status.draft'),
            self::Sent => __('ui.status.sent'),
            self::Approved => __('ui.status.approved'),
            self::Rejected => __('ui.status.rejected'),
            self::Expired => __('ui.status.expired'),
            self::Converted => __('ui.status.converted'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Sent => 'blue',
            self::Approved => 'green',
            self::Rejected => 'red',
            self::Expired => 'yellow',
            self::Converted => 'purple',
        };
    }
}
