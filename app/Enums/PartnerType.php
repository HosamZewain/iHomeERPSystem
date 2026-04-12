<?php

namespace App\Enums;

enum PartnerType: string
{
    case EngineeringOffice = 'engineering_office';
    case Company = 'company';
    case Individual = 'individual';

    public function label(): string
    {
        return match ($this) {
            self::EngineeringOffice => __('ui.partner_types.engineering_office'),
            self::Company => __('ui.partner_types.company'),
            self::Individual => __('ui.partner_types.individual'),
        };
    }
}
