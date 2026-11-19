<?php

namespace App\Enums;

enum SignoffKind: string
{
    case Signoff = 'signoff';
    case Verification = 'verification';

    public function label(): string
    {
        return __('enums.signoff_kind.'.$this->value);
    }
}
